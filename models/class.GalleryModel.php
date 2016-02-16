<?php

class GalleryModel extends BaseModel {

    public function __construct() {
        parent::__construct();

        $this->tableName = 'galleries';
    }
}
