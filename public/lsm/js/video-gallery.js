// TODO FOR AJAX REQUESTS window.lsmConf.baseUrl

function showInsertErrMsg( errMsg ) {
    // Show error message on page for 10 seconds
    var err = $( '#msg' );
    err.html( errMsg );
    err.toggle( 'fade' );

    window.setTimeout( function () {
        err.toggle( 'fade' );
        err.html( '' );
    }, 3000 );
}

function validateData( title, pos, iframe ) {
    var errMsg = '';

    if ( title === '' || iframe === '' ) {
        errMsg += 'Preencha todos os campos (Título, Posição e iframe)!';
    } else if ( pos !== '' && pos != parseInt( pos, 10 ) ) {
        errMsg += 'A posição deve ser um número!';
    }

    if ( errMsg ) {
        showInsertErrMsg( errMsg );
        return false;
    }

    return true;
}

$( '#insert-iframe' ).blur( function ( evt ) {
    var iframe = $( this ).val();
    var preview = $( '#video-preview' );

    // If the field is empty or it is not an iframe tag,
    // just put back the default preview text
    if ( ! iframe
         || iframe.indexOf( '<iframe' ) == -1 ) {
        preview.html( '<span id="insert-preview" class="preview">Preview</span>' );
    }

    // Else, show video preview
    else {
        iframe = $( iframe );
        iframe.attr( 'width', 250 );
        iframe.attr( 'height', 130 );
        preview.html( iframe );
    }
} );

$( '#btn-insert' ).click( function(evt) {
    evt.preventDefault();

    var title = $( '#insert-title' );
    var position = $( '#insert-position' );
    var iframe = $( '#insert-iframe' );

    if ( ! validateData( title[ 0 ].value, position[ 0 ].value, iframe[ 0 ].value ) ) {
        return;
    }

    insertVideo();
} );

function insertVideo() {
    //
    // Send ajax request to insert video
    //
    try {
        $.ajax( {
            method: 'POST',
            url: window.lsmConf.baseUrl + '/posts/' + $( '#post-id' )[ 0 ].value + '/insert-video/',
            data: {
                title: $( '#insert-title' )[ 0 ].value,
                position: $( '#insert-position' )[ 0 ].value,
                iframe: $( '#insert-iframe' )[ 0 ].value
            },
            dataType: 'json'
        } )
        .done( function() {} )
        .fail( function() {} );
    } catch ( e ) {
        // TODO Show error message
    }
}

function updateVideo() {

}
