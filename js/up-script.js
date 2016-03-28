$( '#img' ).change( function() {
    if ( validateImg() ) {
        saveImg( this.files[ 0 ], displayImg, showError );
    } else {
        // TODO SHOW ERROR
    }
} );

function validateImg() {
    // TODO CHECK EXTENSION TO SEE IF IT IS IMAGE
    return true;
}

function displayImg() {

}

function showError() {

}

/**
 * Load image for cropping
 * @param file
 */
function saveImg( file, success, fail ) {
    showLoading();

    var imgTag = document.createElement( 'img' );
    imgTag.file = file;
    var reader = new FileReader;
    reader.readAsDataURL( file );

    if ( window.FormData ) {
        var formData = new FormData();
        formData.append( 'img', file );
    }

    // Send image through ajax
    if ( formData ) {
        $.ajax( {
            url: window.location.origin + '/light-site-manager/public/posts/saveImg',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function( res ) {
                // Remove loading message and icon
                hideLoading();

                try {
                    var div = document.getElementById( 'img-div' );
                    if ( document.getElementById( 'crop' ) != null ) {
                        div.removeChild( document.getElementById( 'crop' ) );
                    }
                } catch ( e ) {}

                // Get answer from server
                var arr = $.parseJSON( res );

                // Put data into the hidden fields
                document.getElementById( 'tmpname' ).setAttribute( 'value', arr[ 'tmpname' ] );
                document.getElementById( 'extension' ).setAttribute( 'value', arr[ 'ext' ] );

                // Hide field to choose image, because changing the image will cause trouble
                $( '#sel-img' ).hide( 'fast' );
                $( '#new-img' ).show( 'fast' );

                // Puts a new image, in case the user has given up the last one...
                var imgTag = document.createElement( 'img' );
                imgTag.setAttribute( 'src', arr[ 'tmpdir' ] + '/' + arr[ 'tmpname' ] + '-tmpcrop.'
                                     + arr[ 'ext' ] + '?' + ( new Date() ).getTime() );
                imgTag.setAttribute( 'id', 'crop' );
                document.getElementById( 'img-div' ).appendChild( imgTag );

                // Call function that will take the cropping data and send the ajax request
                crop();
            }
        } );
    }
}

/**
 * Add spinner icon while loading image,
 * so that the user will not think the page is stuck
 */
function showLoading() {
    var spinIcon = document.createElement( 'span' );
    spinIcon.className = "fa fa-spinner fa-spin";
    var loading = document.getElementById( 'loading' )
    loading.appendChild( document.createTextNode( 'Carregando ' ) );
    loading.appendChild( spinIcon );
}

function hideLoading() {
    var loading = document.getElementById( 'loading' );
    while ( loading.firstChild ) {
        loading.removeChild( loading.firstChild );
    }
}

/**
 * Checks the minimum width and height of the selection
 * before showing the button to Crop the image.
 * Takes the information about the selection and
 * sends it via ajax for PHP to crop and create
 * the 4 versions of the image
 */
function crop() {
    var selection = $( '#crop' ).imgAreaSelect( {
        handles: true,
        instance: true,
        maxWidth: $( '#w' ).val(),
        maxHeight: $( '#h' ).val(),
        // Check if the selected area has the correct size to show
        // the Crop button
        onSelectEnd: function() {
            toggleCropBtn( selection.getSelection(), this.maxWidth, this.maxHeight );
        }
    } );

    function toggleCropBtn() {

    }
}
