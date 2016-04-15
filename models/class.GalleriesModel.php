<?php

class GalleriesModel extends BaseModel {

    public $id;
    public $post_id;
    public $caption;
    public $position;
    public $extension;
    public $created_at;
    public $updated_at;

    public function __construct() {
        parent::__construct();
    }

    public function path() {
        $baseUrl = ( new Base() )->getConfig()[ 'base_url' ];
        return "{$baseUrl}/../uploads/galleries/{$this->post_id}-{$this->id}-thumb.{$this->extension}?" . time();
    }
}

