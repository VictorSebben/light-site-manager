<?php

class HomeController extends BaseController {

    public function __construct( $model_name ) {

        parent::__construct( $model_name );
    }

    public function index() {
        echo "This is the index method!";
    }

    public function notFound() {
        $this->_model->notFound();
    }
}
