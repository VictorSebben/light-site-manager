<?php

class PostModel extends BaseModel {

    public $id;
    public $category_id;
    public $user_id;
    public $title;
    public $intro;
    public $post_text;
    public $image;
    public $image_caption;
    public $status;

    /**
     * @var array Array of gallery images associated to the post
     */
    public $galleries;

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
            'category' => array( 'fieldName' => 'categoria', 'rules' => 'required', 'type' => 'int' ),
            'title' => array( 'fieldName' => 'título', 'rules' => 'required|max:200|min:3' ),
            'image' => array( 'fieldName' => 'imagem', 'rules' => 'max:80' ),
            'image_caption' => array( 'fieldName' => 'legenda da imagem', 'rules' => 'max:100' ),
            'status' => array( 'fieldName' => 'status', 'valueIn' => array( self::STATUS_INACTIVE, self::STATUS_ACTIVE ) ),
        );
    }
}
