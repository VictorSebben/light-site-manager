<?php

class VideoGalleryModel extends BaseModel {

    public $id;
    public $post_id;
    public $video_iframe;
    public $title;
    public $position;

    /**
     * VideoGalleryModel constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->tableName = 'video_galleries';
    }
}
