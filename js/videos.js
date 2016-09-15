/*
 *
 * This video-gallery app will be responsible for insertion, deletion and updating of the
 * video gallery related to a Post.
 *
 */

var lsmVideo = ( function () {
    var l = console.log.bind(console);

    function assembleIframe( url, width, height ) {
        if ( /vimeo/.exec( url ) ) {
            url = url.split( '/' );
            return '<iframe src="https://player.vimeo.com/video/' + url[ url.length - 1 ]
                + '" width="' + width + '" height="' + height
                + '" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
        } else if ( /youtube/.exec( url ) ) {
            url = url.split( '=' );
            return '<iframe width="' + width + '" height="' + height
                + '" src="https://www.youtube.com/embed/' + url[ url.length - 1 ]
                + '" frameborder="0" allowfullscreen></iframe>';
        }

        return 'Preview';
    }

    function htmlencode(str) {
        return str.replace(/[&<>"']/g, function($0) {
            return "&" + {"&":"amp", "<":"#60", ">":"#62", '"':"#34", "'":"#39"}[$0] + ";";
        });
    }

    function showErrMsgInsert( errMsg ) {
        // Show error message on page for 10 seconds
        var btn = $( '#btn-insert' );
        var err = $( '#msg' );
        err.html( errMsg );
        err.fadeIn( 'slow' );
        btn.attr( 'disabled', 'disabled' );

        window.setTimeout( function () {
            err.fadeOut( 'slow', function () {
                err.html( '' );
            } );
            btn.removeAttr( 'disabled' );
        }, 3000 );
    }

    function validateInsert( title, pos, url ) {
        var errMsg = '';

        if ( title === '' || url === '' ) {
            errMsg += 'Preencha todos os campos (Título, Posição e Url)!';
        } else if ( pos !== '' && pos != parseInt( pos, 10 ) ) {
            errMsg += 'A posição deve ser um número!';
        }

        if ( errMsg ) {
            showErrMsgInsert( errMsg );
            return false;
        }

        return true;
    }

    function showErrMsgUpdate( msg, fieldId ) {
        // Obter div container]
        var field = $( '#' + fieldId ).parent();
        var divErr = $( '<div class="err-msg err-msg-tooltip">' + msg + '</div>' );

        field.after( divErr );
        divErr.width( divErr.parent().width() );

        window.setTimeout( function () {
            divErr.toggle( 'fade' );
            divErr.remove();
        }, 3000 );
    }

    function validateUpdate( item ) {
        item.id = item.id || undefined;
        item.val = item.val || undefined;
        item.type = item.type || undefined;

        var msgMap = {
            title: 'Título',
            position: 'Posição',
            url: 'Url'
        };

        if ( ( item.type === 'title' || item.type === 'url' )
            && ( ! item.val ) ) {
            showErrMsgUpdate( 'Campo ' + msgMap[ item.type ] + ' deve conter um texto.', item.id );
            return false;
        } else if ( item.type === 'position' && item.val != parseInt( item.val, 10 ) ) {
            showErrMsgUpdate( 'Campo ' + msgMap[ item.type ] + ' deve conter um número.', item.id );
            return false;
        }

        return true;
    }

    $( '#insert-url' ).blur( function ( evt ) {
        var url = $( this ).val();
        var preview = $( '#video-preview' );

        // If the field is empty, just put back the default preview text
        if ( ! url ) {
            preview.html( '<span id="insert-preview" class="preview">Preview</span>' );
        }

        // Else, show video preview
        else {
            // Create iframe tag using url
            preview.html( assembleIframe( url, 250, 130 ) );
        }
    } );

    $( '#btn-insert' ).click( function( evt ) {
        evt.preventDefault();

        var title = $( '#insert-title' );
        var position = $( '#insert-position' );
        var url = $( '#insert-url' );

        if ( ! validateInsert( title[ 0 ].value, position[ 0 ].value, url[ 0 ].value ) ) {
            return;
        }

        insertVideo();
    } );

    function insertVideo() {
        // Get fields.
        var title = $( '#insert-title' );
        var position = $( '#insert-position' );
        var url = $( '#insert-url' );

        //
        // Send ajax request to insert video
        //
        try {
            $.ajax( {
                method: 'POST',
                url: window.lsmConf.baseUrl + '/posts/' + $( '#post-id' )[ 0 ].value + '/insert-video/',
                data: {
                    title: title[ 0 ].value,
                    position: position[ 0 ].value,
                    url: url[ 0 ].value
                },
                dataType: 'json'
            } )
                .done( function( result ) {
                    if ( result.isOk ) {
                        // Clean fields
                        title[ 0 ].value = '';
                        position[ 0 ].value = '';
                        url[ 0 ].value = '';

                        // Reload items
                        reloadPage();
                    } else {
                        showErrMsgInsert( result.error );
                    }
                } )
                .fail( function() {
                    // Clean fields
                    title[ 0 ].value = '';
                    position[ 0 ].value = '';
                    url[ 0 ].value = '';

                    // Show error message
                    showErrMsgInsert( 'Não foi possível inserir. Contate o suporte!' );
                } );
        } catch ( e ) {
            l( e );
        }
    }

    /**
     * Reload the page
     */
    function reloadPage() {
        window.location.href = window.location.href;
    }

    /**
     * Reload video preview after the url
     * field is edited.
     */
    function reloadPreview( videoId, url ) {
        $( '#video-preview-' + videoId ).html( assembleIframe( url, 200, 100 ) );
    }

    function updateVideo( $obj, videoId ) {
        // Create data object that will be sent via ajax
        var data = {
            id: videoId
        };
        data[ $obj.attr( 'data-type' ) ] = $obj[ 0 ].value;

        try {
            $.ajax( {
                method: 'POST',
                url: window.lsmConf.baseUrl + '/posts/' + $( '#post-id' )[ 0 ].value + '/update-video/',
                data: data,
                dataType: 'json'
            } )
                .done( function( result ) {
                    if ( result.isOk ) {
                        var data = $obj[ 0 ].value;

                        if ( $obj.attr( 'class' ).indexOf( 'url' ) !== -1 ) {
                            data = htmlencode( data );
                            reloadPreview( videoId, $obj[ 0 ].value );
                        }

                        // Change data-db attribute to reflex
                        // the new value
                        $obj.parent().attr( 'data-db', data );

                        // Turn field back into pure text
                        input2text( $obj );

                        // If the position changed, we will reload the items
                        if ( $obj.attr( 'data-type' ) === 'position' )
                            reloadPage();
                    } else {
                        input2text( $obj );
                        showErrMsgInsert( result.error, $obj.attr( 'id' ) );
                    }
                } )
                .fail( function() {
                    input2text( $obj );

                    // Show error message
                    showErrMsgUpdate( 'Não foi possível inserir. Contate o suporte!', $obj.attr( 'id' ) );
                } );
        } catch ( e ) {
            l( e );
        }
    }

    var editable = $( '.title, .position, .url' );

    editable.bind( 'dblclick', text2input );

    function text2input() {
        var old = $( this ).html();

        $( this ).attr( 'data-db', old );

        var fieldId = $( this ).attr( 'id' );
        var fieldClass = $( this ).attr( 'class' );

        $( this ).css( 'background-color', 'white' );

        // While we are operating, unbind dblclick, or weird things will happen
        // if the user double clicks again
        $( this ).unbind( 'dblclick' );

        $( this ).html( '<input data-type="' + fieldClass + '" id="edit-' + fieldId + '" class="editable ' + fieldClass + '" value="' + old + '" name="' + fieldId + '">' );

        $( '#edit-' + fieldId ).focus();
    }

    function input2text( obj ) {
        var parent = obj.parent();
        parent.css( 'background-color', '' );
        parent.bind( 'dblclick', text2input );
        parent.html( parent.attr( 'data-db' ) );
    }

    /*
     * When the user hits ESC, we'll cancel all the open in-place edition fields,
     * and turn them into pure text again
     */
    $( document ).keypress( function( evt ) {
        if ( evt.keyCode != 27 ) {
            return true;
        }

        // Find all in-place editions that are open and cancel them
        $( '.editable' ).each( function() {
            input2text( $( this ) );
        } );
    } );

    // When the user hits ENTER in the field they are editing,
    // we'll save the change in the DB and turn the input field
    // back into pure text
    editable.on( 'keypress', '.editable', function ( evt ) {
        // If the key pressed is not ENTER, we do nothing
        if ( evt.keyCode != 13 ) {
            return true;
        }

        var videoId = $( this ).closest( 'div.video-item' ).find( '.video-id' )[ 0 ].value;

        // ENTER: try to perform update, show success or error message,
        // and then turn the input field back into regular text
        if ( ! validateUpdate( { id: $( this ).attr( 'id' ), type: $( this ).attr( 'data-type' ), val: $( this ).val() } ) ) {
            return;
        }

        updateVideo( $( this ), videoId );

        // Let us cancel the normal action of the ENTER key
        // (for the textarea fields)
        return false;
    } );

    function destroyVideo( videoId, container ) {
        var id = videoId;
        var data = {
            video_id: id,
            position: $( '#edit-position-' + id ).val() || $( '#position-' + id ).html()
        };

        try {
            $.ajax( {
                    method: 'POST',
                    url: window.lsmConf.baseUrl + '/posts/' + $( '#post-id' )[ 0 ].value + '/destroy-video/',
                    data: data,
                    dataType: 'json'
                } )
                .done( function( result ) {
                    if ( result.isOk ) {
                        // Reload
                        reloadPage();
                    } else {
                        var divErr = $( '<div style="display: none;" class="err-msg">' + result.error + '</div>' );
                        container.before( divErr );
                        divErr.fadeIn( 'slow' );

                        window.setTimeout( function() {
                            divErr.fadeOut( 'slow', function () {
                                divErr.remove();
                            } );
                        }, 3000 );
                    }
                } )
                .fail( function ( err ) {
                    var divErr = $( '<div style="display: none;" class="err-msg">Não foi possível inserir. Contate o suporte!</div>' );
                    container.before( divErr );
                    divErr.fadeIn( 'slow' );

                    window.setTimeout( function() {
                        divErr.fadeOut( 'slow', function () {
                            divErr.remove();
                        } );
                    }, 3000 );
                } );
        } catch ( e ) {
            l( e );
        }
    }

    $( '.btn-remove' ).click( function() {
        // Create message that asks for confirmation
        var container = $( this ).closest( 'div' );

        var divConfirm = $(
            '<div class="destroy-confirm" style="display: none;">' +
                'Deseja realmente remover esse Vídeo? &nbsp;&nbsp;' +
                '<a data-id="' + $( this ).attr( 'data-id' ) + '" class="destroy-yes">Sim</a> &nbsp;&nbsp; <a class="destroy-cancel">Cancelar</a>' +
            '</div>'
        );
        container.before( divConfirm );
        divConfirm.fadeIn( 'slow' );
    } );

    var videoItem = $( '.video-item' );
    videoItem.on( 'click', '.destroy-yes', function() {
        var div = $( this ).parent();

        div.fadeOut( 'slow', function() {
            div.remove();
        } );

        destroyVideo( $( this ).attr( 'data-id' ), div );
    } );

    videoItem.on( 'click', '.destroy-cancel', function( evt ) {
        var div = $( this ).parent();
        div.fadeOut( 'slow', function() {
            div.remove();
        } );
    } );
}() );
