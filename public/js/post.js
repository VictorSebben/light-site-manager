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

// Preview image with colorbox
$( document ).ready( function() {
    $( 'a.preview-colorbox' ).colorbox();
});
