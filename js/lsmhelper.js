// Light Site Manager JS Helper Library
$( '#go-back' ).click( function () {
    window.history.go( -1 );
    return false;
} );

var lsm = (function () {


    function addOverlay() {
        var img = lsmConf.baseUrl + '/img/icons/spinner1.gif';
        $( document.body ).append( "<div id='overlay'><img src='" + img + "'</div>" );
    }


    function removeOverlay() {
        $( '#overlay' ).fadeOut( 800, function () {
            $( this ).remove();
        });
    }


    /**
     * Call any of the functions as `lsm.addOverlay()` or `lsm.removeOverlay()`
     * from anywhere in the lsm project.
     */
    return {
        addOverlay: addOverlay,
        removeOverlay: removeOverlay
    }

}());

