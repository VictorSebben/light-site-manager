<?php

class ConfigController {

    /**
     * @var UserModel
     */
    protected $_user;

    /**
     * @var array
     */
    protected $_permList = array();

    public function __construct( $model_base_name ) {
        $this->_view = new View();

        /*
         * Populate logged User and Permissions
         * for authentication throughout the system's
         * execution
         */
        // populate User
        $userMapper = new UserMapper();

        $this->_user = new UserModel();
        $this->_user->id = $_SESSION[ 'user' ];
        $userMapper->initRoles( $this->_user );
    }

    public function index() {
        // Load result of edit_roles permission test
        $this->_view->editRoles = $this->_user->hasPrivilege( 'edit_roles' );

        // Load main configuration page, with links to specific config options
        $this->_view->render( 'config/index' );
    }
}
