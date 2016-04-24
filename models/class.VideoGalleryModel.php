<?php

class VideoGalleryModel extends BaseModel {

    public $id;
    public $post_id;
    public $iframe;
    public $title;
    public $position;

    /**
     * VideoGalleryModel constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->tableName = 'video_galleries';
    }

    public function getVideoIframe() {
        if ( ! $this->iframe ) {
            return '<span id="preview" class="preview">Preview</span>';
        }

        $iframe = htmlspecialchars_decode( $this->iframe );
        $iframe = preg_replace( "/'/", '"', $iframe );
        $iframe = preg_replace( '/width="\d+"/', 'width="200"', $iframe );
        $iframe = preg_replace( '/height="\d+"/', 'height="100"', $iframe );
        return htmlspecialchars_decode( $iframe );
    }
}
