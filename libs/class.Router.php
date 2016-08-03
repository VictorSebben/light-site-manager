<?php

namespace lsm\libs;

use lsm\libs\H;
use lsm\conf\Base;
use Exception;
use PDOException;
use ReflectionClass;
use ReflectionMethod;
use lsm\exceptions\PermissionDeniedException;

class Router extends Base {

    /**
     * The defined url routes, like '/users/\d+/edit'.
     *
     * @var array
     */
    protected $_routeRegex;

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

        // Create the regexp to match the route accessed by the user.
        // From the parts (groups) that were matched, we are going to get the information
        // about Controller, categories, primary key, and method.
        $this->_routeRegex = '/(\w+)/((?:[a-z][a-z0-9-]{0,}/?){0,4}/)?(\d+)?/?([\w-]+(?=(?:/|$)))/?(.+)?';

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
     * Starts the application. If a route matches the url, _run() is called.
     */
    public function start() {
        // get URL accessed by the user
        $this->_request->uri = filter_var( $_SERVER[ "REQUEST_URI" ], FILTER_SANITIZE_SPECIAL_CHARS );

        // In order to make it work inside subdirectories on both Apache and NGINX, we have to
        // get the uri from $_SERVER (not as a $_GET param). As for Apache, we need to strip away
        // the root directory from the uri string, hence the preg_replace() functions.
        $this->_request->uri = preg_replace(
            ":" . preg_replace( '/index.php/', '', trim( $_SERVER[ "SCRIPT_NAME" ], '/') ) . ":",
            '',
            $this->_request->uri
        );

        // Remove query part, if there was one: it will be accessed via get later
        $pos = strpos( $this->_request->uri, '?' ) ? : ( strlen( $this->_request->uri ) );
        $this->_request->uri = substr( $this->_request->uri, 0, $pos );

        // If no controller or method was found, the run method will throw
        // an exception. We will catch the exception to show an error to the user.
        try {
            $this->_run();
        } catch ( PermissionDeniedException $e ) {
            echo $e->getMessage();
        } catch ( PDOException $e ) {
            if ( DEBUG ) {
                echo "DebugError: " . $e->getMessage();
            } else {
                echo "Ocorreu um erro na execução da aplicação. Contate o administrador do sistema.";
            }
        } catch ( Exception $e ) {
            echo $e->getMessage();
        }
    }

