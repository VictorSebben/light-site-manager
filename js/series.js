var DELETE_POSTS = 1;
var DISSOCIATE_POSTS = 0;
var NO_POSTS = -1;

var toggleBtns = document.getElementsByClassName( 'onoffswitch-checkbox' );

for ( var i = 0; i < toggleBtns.length; i++ ) {
    toggleBtns[ i ].addEventListener(
        'change',
        function () {
            sendToggleStatusRequest.apply( this, [ 'series' ] );
        },
        false
    );
}

formConsole.btnRemove.click( ajaxDeleteSeries );
formConsole.btnActivate.click( formConsole.activateItems.bind( undefined, [ 'series' ] ) );
formConsole.btnDeactivate.click( formConsole.deactivateItems.bind( undefined, [ 'series' ] ) );

function ajaxDeleteSeries() {
    var items = getIds( $( '.list-item:checked' ) );

    if ( ! items.length ) return;

    var hasPosts = false;

    for ( i = 0; i < items.length; ++i ) {
        if ( $( '#count-posts-' + items[ i ] ).val() > 0 ) {
            hasPosts = true;
            break;
        }
    }

    var dialogText = '';
    var dialogBtns = [];

    // TODO If there are posts associated with at least one of the series,
    // TODO inquire user about action to take regarding posts (dissociate or delete them),
    // TODO and offer the possibility of canceling the action
    if ( hasPosts ) {
        dialogText = 'Há posts associados à Série: selecione a ação desejada:';
        dialogBtns = [
            {
                text: 'Remover Série e Posts',
                click: function() {
                    removeSeries( items, DELETE_POSTS );
                    $( this ).dialog( 'close' );
                }
            },
            {
                text: 'Remover Série e desassociar Posts',
                click: function() {
                    removeSeries( items, DISSOCIATE_POSTS );
                    $( this ).dialog( 'close' );
                }
            },
            {
                text: 'Cancelar ação',
                click: function() {
                    $( this ).dialog( 'close' );
                }
            }
        ];
    }
    // TODO Else, just show the options to continue or cancel the action
    else {
        dialogText = 'Deseja realmente remover a Série?';
        dialogBtns = [
            {
                text: 'Remover',
                click: function() {
                    removeSeries( items, NO_POSTS );
                    $( this ).dialog( 'close' );
                }
            },
            {
                text: 'Cancelar ação',
                click: function() {
                    $( this ).dialog( 'close' );
                }
            }
        ];
    }

    $( '#dialog' ).dialog( {
        show: 'fade',
        hide: 'fade',
        modal: true,
        width: 'auto',
        position: { my: 'bottom+20' },
        open: function() {
            $( this ).html( '<div style="font-size: 1.1em; color: #c75501">' + dialogText + '</div>' );
        },
        buttons: dialogBtns
    } );
}

function removeSeries( items, actionPosts ) {
    if ( ! items.length ) {
        return;
    }

    try {
        $.ajax( {
            method: 'POST',
            url: window.lsmConf.baseUrl + '/series/delete-ajax',
            data: {
                token: document.getElementById( 'token' ).value,
                items: items,
                'action-posts': actionPosts
            },
            dataType: 'json'
        } )
            .done( callSuccessRemove )
            .fail( callFail );
    } catch ( e ) {
        console.log( e );
    }
}