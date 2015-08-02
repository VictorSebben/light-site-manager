var btnConfig = document.getElementById( 'btn-open-config' );
btnConfig.addEventListener( 'click', toggleDivConfig, false );

function toggleDivConfig () {
    var navConfig = document.getElementById( 'nav-config' );

    if ( navConfig.style[ 'display' ] == 'block' ) {
        navConfig.style[ 'display' ] = 'none';
    }
    else {
        navConfig.style[ 'display' ] = 'block';
    }
}
