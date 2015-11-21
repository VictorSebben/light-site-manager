var toggleAll = document.getElementById( 'toggle-all' );
toggleAll.addEventListener( 'click', function () {
    var items = document.getElementsByClassName( 'list-item' );
    var checked = toggleAll.checked;

    for ( var i = 0; i < items.length; i++ ) {
        items[ i ].checked = checked;
    }
}, false );
