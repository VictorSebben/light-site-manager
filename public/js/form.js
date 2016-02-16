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
// TODO REFACTOR THIS SHOULD BE A list.js, NOT form.js
var formConsole = {
    btnActivate: document.getElementById( 'btn-activate' ),
    btnDeactivate: document.getElementById( 'btn-deactivate' ),
    btnRemove: document.getElementById( 'btn-delete' ),

    // TODO METHODS/FUNCTIONALITY OF THE CONSOLE
    removeItems: function removeItems() {
        console.log( 'Removing items' );
    }
};

window.onload = function () {
    formConsole.btnRemove.addEventListener( 'click', formConsole.removeItems, false );
};

// Define base URL for Ajax requests
var baseUrl = window.location.origin;

// Toggle functionality (for those forms with a toggle-status button
var toggleSuccess = function toggleSuccess( result ) {
    if ( ! result.isOk ) {
        // Show the user the error message that came back

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
                url: baseUrl + '/' + baseRoute + '/' + this.value + '/toggle-status/',
                data: { token: document.getElementById( 'token' ).value },
                dataType: 'json'
            } )
            .done( toggleSuccess )
            .fail( toggleFail.bind( this ) );
    } catch ( e ) {
        toggleFail.bind( this );
    }
}
