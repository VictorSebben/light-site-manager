<?php

class PostsModel extends BaseModel {

    public $id;
    public $user_id;
    public $title;
    public $intro;
    public $post_text;
    public $image;
    public $image_caption;
    public $status;

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
            'image' => array( 'fieldName' => 'imagem', 'rules' => 'max:80' ),
            'image_caption' => array( 'fieldName' => 'legenda da imagem', 'rules' => 'max:100' ),
            'status' => array( 'fieldName' => 'status', 'valueIn' => array( self::STATUS_INACTIVE, self::STATUS_ACTIVE ) ),
        );
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
