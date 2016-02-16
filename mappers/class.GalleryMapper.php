<?php

class GalleryMapper extends Mapper {

    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, post_id, image FROM galleries WHERE id = ?"
        );
    }

    public function destroy( GalleryModel $model ) {
        // TODO Erase image files

        // Erase from Database
        parent::destroy( $model );
    }
}
