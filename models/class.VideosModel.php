<?php

namespace lsm\models;

use WideImage\Exception\Exception;

class VideosModel extends BaseModel {

    public $id;
    public $post_id;
    public $url;
    public $video_id;
    public $video_provider;
    public $title;
    public $position;

    /**
     * VideoGalleryModel constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->tableName = 'videos';
    }

    public function getVideoIframe($width, $height) {
        if ( ! $this->video_id || ! $this->video_provider ) {
            return '<span id="preview" class="preview">Preview</span>';
        }

        if ( $this->video_provider === 'vimeo' ) {
            return "<iframe src=\"https://player.vimeo.com/video/{$this->video_id}\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>";
        }
        else if ( $this->video_provider === 'youtube' ) {
            return "<iframe width=\"{$width}\" height=\"$height\" src=\"https://www.youtube.com/embed/{$this->video_id}\" frameborder=\"0\" allowfullscreen></iframe>";
        }
    }

    /**
     * This method breaks the URL typed by the user and gets the
     * video ID and provider (for now, we'll work with youtube and vimeo).
     * This is necessary because the URL the user will type in (the browser
     * URL, which is easier for the user) is not the same URL required for
     * the iframe. But from the browser URL we can get both the id and the
     * provider of the video, so it is better to make the user type it instead
     * of having two fields for the two pieces of information.
     *
     * @param $url
     */
    public function setUrlProviderFromUrl( $url ) {
        if ( preg_match( '/vimeo/', $url ) ) {
            $this->video_provider = 'vimeo';

            $videoId = explode( '/', $url );
            end( $videoId );
            $this->video_id = current( $videoId );
        } else if ( preg_match( '/youtube/', $url ) ) {
            $this->video_provider = 'youtube';

            $videoId = explode( '=', $url );
            $this->video_id = $videoId[ 1 ];
        }

        if ( ! $this->video_provider || ! $this->video_id ) {
            throw new Exception( 'A URL deve ser um endereço válido de um vídeo (Youtube ou Vimeo)!' );
        }
    }
}
