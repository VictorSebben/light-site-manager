/*
 *
 * This video-gallery app will be responsible for insertion, deletion and updating of the
 * video gallery related to a Post.
 *
 */

var lsmVideo = ( function () {
    var l = console.log.bind(console);

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

    function validateInsert( title, pos, iframe ) {
        var errMsg = '';

        if ( title === '' || iframe === '' ) {
            errMsg += 'Preencha todos os campos (Título, Posição e iframe)!';
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
        l($('#' + fieldId));
        var field = $( '#' + fieldId ).parent();
        l( field );
        var divErr = $( '<div class="err-msg-tooltip">' + msg + '</div>' );

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
            iframe: 'iframe'
        };

        if ( ( item.type === 'title' || item.type === 'iframe' )
            && ( ! item.val ) ) {
            showErrMsgUpdate( 'Campo ' + msgMap[ item.type ] + ' deve conter um texto.', item.id );
            return false;
        } else if ( item.type === 'position' && item.val != parseInt( item.val, 10 ) ) {
            showErrMsgUpdate( 'Campo ' + msgMap[ item.type ] + ' deve conter um número.', item.id );
            return false;
        }

        return true;
    }

    $( '#insert-iframe' ).blur( function ( evt ) {
        var iframe = $( this ).val();
        var preview = $( '#video-preview' );

        // If the field is empty or it is not an iframe tag,
        // just put back the default preview text
        if ( ! iframe
            || iframe.indexOf( '<iframe' ) == -1 ) {
            preview.html( '<span id="insert-preview" class="preview">Preview</span>' );
        }

        // Else, show video preview
        else {
            iframe = $( iframe );
            iframe.attr( 'width', 250 );
            iframe.attr( 'height', 130 );
            preview.html( iframe );
        }
    } );

    $( '#btn-insert' ).click( function( evt ) {
        evt.preventDefault();

        var title = $( '#insert-title' );
        var position = $( '#insert-position' );
        var iframe = $( '#insert-iframe' );

        if ( ! validateInsert( title[ 0 ].value, position[ 0 ].value, iframe[ 0 ].value ) ) {
            return;
        }

        insertVideo();
    } );

    function insertVideo() {
        // Get fields.
        var title = $( '#insert-title' );
        var position = $( '#insert-position' );
        var iframe = $( '#insert-iframe' );

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
                    iframe: iframe[ 0 ].value
                },
                dataType: 'json'
            } )
                .done( function( result ) {
                    if ( result.isOk ) {
                        // Clean fields
                        title[ 0 ].value = '';
                        position[ 0 ].value = '';
                        iframe[ 0 ].value = '';

                        // Reload items
                        reloadItems();
                    } else {
                        showErrMsgInsert( result.error );
                    }
                } )
                .fail( function() {
                    // Clean fields
                    title[ 0 ].value = '';
                    position[ 0 ].value = '';
                    iframe[ 0 ].value = '';

                    // Show error message
                    showErrMsgInsert( 'Não foi possível inserir. Contate o suporte!' );
                } );
        } catch ( e ) {
            l( e );
        }
    }

    /**
     * This function will load the items from the database
     * using ajax
     */
    function reloadItems() {
        // TODO sort when item was inserted and when a position was updated
        l( 'Items reloaded' );
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
                        // Change data-db attribute to reflex
                        // the new value
                        $obj.parent().attr( 'data-db', $obj[ 0 ].value );

                        // Turn field back into pure text
                        input2text( $obj );

                        // If the position changed, we will reload the items
                        if ( $obj.attr( 'data-type' ) === 'position' )
                            reloadItems();
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

    var editable = $( '.title, .position, .iframe' );

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

        if ( fieldId.indexOf( 'iframe' ) !== -1 ) {
            $( this ).html( '<textarea data-type="' + fieldClass + '" id="edit-' + fieldId + '" class="editable ' + fieldClass + '" name="' + fieldId + '">' + old + '</textarea>' );
        } else {
            $( this ).html( '<input data-type="' + fieldClass + '" id="edit-' + fieldId + '" class="editable ' + fieldClass + '" value="' + old + '" name="' + fieldId + '">' );
        }

        $( '#edit-' + fieldId ).focus();
    }

    function input2text( obj ) {
        var parent = obj.parent();
        parent.css( 'background-color', '' );
        parent.bind( 'dblclick', text2input );

        var data = parent.attr( 'data-db' );

        if ( obj.attr( 'class' ).indexOf( 'iframe' ) !== -1 ) {
            data = htmlencode( data );
        }

        parent.html( data );
    }
// TODO UPDATE PREVIEW ON CHANGE OF THE IFRAME FIELD
// FIXME ON ESC ON IFRAME FIELD, IT IS TURING CHARS INTO ENTITIES
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
}() );



// TODO ON CLICKING OUT OF THE INPUT FIELD OR HITTING ENTER, SAVE
// TODO ON HITTING ESC, CANCEL SAVE
// TODO TURN FIELD BACK INTO A TEXT FIELD
// TODO IF POSITION WAS CHANGED, SORT
// TODO >>> SORTING: SELECT ALL VIDEOS; REMOVE LIST AND ADD LOADING ICON; SORT BY POSITION; REMOVE ICON AND READD LIST ITEMS

// TODO IF IFRAME WAS CHANGED, CHANGE PREVIEW
