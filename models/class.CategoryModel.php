<?php

class CategoryModel extends BaseModel {

    public $id;
    public $name;
    public $description;

    public $tableName;

    public function __construct() {
        parent::__construct();

        $this->tableName = 'categories';

        $this->rules = array(
            'name' => array( 'fieldName' => 'nome', 'rules' => 'required|max:40|min:3' ),
            'description' => array( 'fieldName' => 'descrição', 'rules' => 'max:300' )
        );
    }
}
