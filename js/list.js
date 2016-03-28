var toggleAll = document.getElementById( 'toggle-all' );

if ( toggleAll) {
    toggleAll.addEventListener( 'click', function () {
        var items = document.getElementsByClassName( 'list-item' );
        var checked = toggleAll.checked;

        for ( var i = 0; i < items.length; i++ ) {
            items[ i ].checked = checked;
        }
    }, false );
}

function getIds( items ) {
    var ids = [];
    var i = 0;

    items.each( function() {
        ids[i] = $( this ).val();
        i++;
    } );

    return ids;
}

var formConsole = {
    btnActivate: $( '#btn-activate' ),
    btnDeactivate: $( '#btn-deactivate' ),
    btnRemove: $( '#btn-delete' ),

    removeItems: function removeItems( baseRoute ) {
        var items = getIds( $( '.list-item:checked' ) );

        if ( items.length && confirm( 'Deseja remover os itens selecionados?' ) ) {
            callConsoleAjax( items, baseRoute, 'delete-ajax' );
        }
    },
    activateItems: function activateItems( baseRoute ) {
        callConsoleAjax( getIds( $( '.list-item:checked' ) ), baseRoute, 'activate' );
    },
    deactivateItems: function deactivateItems( baseRoute ) {
        callConsoleAjax( getIds( $( '.list-item:checked' ) ), baseRoute, 'deactivate' );
    }
};

function callSuccess( result ) {
    // Remove old messages if they exist
    $( '.err-msg' ).remove();
    $( '.success-msg' ).remove();

    if ( result.isOk ) {
        // If everything was OK, we're printing a success message
        $( '<div class="flash success-msg">' +
              result.success +
           '</div>' ).insertAfter( '#area-header' );
    } else {
        // If something went wrong, we're printing an error message
        $( '<div class="flash err-msg">' +
              result.error +
           '</div>' ).insertAfter( '#area-header' );
    }

    // Reset token
    if ( result.token ) {
        $( '#token' )[0].value = result.token;
    }

    // Uncheck all of the items
    $( '#toggle-all' ).attr( 'checked', false );
    $( '.list-item' ).each( function() {
        $( this ).attr( 'checked', false );
    } );
}

function callSuccessActivate( result ) {
    if ( result.isOk ) {
        // Toggle all status checkboxes of updated items to "Ativo"
        for ( var i = 0; i < result.items.length; i++ ) {
            $( '#onoffswitch-' + result.items[ i ] ).prop( 'checked', 'checked' );
        }
    }

    callSuccess( result );
}

function callSuccessDeactivate( result ) {
    if ( result.isOk ) {
        // Toggle all status checkboxes of updated items to "Ativo"
        for ( var i = 0; i < result.items.length; i++ ) {
            $( '#onoffswitch-' + result.items[ i ] ).prop( 'checked', false );
        }
    }

    callSuccess( result );
}

function callFail() {
    // Remove old error message if exists
    $( '.err-msg' ).remove();
    $( '.success-msg' ).remove();

    // Show the user a failure message
    $( '<div class="flash err-msg">' +
         'Não foi possível atualizar os itens: contate o suporte.' +
       '</div>' ).insertAfter( '#area-header' );

    // Uncheck all
    $( '#toggle-all' ).attr( 'checked', false );
    $( '.list-item' ).each( function() {
        $( this ).attr( 'checked', false );
    } );
}

function callSuccessRemove( result ) {
    // Redirect user to same URL but in page 1
    var url = window.location.href;
    url = url.replace( /pag:\d/, 'pag:1' );

    window.location.replace( url );
}

function callConsoleAjax( items, baseRoute, method ) {
    if ( ! items.length ) {
        return;
    }

    var successCallback = undefined;

    // Get success callback function in accordance with the method called
    if ( method == 'activate' ) {
        successCallback = callSuccessActivate;
    } else if ( method == 'deactivate' ) {
        successCallback = callSuccessDeactivate;
    } else {
        successCallback = callSuccessRemove;
    }

    try {
        $.ajax( {
                method: 'POST',
                url: window.lsmConf.baseUrl + '/' + baseRoute + '/' + method,
                data: { token: document.getElementById( 'token' ).value, items: items },
                dataType: 'json'
            } )
            .done( successCallback )
            .fail( callFail );
    } catch ( e ) {
        console.log( e );
    }
}

// Toggle functionality (for those forms with a toggle-status button
var toggleSuccess = function toggleSuccess( result ) {
    if ( ! result.isOk ) {
        // Show the user the error message that came back
        // Remove old error message if exists
        $( '.err-msg' ).remove();

        // Show the user a failure message
        $( '<div class="flash err-msg">' +
                result.error +
           '</div>' ).insertAfter( '#area-header' );

        this.checked = ( this.checked ) ? false : true;
    }

    // Update token on the page
    if ( result.token ) {
        document.getElementById( 'token' ).value = result.token;
    }
};

var toggleFail = function toggleFail() {
    // Remove old error message if exists
    $( '.err-msg' ).remove();

    // Show the user a failure message
    $( '<div class="flash err-msg">' +
           'Não foi possível atualizar o status: contate o suporte.' +
       '</div>' ).insertAfter( '#area-header' );

    // Toggle checkbox back
    this.checked = ( this.checked ) ? false : true;
};

function sendToggleStatusRequest( baseRoute ) {
    try {
        $.ajax( {
                method: 'POST',
                url: window.lsmConf.baseUrl + '/' + baseRoute + '/' + this.value + '/toggle-status/',
                data: { token: document.getElementById( 'token' ).value },
                dataType: 'json'
            } )
            .done( toggleSuccess.bind( this ) )
            .fail( toggleFail.bind( this ) );
    } catch ( e ) {
        toggleFail.bind( this );
    }
}
