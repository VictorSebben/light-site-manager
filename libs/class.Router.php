<?php

class Router extends Base {

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
     * Position in the array where the route matches so that we can look in $_controllers
     * at the same position in order to decide what to do.
     *
     * @var int
     */
    protected $_key;

    protected $_namespace;

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
        $this->_request = Request::getInstance();
        $this->_namespace = '';

        parent::__construct();
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

    public function setNamespace( $ns ) {
        $this->_namespace = $ns;
    }

    /**
     * Maps, routes, controller options (controller name and mathod) and additional parameters.
     *
     * @param String $route      A string containing the route. It may contain regular expressions.
     * @param array  $controller An associative array containing controller name and method to be called.
     * @param array  $params     Optional parameters that will be passed to the model class.
     * The first key must be called 'args'.
     */
    public function map( $route, $controller, $params = array() ) {
        $this->_routes[] = "/{$this->_namespace}{$route}";
        $this->_controllers[] = $controller;
        $this->_params[] = $params;
    }

    /**
     * Starts the application. If a route matches the url, _run() is called.
     */
    public function start() {
        // get URL accessed by the user
        $this->_request->uri = filter_var( $_SERVER[ "REQUEST_URI" ], FILTER_SANITIZE_SPECIAL_CHARS );

        // In order to make it work inside subdirectories on both Apache and NGINX, we have to
        // get the uri from $_SERVER (not as a $_GET param). As for Apache, we need to strip away
        // the root directory from the uri string, hence the preg_replace() functions.
        $this->_request->servRootDir = preg_replace( '/index.php/', '', trim( $_SERVER[ "SCRIPT_NAME" ], '/') );
        $this->_request->uri = preg_replace( ":{$this->_request->servRootDir}:", '', $this->_request->uri );

        foreach ( $this->_routes as $key => $route ) {

            if ( preg_match( ";^{$route}$;", $this->_request->uri ) ) {
                $this->_request->mappedRoute = $route;
                $this->_key = $key;
                try {
                    $this->_run();
                }  catch ( PermissionDeniedException $e ) {
                    // TODO redirect to an error page
                    echo $e->getMessage();
                }  catch ( PDOException $e ) {
                    if ( DEBUG )
                        echo "DebugError: " . $e->getMessage();
                    else
                        echo "Ocorreu um erro na execução da aplicação. Contate o administrador do sistema.";
                } catch ( Exception $e ) {
                    echo $e->getMessage();
                }
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
        $uri = trim( $this->_request->uri, '/' );
        // check the last position to be taken from th URI
        $pos = strpos( $uri, '?' ) ?: ( strlen( $uri ) );
        $this->_request->uriParts = explode( '/', substr( $uri, 0, $pos ) );

        if ( $this->_request->uriParts[ 0 ] === $this->_config[ 'admin_path' ] ) {
            Request::getInstance()->uriAdminPath = array_shift( $this->_request->uriParts );
        }

        $controller_name = $this->_controllers[ $this->_key ][ 'controller' ] . 'Controller';
        $model_base_name = $this->_controllers[ $this->_key ][ 'controller' ];
        $method_name = $this->_controllers[ $this->_key ][ 'method' ];

        $this->_request->controller = $this->_controllers[ $this->_key ][ 'controller' ];
        $this->_request->method = $this->_controllers[ $this->_key ][ 'method' ];

        $controller_class = new ReflectionClass( $controller_name );
        if ( $controller_class->isInstantiable() ) {

            $controller_obj = new $controller_name( $model_base_name );

            // get arguments
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

            $uriParts = $this->_request->uriParts;
            // remove first element, that contains the module name (ex: users)
            array_shift( $uriParts );

            $routeArgs = $this->_params[ $this->_key ][ 'args' ];
            for ( $i = 0; $i < count( $routeArgs ); $i++ ) {

                $uriArgVal = $uriParts[ $i ];

                // store param value in array that's going to be passed when calling the
                // method specified in the route
                $args[] = $uriArgVal;

                // store parameters in the request object
                switch ( $routeArgs[ $i ] ) {
                    case 'id':
                        $this->_request->pk = $uriArgVal;
                        break;
                    case 'cat':
                        $this->_request->category = $uriArgVal;
                }
            }
        }

        return $args;
    }

    protected function _isHomePath( $route ) {
        return ( $route === '' || $route === '/' || $route === '/index.php' );
    }
}
