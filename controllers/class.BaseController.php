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
     * A Url object, used in Controllers mainly for redirection purposes.
     *
     * @var Url
     */
    protected $_url;

    /**
     * The View object.
     *
     * @var View
     */
    protected $_view;

    /**
     * @var UserModel
     */
    protected $_user;

    public function __construct( $model_base_name ) {
        $model_name = $model_base_name . 'Model';

        // instantiate Model
        $this->_model = new $model_name;
        // instantiate View
        $this->_view = new View();
        // instantiate Url
        $this->_url = new Url();

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
}
