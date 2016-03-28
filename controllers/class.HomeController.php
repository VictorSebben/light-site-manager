<?php

class HomeController extends BaseController {
    /**
     * @var HomeModel
     */
    protected $_model;

    public function __construct() {
        parent::__construct( 'Home' );
    }

    public function welcome() {
        $this->_view->render( 'home/welcome' );
    }

    public function notFound() {
        $this->_model->notFound();
    }
}