    /*
    * Sets lots of attributes, instantiates the controller and model and calls
    * the correct method
    */
    protected function _run() {
        $uriParts = array();

        // Initialize uriParts[ 'params' ]: if there were no pagination or other
        // parameters in the URL, this will remain null
        $this->_request->uriParts[ 'params' ] = null;

        // Initialize uriParts[ 'args_str' ]: it will contain the whole args string, before implosion
        $this->_request->uriParts[ 'args_str' ] = '';

        preg_match( ";{$this->_routeRegex};", $this->_request->uri, $uriParts );

        if ( count( $uriParts) ) {
            $this->_request->uri = array_shift( $uriParts );
            $this->_request->uriParts[ 'ctrl' ] = array_shift( $uriParts );

            $lastElem = array_pop( $uriParts );

            // Check if the last part of the array corresponds to parameters
            // (pagination, order by, etc.) or the method, and assign properties
            // accordingly

            if ( strpos( $lastElem, ':' ) !== false ) {
                $this->_request->uriParts[ 'params' ] = $lastElem;
                $this->_request->uriParts[ 'act' ] = array_pop( $uriParts );
            } else {
                $this->_request->uriParts[ 'act' ] = $lastElem;
            }

            $this->_request->uriParts[ 'pk' ] = array_pop( $uriParts );

            $uriParts = array_filter( $uriParts );

            $this->_request->uriParts[ 'args' ] = array_pop( $uriParts );
            if ( $this->_request->uriParts[ 'args' ] ) {
                $this->_request->uriParts[ 'args_str' ] = trim( $this->_request->uriParts[ 'args' ], '/' );
                $this->_request->uriParts[ 'args' ] = explode( '/', trim( $this->_request->uriParts[ 'args' ], '/' ) );
            }
        }
        // If nothing was matched, get default controller and method
        else {
            $this->_request->uriParts[ 'ctrl' ] = 'Home';
            $this->_request->uriParts[ 'act' ] = 'welcome';

            $this->_request->uriParts[ 'pk' ] = null;
            $this->_request->uriParts[ 'args' ] = null;
        }

        // set additional parameters for the pagination
        // in the request object
        Request::getInstance()->setPagParams();

        $modules = include 'modules/modules.php';

        if ( in_array( $this->_request->uriParts[ 'ctrl' ], $modules ) ) {
            $ns = "\\lsm\\modules\\{$this->_request->uriParts[ 'ctrl' ]}\\";
        } else {
            $ns = '\lsm\controllers\\';
        }

        $ctrl_name = $ns . $this->_dashes2camel( $this->_request->uriParts[ 'ctrl' ], true ). 'Controller';
        $method_name = $this->_dashes2camel( $this->_request->uriParts[ 'act' ] );

        $ref_class = new ReflectionClass( $ctrl_name );
        if ( ! $ref_class->isInstantiable() ) {
            throw new Exception( "{$ctrl_name} not found." );
        } else if ( ! $ref_class->hasMethod( $method_name ) ) {
            throw new Exception( "Method {$method_name} not found." );
        }

        $args = array();
        $ref_method = new ReflectionMethod( $ctrl_name, $method_name );
        foreach ( $ref_method->getParameters() as $param ) {
            if ( $param->getName() === 'pk' || $param->getName() === 'id' ) {
                if ( ! $this->_request->uriParts[ 'pk' ] ) {
                    throw new Exception( 'Primary key not informed.' );
                }

                $args[] = $this->_request->uriParts[ 'pk' ];
            } else if ( $param->getName() === 'fk' || $param->getName() === 'args' ) {
                if ( ( ! $this->_request->uriParts[ 'args' ] ) && ( ! $param->allowsNull() ) ) {
                    throw new Exception( 'Arguments not informed.' );
                }

                $args[] = $this->_request->uriParts[ 'args' ];
            }
        }

        if ( count( $args ) ) {
            call_user_func_array( array( new $ctrl_name(), $method_name ), $args );
        } else {
            call_user_func( array( new $ctrl_name, $method_name ) );
        }
    }

    /**
     * @param $uri
     * @return array
     */
    protected function _getRouteArgs( $uri ) {
        // Check the last position to be taken from the URI, cutting the query parameters
        $pos = strpos( $uri, '?' ) ?: ( strlen( $uri ) );
        $uri = explode( '/', substr( $uri, 0, $pos ) );

        // Get the controller/model name (first position of the URL)
        $this->_request->uriParts[ 'ctrl' ] = array_shift( $uri );

        // Initialize array of categories
        $this->_request->uriParts[ 'cats' ] = array();
        // Get categories
        foreach ( $uri as $part ) {
            // When we get to the pagination or order by slugs, or the id (numeric value)
            // we are not in the categories positions anymore
            if ( ( strpos( $part, ':' ) !== false )
                || preg_match( '%\d+%', $part ) ) break;

            $this->_request->uriParts[ 'cats' ][] = $part;
        }

        // TODO -> USE ARGS TO CHECK IF STRING IS CATEGORY OR ACTION?
        // TODO Ex: /posts/create/
        H::ppr( $this->_request->uriParts );
        exit;

        $args = array();

        if ( ! count( $this->_params[ $this->_key ] ) ) {
            return $args;
        }

        $routeArgs = $this->_params[ $this->_key ][ 'args' ];



        return $args;
    }

    protected function _dashes2camel( $str, $capitalizeFirstChar = false ) {
        $str = str_replace(' ', '', ucwords(str_replace('-', ' ', $str)));

        if ( ! $capitalizeFirstChar ) {
            $str[ 0 ] = strtolower( $str[ 0 ] );
        }

        return $str;
    }

    protected function _isHomePath( $route ) {
        return ( $route === '' || $route === '/' || $route === '/index.php' );
    }
}
