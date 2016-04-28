/*

Let's assume that, by default, images are uploaded and saved without
any cropping. If the user selects one or a bunch of images, they are
simply stored and resized internally. Only after that, if the user
wants to, they can crop the images.

API:

 - images       →  list and present form to upload one ore more images at once.
 - images-save  →  store one ore more images sent via ajax.
 - image-crop   →  crop a single image.
 - image-destroy →  remove a single image.
 - image-set-position →  set a new position for the image (and return the new position of the other images).

NOTE: resize is done internally based on the layout of every project.


TODO: Perhaps send images in a sync manner instead of async? It will probably
make some things easier and will not put a high load on the server dealing
with images, crops and resizing at the same time.

*/



var lsmImage = (function () {

    var l = console.log.bind(console);

    var $img = $( '#img' );
    var $imageListWrap = $( '#image-list-wrap' );

    // Messages that rise from performing image actions.
    var $imagesMessages = $( '#images-messages' );

    // The number of images the user selects for upload.
    var totalImages;
    // How many images have already been processed for this upload.
    var totalImagesProcessed;

    // For the sortable jquery ui plugin
    var positionInfo = {};

    // Some actions require that we set the current “preview” that is being dealt with.
    var $currentPreview;

    // The crop object so whe can data when the user clicks “Recortar”.
    var cropper;

    $img.on( 'change', function () {
        var i;

        // Both used to update the user on the status of the uploads.
        totalImages = this.files.length;
        totalImagesProcessed = 0;

        // Inits it here, and remove on updateViewImagesRemaining() when the
        // last image is processed.
        lsm.addOverlay();
        updateViewImagesRemaining( 'Iniciando o upload...' );

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
                        sendFile( someImg.file, preview.previewWrap );
                    };
                }( preview.img ));
            }( this.files[ i ] ) );
        };

    });


    // When user clicks on “remover” inside a preview.
    $imageListWrap.on( 'click', '.preview-wrap .remove', function ( evt ) {
        $currentPreview = $( this ).closest( '.preview-wrap' );
        showConfirmRemove( this );
    });

    // Closes `remove` confirmation box upon hitting <Esc>.
    // Closes crop UI view.
    $( document ).on( 'keyup', function ( evt ) {
        // Se for Esc
        if ( evt.keyCode === 27 ) {
            $imageListWrap.find( '.preview-wrap .remove-confirm' ).remove();
            $( '#image-crop-wrap' ).fadeOut( 1000, function () {
                $( this ).remove();
            });
        }
    });

    // Closes `remove` confirmation box upon clicking `Cancelar`.
    $imageListWrap.on( 'click', '.preview-wrap .del-no', function ( evt ) {
        evt.stopPropagation();
        $( this ).closest( '.remove-confirm' ).remove();
    });

    // Actually removes the image when user clicks on “Sim, remover”.
    $imageListWrap.on( 'click', '.preview-wrap .del-yes', function ( evt ) {

        evt.stopPropagation();

        // This one is removed after repositionAfterDestroy() that updates the data-position
        // attributes of the previews after the image has been repositioned on DB.
        lsm.addOverlay();

        $currentPreview = $( this ).closest( '.preview-wrap' );

        var uri = lsmConf.baseUrl + '/' + lsmConf.ctrl + '/' + lsmConf.pk + '/' + 'image-destroy';
        var data = getPreviewData( $currentPreview );

        jQuery.ajax({
            type: 'POST',
            url: uri,
            data: data,
            success: function ( response ) {
                response = JSON.parse( response );
                if ( response[ 'status' ] === 'success' ) {
                    repositionAfterDestroy( $currentPreview.attr( 'data-position' ) );
                    $currentPreview.remove();
                    addMessage( '<div>Imagem removida</div>' );
                }
                setTimeout( function () {
                    $imagesMessages.html( '' );
                }, 10000);
            }
        });
    });


    // Opens crop UI.
    $imageListWrap.on( 'click', '.preview-wrap .crop', function ( evt ) {

        $currentPreview = $( this ).closest( '.preview-wrap' );

        // Adds the crop view to the page and returns a cropable object from cropperjs.
        insertCropper({
            imagePath: getPreviewData( $currentPreview )['imagePath']
        });

        l(getPreviewData($currentPreview));

    });


    // Sends ajax with crop data.
    $( document.body ).on( 'click', '#btn-crop-perform', function ( evt ) {

        var cropData = cropper.getData( true );
        var previewData = getPreviewData( $currentPreview );

        // .../lsm/posts/NN/image-crop
        var uri = lsmConf.baseUrl + '/' + lsmConf.ctrl + '/' + lsmConf.pk + '/' + 'image-crop';

        jQuery.ajax({
            method: 'POST',
            url: uri,
            data: {
                post_id: previewData.post_id,
                image_id: previewData.image_id,
                extension: previewData.extension,
                crop_x: cropData.x,
                crop_y: cropData.y,
                crop_w: cropData.width,
                crop_h: cropData.height
            },
            success: function ( response ) {

                // Removes the crop UI.
                $( '#image-crop-wrap' ).remove();

                // date.getTime() is to avoid cache.
                var imgpath = lsmConf.baseUrl + '/../uploads/images/' + previewData.post_id + '-'
                            + previewData.image_id + '-thumb.' + previewData.extension + '?' + (new Date()).getTime();

                l(imgpath);

                // Places the cropped image in the preview.
                $currentPreview.find( 'img' ).attr( 'src', imgpath );
            }
        });
    });


    // Cancel crop and do nothing else.
    $( document.body ).on( 'click', '#btn-crop-cancel', function ( evt ) {
        $( this ).closest( '#image-crop-wrap' ).fadeOut( 1000, function () {
            $( this ).remove();
        });
    });


    // Invoke sortable to make items sortable ☺
    $imageListWrap.sortable({

        items: '.preview-wrap', // Make the preview boxes draggable, but...
        handle: '.position',    // ...only drag if click happens on this child of the preview box.

        // When dragging starts.
        start: function (evt, ui) {
            positionInfo.oldpos = parseInt(ui.item.attr('data-position'), 10);
        },

        // When dragging ends.
        update: function ( evt, ui ) {

            // Inits it here and removes it on the repositionPreviewAttributes() after
            // the repositionDb() has been performed.
            lsm.addOverlay();

            // index() + 1 because index is zero-based, but our DB thing starts with 1, not 0.
            positionInfo.newpos = ui.item.index() + 1;

            positionInfo.image_id = ui.item.attr( 'data-id' );

            positionInfo.$draggedItem = ui.item;

            // Gets needed values from positionInfo object.
            repositionDb();
        }

    }).disableSelection(); // Prevents selecting text accidentaly.


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

        formData.append( 'image', file );

        jQuery.ajax({
            type: 'POST',
            url: uri,
            processData: false, // Don't try to process data.
            contentType: false, // Don't try to be smart about content-type.
            data: formData,
            success: function ( response ) {
                addDataToUploadedPreviews( JSON.parse( response ), previewWrap );

                totalImagesProcessed += 1;
                updateViewImagesRemaining();
            }
        });
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

        $(previewWrap).attr({
            'data-id': jsonData.id,
            'data-position': jsonData.position,
            'data-extension': jsonData.extension
        });

        // imageListWrap is the container for the list of previews. We need to
        // append the previews to that list based on the order of the
        // `position` column on DB given to each image.
        $imageListWrap.append( previewWrap );

        // Show the preview at this point.
        previewWrap.style.display = 'block';

        // Causes the newly added previews to respond to drag/sort actions.
        $imageListWrap.sortable( 'refresh' );
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

        var previewWrap = tmp.children[0];

        // Each preview is only shown after its image has been properly dealt with on the back-end.
        previewWrap.style.display = 'none';
        var img = previewWrap.getElementsByTagName('img')[0];
        img.file = currentFile;

        return {
            img: img,
            previewWrap: previewWrap
        };
    }


    /**
     * @ajax. Reposition images on DB.
     *
     * @return {json} response json.true or json.false.
     */
    function repositionDb() {

        // The post_id goes in the request url so we can keep our “routing protocol”.
        var uri = lsmConf.baseUrl + '/' + lsmConf.ctrl + '/' + lsmConf.pk + '/' + 'image-set-position';

        var data = {};

        data.image_id = positionInfo.image_id;
        data.oldpos = positionInfo.oldpos;
        data.newpos = positionInfo.newpos;

        jQuery.ajax({
            type: 'POST',
            url: uri,
            data: data,
            success: function ( response ) {
                response = JSON.parse( response );
                if ( response[ 'status' ] === 'success' ) {
                    repositionPreviewAttributes();
                    addMessage('Imagem reposicionada');
                }
                else {
                    // If there was a problem repositioning images on DB, undo the
                    // repositioning on the view as well.
                    $imageListWrap.sortable( 'cancel' );
                }
            }
        });
    }


    /**
     * Called only after the reposition in DB has proven successfull.
     *
     * Gets params from an object scoped inside this “module”.
     */
    function repositionPreviewAttributes() {

        // Sets the dragged item `data-positin` attribute to the position it
        // was dragged to in the list of previews.
        positionInfo.$draggedItem.attr( 'data-position', positionInfo.newpos );

        $imageListWrap.children( '.preview-wrap' ).each( function () {

            // The position of the dragged item inside the list of previews (+ 1 because
            // the list is zero-based, but we used 1-based positions on DB.
            var idx = $( this ).index() + 1;

            // `this` is the current element of the iteration. Let's grab its value so
            // we can more easily increment or decrement it according to what we need
            // to do for each situation.
            var pos = Number( $( this ).attr( 'data-position' ) );

            // Move the increment or decrement data-position of each preview
            // in the list accordingly.

            if ( positionInfo.newpos < positionInfo.oldpos ) {
                if ( idx > positionInfo.newpos && idx <= positionInfo.oldpos ) {
                    this.setAttribute('data-position', pos + 1);
                }
            }
            else if ( positionInfo.newpos > positionInfo.oldpos ) {
                if ( idx >= positionInfo.oldpos && idx < positionInfo.newpos ) {
                    this.setAttribute('data-position', pos - 1);
                }
            }

            setTimeout(function () {
                // This overlay was initialized on the sortable initializer on its `update` method.
                lsm.removeOverlay();
            }, 1000);
        });
    }


    /**
     * Shows the status of the upload to the user.
     *
     * If `msg` is passed, it is because this function was called on the `change`
     * event when the user selects the image. In this case, shows a spinner and some
     * initial text. On the other times we show a new message saying how many images
     * have already been processed.
     */
    function updateViewImagesRemaining( msg ) {


        // Shows that the process has begun and displays the spinner.
        if ( msg ) {
            // If we don't yet have the spinner there.
            if ( $imagesMessages.has( 'img' ).length === 0 ) {
                var img = document.createElement( 'img' );
                $imagesMessages.append( "<img class='icon'>" );
                $imagesMessages.append( "<span class='text'></span>" );
            }

            // In one way or another we now have the Nodes to add the spinner and the text.
            $imagesMessages.find( '.icon' ).attr( 'src', 'img/icons/ajax-loader.gif' );
            $imagesMessages.find( '.text' ).text( msg );

            return;
        }

        // At this point the spinner is visible and we update the status about
        // the number of images already processed.

        msg = totalImagesProcessed + ' de ' + totalImages + ' imagens processadas';

        // If all images have been processed, change the icon and change the text a little bit.
        if ( totalImagesProcessed === totalImages ) {
            $imagesMessages.find( 'img' ).attr( 'src', 'img/icons/checked-ok.svg' );
            $imagesMessages.find( '.text' ).text( msg + ' (concluído)' );

            // Now, wait for some time and remove the message.
            setTimeout( function () {
                $imagesMessages.html( '' );
            }, 15000);

            // Called on change of $img.
            lsm.removeOverlay();
        }
        // If there are more images to be processed, just update the processed count.
        else {
            $imagesMessages.find( '.text' ).text( msg );
        }
    }


    /**
     * When the user clicks on `remover`, first show a confirmation message.
     *
     * @param {DOMElement} elem - the element that was clicked. We add the confirmation box
     * inside the `elem` so the box always pops up near the mouse pointer.
     */
    function showConfirmRemove( elem ) {
        var html = "\
            <div class='remove-confirm'>\
                <div class='txt'>Tem certeza?</div>\
                <div class='btns'>\
                    <span class='del-no'>Cancelar</span>\
                    <span class='del-yes'>Sim, remover!</span>\
                </div>\
            </div>";

        // Removes any existing boxes (even if it is from other previews ).
        $imageListWrap.find( '.preview-wrap .remove-confirm' ).remove();

        // Adds this one
        $( elem ).append( html );
    }


    /**
     * Retrieves relevant preview data from a $preview.
     *
     * @param {jQueryObject} $preview - o container/preview as a jQuery object.
     * @return {object}
     */
    function getPreviewData( $preview ) {
        var data = {
            image_id: $preview.attr( 'data-id' ),
            post_id: lsmConf.pk,
            position: $preview.attr( 'data-position' ),
            extension: $preview.attr( 'data-extension' )
        };

        // We need other info before we can compose the image path.
        data.imagePath = '../uploads/images/' + data.post_id + '-' + data.image_id + '-orig.' + data.extension;

        return data;
    }


    /**
     * After destroying an image, update the preview's `data-position` attribute accordingly.
     *
     * @param {Integer} pos - the value of `data-position` of the removed image.
     */
    function repositionAfterDestroy( posOfRemovedOne ) {

        $imageListWrap.children( '.preview-wrap' ).each( function () {

            // Index of “this” element in the list of previews.
            var idx = $(this).index() + 1;

            // If “this” preview comes after of the one destroyed, decrements
            // its `data-position` by 1.
            if (idx > posOfRemovedOne) {
                var pos = Number(this.getAttribute( 'data-position' ) );
                this.setAttribute( 'data-position', pos - 1 );
            }

            // It was added on the confirmation to delete an image.
            lsm.removeOverlay();
        });
    }


    /**
     * Insert the cropper with image and shows to the user.
     * @param {object} opts - Options to use when creating/inserting the cropper
     *
     *  { imagePath: '../uploads/produtos/130-28-orig.jpg', foo: 'foo', etc... };
     */
    function insertCropper( opts ) {
        var html = "\
            <div id='image-crop-wrap' class='image-crop-wrap'>\
                <div class='buttons'>\
                    <input type='button' id='btn-crop-perform' value='Recortar'>\
                    <input type='button' id='btn-crop-cancel' value='Cancelar'>\
                </div>\
                <div class='table'>\
                    <div class='td'>\
                        <img id='crop-me' alt='image for cropping'>\
                    </div>\
                </div>\
            </div>";

        jQuery( 'body' ).append( html );

        var $imageCropWrap = $( '#image-crop-wrap' );

        $imageCropWrap.find( '#crop-me' ).attr( 'src', opts.imagePath );
        $imageCropWrap.find( '#crop-me' ).css({
            'max-height': $imageCropWrap.height()
        });

        $imageCropWrap.css({
            'display': 'block'
        });

        var cropMe = document.querySelector('#crop-me');
        cropper = new Cropper(cropMe, {
            viewMode: 1, // Limit crop inside the image boundaries.
            aspectRatio: 180 / 120 // Let's base it on the thumbnail size.
        });

        jQuery( 'body' ).animate( { scrollTop: 0 }, 300 );
    }


    /**
     * Adds a message to “space” to the right of the upload button./
     */
    function addMessage( msg ) {
        $imagesMessages.html( msg );
        setTimeout(function () {
            $imagesMessages.html( '' );
        }, 10000);
    }
}());;
