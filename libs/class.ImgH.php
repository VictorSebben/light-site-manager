<?php

namespace lsm\libs;

use lsm\models\ImagesModel;
use Exception;
use WideImage\WideImage;

class ImgH {
    private $_dir;

    public function __construct() {
        $this->_dir = ROOT_DIR . '/../uploads/images';
    }

    /**
     * @throws Exception
     */
    public function save($file, $post_id, $image_id) {

        /**
         * If the image is too small, Medium and Big sizes
         * will be smaller than what specified in the DB or class
         */

        $fileName = $file[ 'name' ];
        $ext = pathinfo( $fileName )[ 'extension' ];

        $prefixName = "{$post_id}-{$image_id}";

        $this->_checkDir( $this->_dir );

        // Persist on DB and retrieve generated PK.

        if ( ! move_uploaded_file( $_FILES[ 'image' ][ 'tmp_name' ], "{$this->_dir}/{$prefixName}-orig.{$ext}" ) ) {
            throw new Exception( 'Erro ao fazer upload de arquivo!' );
        }

        // Get WideImage to crop images
        require_once ROOT_DIR . '/vendor/smottt/wideimage/lib/WideImage/WideImage.php';

        // Instantiate WideImage with original picture
        $wi_orig = WideImage::load( "{$this->_dir}/{$prefixName}-orig.{$ext}" );

        $wi_large = $wi_orig->resize( 900, 600 );
        $wi_large->saveToFile( "{$this->_dir}/{$prefixName}-large.{$ext}" );

        $wi_mid = $wi_orig->resize( 720, 480 );
        $wi_mid->saveToFile( "{$this->_dir}/{$prefixName}-mid.{$ext}" );

        $wi_thumb = $wi_large->resize( 180, 120 );
        $wi_thumb->saveToFile( "{$this->_dir}/{$prefixName}-thumb.{$ext}" );
    }


    public function crop( $post_id, $image_id, $extension, $x, $y, $w, $h ) {

        require_once ROOT_DIR . '/vendor/smottt/wideimage/lib/WideImage/WideImage.php';

        $base = "{$post_id}-{$image_id}";
        $origPathName = "{$this->_dir}/{$base}-orig.{$extension}";

        $wi_orig = WideImage::load( $origPathName );

        // The large image will have proportions as defined in the crop selection,
        // and the crop selection itself has a proper aspect ratio set.
        $wi_large = $wi_orig->crop($x, $y, $w, $h);
        $wi_large->saveToFile( "{$this->_dir}/{$base}-large.{$extension}" );

        $wi_mid = $wi_large->resize( 720, 480 );
        $wi_mid->saveToFile( "{$this->_dir}/{$base}-mid.{$extension}" );

        $wi_thumb = $wi_large->resize( 180, 120 );
        $wi_thumb->saveToFile( "{$this->_dir}/{$base}-thumb.{$extension}" );

        // TODO: WideImage->saveToFile() doesn't return anything... How to check if operation succeeded.
    }


    /**
     * Removes all versions/sizes of a image based on its ID and Post_ID.
     *
     * @param ImagesModel $image
     */
    public function destroy( $image ) {

        $base = "{$image->post_id}-{$image->id}";

        // We check if the original image exists, and from that, we removed all variations of the image.
        if ( file_exists( "{$this->_dir}/{$base}-orig.{$image->extension}" ) ) {
            // Removes orig, large and thumb, etc. (post_id-image_id-<*anything*>.extension)
            array_map( 'unlink', glob( "{$this->_dir}/{$base}-*.{$image->extension}" ) );
        }
    }

    /**
     * Checks whether /site/uploads/galleries/ (or other dir) exists. If it does not, create it.
     *
     * NOTE: What if there is an error and we create a new dir overriding all
     * images that could possibily be there already?
     *
     * @param string $path
     * @return boolean
     */
    protected function _checkDir( $path ) {
        // If exists and is writeable, we are fine.
        if ( file_exists( $path ) && is_writeable ( $path ) ) return true;
        return mkdir( $path, 0775, true );
    }

    /**
     * Removes the temporary directory for processing images
     *
     * @param $path
     * @return boolean
     */
    protected function _rmTmpDir( $path ) {
        $isOk = true;

        if ( file_exists( $path ) ) {
            foreach ( glob( "{$path}/*" ) as $file ) {
                if ( ! unlink ( $file ) ) {
                    $isOk = false;
                }
            }

            if ( !rmdir( $path ) ) {
                $isOk = false;
            }
        }

        return $isOk;
    }

    protected function _mkTmpDir( $path ) {
        echo $path;
        if ( ! mkdir( $path, 0775, true ) ) {
            throw new Exception( 'Erro ao criar diretório temporário para upload de arquivos!' );
        }
    }
}
