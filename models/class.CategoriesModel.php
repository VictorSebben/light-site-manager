<?php

namespace lsm\models;

class CategoriesModel extends BaseModel {

    public $id;
    public $name;
    public $description;

    /**
     * The width of the main image of the post belonging
     * to a certain category
     * @var string
     */
    public $img_w;

    /*
     * The height of the main image of the post belonging
     * to a certain category
     * @var string
     */
    public $img_h;

    public $tableName;

    // Default width and height for main image
    // of the posts
    const IMG_WIDTH = "200";
    const IMG_HEIGHT = "200";

    public function __construct() {
        parent::__construct();

        $this->tableName = 'categories';

        $this->rules = array(
            'name' => array( 'fieldName' => 'nome', 'rules' => 'required|max:40|min:3' ),
            'description' => array( 'fieldName' => 'descrição', 'rules' => 'max:300' )
        );
    }
}
