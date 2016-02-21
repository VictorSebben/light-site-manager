var toggleBtns = document.getElementsByClassName( 'onoffswitch-checkbox' );

for ( var i = 0; i < toggleBtns.length; i++ ) {
    toggleBtns[ i ].addEventListener(
        'change',
        function () {
            sendToggleStatusRequest.apply( this, [ 'posts' ] );
        },
        false
    );
}

formConsole.btnRemove.click( formConsole.removeItems.bind( undefined, [ 'posts' ] ) );
formConsole.btnActivate.click( formConsole.activateItems.bind( undefined, [ 'posts' ] ) );
formConsole.btnDeactivate.click( formConsole.deactivateItems.bind( undefined, [ 'posts' ] ) );

// Preview image with colorbox
$( document ).ready( function() {
    $( 'a.preview-colorbox' ).colorbox();
});
