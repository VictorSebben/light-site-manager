/*

Let's assume that, by default, images are uploaded and saved without
any cropping. If the user selects one or a bunch of images, they are
simply stored and resized internally. Only after that, if the user
wants to, they can crop the images.

API:

 - images       →  list and present form to upload one ore more images at once.
 - images-save  →  store one ore more images sent via ajax.
 - image-crop   →  crop a single image.
 - image-remove →  remove a single image.
 - image-position →  set a new position for the image (and return the new position of the other images).

NOTE: resize is done internally based on the layout of every project.


TODO: Perhaps send images in a sync manner instead of async? It will probably
make some things easier and will not put a high load on the server dealing
with images, crops and resizing at the same time.

*/



var lsmImage = (function () {

    var l = console.log.bind(console);

    img = document.querySelector( '#img' );
    imageListWrap = document.querySelector( '#image-list-wrap' );

    img.addEventListener( 'change', function () {
        var i;

        for ( i = 0 ; i < this.files.length ; ++i ) {

            // Since we are doing async stuff, we need to “lock” the scope for each image.
            (function (curFile) {

                var preview = createPreview(curFile);

                // Insert additional images after the existing ones.
                //imgPreviewWrapper.appendChild( preview.wrap );

                reader = new FileReader();
                reader.readAsDataURL( curFile );
                reader.onload = (function ( someImg ) {
                    return function ( evt ) {
                        someImg.src = evt.target.result;
                        sendFile( someImg.file, preview.wrap );
                    };
                }( preview.img ));
            }( this.files[ i ] ) );
        };

    }, false);


    //$(imageL


    /**
     * Send the file through ajax.
     *
     * @param {File} file.
     * @param {Node} previewWrap - the image's preview "box". It is passed to this function
     * only because it needs to be passed to yet another function called here.
     */
    function sendFile( file, previewWrap ) {

        var uri = lsmConf.baseUrl + '/' + lsmConf.ctrl + '/' + lsmConf.pk + '/' + 'images-save';
        var xhr = new XMLHttpRequest();
        var formData = new FormData();

        xhr.open( 'POST', uri, true );
            xhr.onreadystatechange = function () {
                if ( xhr.readyState === 4 && xhr.status === 200 ) {
                    // In this case, the response is a json object.
                    addDataToUploadedPreviews( JSON.parse( xhr.responseText ), previewWrap );
                }
            };

        formData.append( 'image', file );
        xhr.send( formData );
    }

    /**
     * Add id and position to each of the preview images that were uploaded.
     *
     * @param {json} jsonData - something like {"id":57,"position":9}, where id is the id
     * that the database generated for the image, and position is the position set for that image.
     *
     * @param {Node} preview - o preview box daquela imagem.
     */
    function addDataToUploadedPreviews( jsonData,  previewWrap ) {
        previewWrap.setAttribute( 'data-id', jsonData.id );
        previewWrap.setAttribute( 'data-position', jsonData.position );
        previewWrap.setAttribute( 'data-extension', jsonData.extension );

        // imageListWrap is the container for the list of previews. We need to
        // append the previews to that list based on the order of the
        // `position` column on DB given to each image.
        imageListWrap.appendChild( previewWrap );

        // Show the preview at this point.
        previewWrap.style.display = 'block';
    }

    /**
     * Creates the image preview with action buttons and img tag. It adds a <img> tag
     * and some other neccessary tags around it and another piece of code “readAsDataURL”
     * and pushes the image as a base64 string into each img.src attribute so that the image
     * actually shows up as a preview.
     *
     * @param {File} file — the image file to be previewed.
     * @return {object} — an object with the img tag and the preview box as a DOM object.
     */
    function createPreview(currentFile) {
        var template = "\
            <div class='preview-wrap'>\
                <div class='btn-action position'>posicionar</div>\
                <div class='tbl'>\
                    <div class='tblcell'>\
                        <img class='preview'>\
                    </div>\
                </div>\
                <div class='actions cf'>\
                    <div class='btn-action remove'>remover</div>\
                    <div class='btn-action crop'>recortar</div>\
                </div>\
            </div>";

        var tmp = document.createElement('div');
        // Indentation and space between inline span elements do matter and may
        // break the layout in some situations.
        tmp.innerHTML = template.replace(/ +/, '');

        var wrap = tmp.children[0];

        // Each preview is only shown after its image has been properly dealt with on the back-end.
        wrap.style.display = 'none';
        var img = wrap.getElementsByTagName('img')[0];
        img.file = currentFile;

        return {
            img: img,
            wrap: wrap
        };
    }

}());;
