<?php

class BaseController {

    /**
     * The Model object.
     *
     * @var BaseModel
     */
    protected $_model;

    /**
     * The Mapper object, used to deal with database operations.
     *
     * @var Mapper
     */
    protected $_mapper;

    /**
     * The View object.
     *
     * @var View
     */
    protected $_view;

    public function __construct( $model_base_name ) {
        $model_name = $model_base_name . 'Model';
        $mapper_name = $model_base_name . 'Mapper';

        $this->_model = new $model_name;
        $this->_mapper = new $mapper_name;
        $this->_view = new View();
    }
}
