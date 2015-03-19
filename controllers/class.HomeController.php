<?php

class HomeController extends BaseController {

    public function __construct( $model_name ) {

        parent::__construct( $model_name );
    }

    public function index() {
        $this->_view->render( 'home/index' );
    }

    public function notFound() {
        $this->_model->notFound();
    }
}
