<?php

namespace lsm\models;

use lsm\libs\Validator;
use lsm\mappers\PostsMapper;
use lsm\libs\H;

class PostsModel extends BaseModel {

    public $id;
    public $user_id;
    public $title;
    public $intro;
    public $post_text;
    public $image;
    public $image_caption;
    public $status;
    public $series_id;

    /**
     * @var UsersModel
     */
    public $user;

    /**
     * Array to hold CategoriesModel objects.
     * @var array
     */
    public $categories = array();

    /**
     * @var SeriesModel
     */
    public $series;

    /**
     * Position of the post in the series
     * @var int
     */
    public $position;

    /**
     * Array of gallery images associated with the post.
     * @var array
     */
    public $images = array();

    public $tableName;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public static $statusString = [
        self::STATUS_ACTIVE => 'Publicado',
        self::STATUS_INACTIVE => 'Despublicado'
    ];

    public function __construct() {
        parent::__construct();

        $this->rules = array(
            'cat' => array( 'fieldName' => 'categorias', 'rules' => 'required', 'array' => true ),
            'title' => array( 'fieldName' => 'título', 'rules' => 'required|max:200|min:3' ),
            'intro' => array( 'fieldName' => 'chamada', 'rules' => 'required|max:200|min:3' ),
            'image' => array( 'fieldName' => 'imagem', 'rules' => 'max:80' ),
            'image_caption' => array( 'fieldName' => 'legenda da imagem', 'rules' => 'max:100' ),
            'status' => array( 'fieldName' => 'status', 'valueIn' => array( self::STATUS_INACTIVE, self::STATUS_ACTIVE ) ),
            'position' => array( 'fieldName' => 'posição', 'type' => Validator::NUMERIC_INT ),
            'series' => array(
                'fieldName' => 'série',
                'valueIn' => array_map( function ( $series ) { return $series->id; }, ( new PostsMapper() )->getAllSeries() )
            )
        );

        $this->series = new SeriesModel();
    }

    public function hasCat( $catId ) {
        if ( ! is_array( $this->categories ) ) {
            return false;
        }

        return in_array( $catId, array_map( function( $cat ) {
            return $cat->id;
        }, $this->categories ) );
    }
}
