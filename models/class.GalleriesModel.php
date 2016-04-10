<?php

class GalleriesModel extends BaseModel {

    public $id;
    public $post_id;
    public $caption;
    public $position;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();
    }

}

