<?php

class ImgH {
    private $_dir;

    public function __construct() {
        $this->_dir = ROOT_DIR . '/public/_tmp';
    }

    /**
     * @throws Exception
     */
    public function saveImg() {
        /**
         * If the image is too small, Medium and Big sizes
         * will be smaller than what specified in the DB or class
         */

        $fileName = $_FILES[ 'img' ][ 'name' ];
        $ext = pathinfo( $fileName )[ 'extension' ];

        // Let us make sure that different users will not disturb each other
        $tmp = $_SESSION[ 'user' ] . '-tmp';
        $tmpdir = '_' . $_SESSION[ 'user' ];

        $this->_rmTmpDir( "{$this->_dir}/{$tmpdir}" );
        $this->_mkTmpDir( "{$this->_dir}/{$tmpdir}" );

        if ( ! move_uploaded_file( $_FILES[ 'img' ][ 'tmp_name' ], "{$this->_dir}/{$tmpdir}/{$tmp}-orig.{$ext}" ) ) {
            throw new Exception( 'Erro ao fazer upload de arquivo!' );
        }

        // Instantiate the upload class
        $upload = new upload( "{$this->_dir}/{$tmpdir}/{$tmp}-orig.{$ext}" );
        if ( ! $upload->uploaded ) {
            throw new Exception( 'Erro ao fazer upload de arquivo!' );
        }

        // If the image is larger than 1000px x 800px, resize it on the larger side,
        // keeping the ration. The purpose of this is to
        $upload->image_resize = true;
        $upload->image_ratio_no_zoom_in = true;
        $upload->image_x = 1000;
        $upload->image_y = 800;
        $upload->file_new_name_body = "{$tmp}-tmpcrop";

        $upload->process( "{$this->_dir}/{$tmpdir}/" );

        if ( ! $upload->processed ) {
            if ( DEBUG ) {
                throw new Exception( $upload->error );
            } else {
                throw new Exception( 'Erro ao realizar upload de arquivo!' );
            }
        }

        $url = new Url();

        $arrImgData = [
            'tmpdir' => $url->make( "_tmp/{$tmpdir}" ),
            'tmpname' => $tmp,
            'ext' => $ext
        ];

        echo json_encode( $arrImgData );
        exit();

        /**
         * From this moment on, the JavaScript inserts the image on the page and
         * calls the functionality for cropping it. After the image is cropped,
         * the information is sent back to the PHP via Ajax.
         */
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
        if ( ! mkdir( $path, 0775, true ) ) {
            throw new Exception( 'Erro ao criar diretório temporário para upload de arquivos!' );
        }
    }
}
