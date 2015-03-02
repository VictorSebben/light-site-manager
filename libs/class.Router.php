<?php

class Router {

    /**
     * The defined url routes, like '/users/\d+/edit'.
     *
     * @var array
     */
    protected $_routes;

    /**
    * Array containing controller names related to user requests.
    *
    * @var array
    */
    protected $_controllers;

    /**
    * Array containing extra parameters that might appear in some requests.
    *
    * @var array
    */
    protected $_params;

    /**
     * Array containing the optional arguments that can be passed in the url.
     *
     * @var array
     */
    protected $_args;

    /**
     * String containing the uri requested by the user.
     *
     * @var string
     */
    protected $_uri;

    /**
     * Array containing the parts of the original uri, after splitting it in the '/'.
     *
     * @var array
     */
    protected $_uri_parts;

    /**
     * Position in the array where the route matches so that we can look in $_controllers
     * at the same position in order to decide what to do.
     *
     * @var int
     */
    protected $_key;

    /**
     * Route defined in routes.php that matches the uri entered
     * by the user.
     *
     * @var string
     */
    protected $_mapped_route;

    /**
     * Request object that hold important information to be passed and searched.
     *
     * @var Request
     */
    protected $_request;

    /**
    * A Singleton instance of this class.
    *
    * @var Router
    */
    protected static $_instance;

    public function __construct() {
        $this->_request = new Request();
    }

    /**
    * Gets a singleton instance of the class.
    *
    * @return Router
    */
    public static function getInstance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Maps, routes, controller options (controller name and mathod) and additional parameters.
     *
     * @param String $route      A string containing the route. It may contain regular expressions.
     * @param Array  $controller An associative array containing controller name and method to be called.
     * @param Array  $params     Optional parameters that will be passed to the model class.
     * The first key must be called 'args'.
     */
    public function map( $route, $controller, $params = array() ) {
        $this->_routes[] = $route;
        $this->_controllers[] = $controller;
        $this->_params[] = $params;
    }

    /**
     * Starts the application. If a route matches the url, _run() is called.
     */
    public function start() {
        // get URL accessed by the user
        $this->_uri = filter_var( $_SERVER[ "REQUEST_URI" ], FILTER_SANITIZE_SPECIAL_CHARS );

        // In order to make it work inside subdirectories on both Apache and NGINX, we have to
        // get the uri from $_SERVER (not as a $_GET param). As for Apache, we need to strip away
        // the root directory from the uri string, hence the preg_replace() functions.
        $rootDirectory = preg_replace( '/index.php/', '', trim( $_SERVER[ "SCRIPT_NAME" ], '/') );
        $this->_uri = preg_replace( ":$rootDirectory:", '', $this->_uri );

        foreach ( $this->_routes as $key => $route ) {

            if ( preg_match( ";^{$route}$;", $this->_uri ) ) {
                $this->_mapped_route = $route;
                $this->_key = $key;
                $this->_run();
                break;
            }
        }

    }

    /*
    * Sets lots of attributes, instantiates the controller and model and calls
    * the correct method
    */
    protected function _run() {
        // trim '/' so that /news/11/ gives us two pieces instead of four (one before
        // the first /, and one after the last /, which will be always empty.
        $uri = trim( $this->_uri, '/' );
        $this->_uri_parts = explode( '/', $uri );

        $controller_name = $this->_controllers[ $this->_key ][ 'controller' ] . 'Controller';
        $model_name = $this->_controllers[ $this->_key ][ 'controller' ] . 'Model';
        $method_name = $this->_controllers[ $this->_key ][ 'method' ];

        $controller_class = new ReflectionClass( $controller_name );
        if ( $controller_class->isInstantiable() ) {

            $controller_obj = new $controller_name( $model_name );

            $this->_args = $this->_getRouteArgs();

            if ( count( $this->_args ) ) {
                call_user_func_array( array( $controller_obj, $method_name ), $this->_args );
            } else {
                call_user_func( array( $controller_obj, $method_name ) );
            }
        } else {
            throw new Exception( "{$controller_name} not found." );
        }
    }

    protected function _getRouteArgs() {
        $args = array();

        if ( count( $this->_params[ $this->_key ] ) ) {

            for ( $i = 1; $i <= count( $this->_params[ $this->_key ][ 'args' ] ); $i++ ) {

                $args[] = $this->_uri_parts[ $i ];
            }
        }

        return $args;
    }

    protected function _isHomePath( $route ) {
        return ( $route === '' || $route === '/' || $route === '/index.php' );
    }
}
