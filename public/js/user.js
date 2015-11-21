var baseUrl = window.location.origin + '/light-site-manager/public/';

var toggleSuccess = function toggleSuccess( result ) {
    l( result );

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
         'Não foi possível atualizar o status do usuário: contate o suporte.' +
       '</div>' ).insertAfter( '#area-header' );

    // Toggle checkbox back
    this.checked = ( this.checked ) ? false : true;
};

function sendToggleStatusRequest() {
    try {
        $.ajax( {
                method: 'POST',
                url: baseUrl + 'users/' + this.value + '/toggle-status/',
                data: { token: document.getElementById( 'token' ).value },
                dataType: 'json'
            } )
            .done( toggleSuccess )
            .fail( toggleFail.bind( this ) );
    } catch ( e ) {
        toggleFail.bind( this );
    }
}

var l = console.log.bind( console );

var toggleBtns = document.getElementsByClassName( 'onoffswitch-checkbox' );

for ( var i = 0; i < toggleBtns.length; i++ ) {
    toggleBtns[ i ].addEventListener(
        'change',
        sendToggleStatusRequest,
        false
    );
}
