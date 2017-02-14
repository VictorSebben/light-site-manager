var positionDiv = $( '#position-div' );
var series = $( '#series' );

if ( series.val() ) {
    positionDiv.show();
}

series.change( function() {
    if ( $( this ).val() ) {
        positionDiv.show();
    } else {
        positionDiv.hide();
    }
} );