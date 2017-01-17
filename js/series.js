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

formConsole.btnRemove.click( formConsole.removeItems.bind( undefined, [ 'series' ] ) );
formConsole.btnActivate.click( formConsole.activateItems.bind( undefined, [ 'series' ] ) );
formConsole.btnDeactivate.click( formConsole.deactivateItems.bind( undefined, [ 'series' ] ) );
