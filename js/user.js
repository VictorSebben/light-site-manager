function sendUserToggleStatusRequest() {
    var thisUserId = document.getElementById( 'me-myself-and-i' ).value;
    var isToggle = true;

    if ( this.value == thisUserId ) {
        isToggle = confirm( 'Atenção: desativando seu próprio usuário, ' +
            'você não poderá realizar nenhuma ação no sistema. Você realmente deseja desativá-lo?' );
    }

    if ( isToggle ) {
        sendToggleStatusRequest.apply( this, [ 'users' ] );
    } else {
        // Toggle checkbox back
        this.checked = ( this.checked ) ? false : true;
    }
}

var toggleBtns = document.getElementsByClassName( 'onoffswitch-checkbox' );

for ( var i = 0; i < toggleBtns.length; i++ ) {
    toggleBtns[ i ].addEventListener(
        'change',
        sendUserToggleStatusRequest,
        false
    );
}

formConsole.btnRemove.click( formConsole.removeItems.bind( undefined, [ 'users' ] ) );
formConsole.btnActivate.click( formConsole.activateItems.bind( undefined, [ 'users' ] ) );
formConsole.btnDeactivate.click( formConsole.deactivateItems.bind( undefined, [ 'users' ] ) );
