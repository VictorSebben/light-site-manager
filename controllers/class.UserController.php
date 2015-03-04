<?php

class UserController extends BaseController {

    public function __construct( $model_name ) {
        parent::__construct( $model_name );
    }

    public function index() {
        // load user-objects array for use in the view
        $this->_view->objectList = $this->_mapper->index();
        $this->_view->render( 'users/index' );
    }

    public function delete( $id ) {
        echo 'Delete object ' . $this->_model_name . ' with id = ' . $id;
    }

    public function edit() {
        echo 'Edit';
    }

    public function show( $id ) {
        $this->_view->user = $this->_mapper->show( $id );
        $this->_view->render( 'users/show' );
    }
}