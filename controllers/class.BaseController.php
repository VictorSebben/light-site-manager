<?php

class BaseController {

    /**
     * The Model object.
     *
     * @var BaseModel
     */
    protected $_model;

    /**
     * The View object.
     *
     * @var View
     */
    protected $_view;

    public function __construct( $model_name ) {
        $this->_model = new $model_name;
        $this->_view = new View();
    }
}
