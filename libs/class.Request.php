<?php

namespace lsm\libs;

use lsm\libs\H;

class Request {

    /**
     * String containing the uri requested by the user.
     *
     * @var string
     */
    public $uri;

    /**
     * If the route accessed is an admin route (lsm),
     * this property will be set and used to compose the
     * base URL, which is internally used for links, form actions,
     * etc. of the admin
     *
     * @var string
     */
    public $uriAdminPath;

    /**
     * Array containing the uri parts after exploding the string.
     *
     * @var array
     */
    public $uriParts;

    /**
     * Route defined in routes.php that matches the uri entered
     * by the user.
     *
     * @var string
     */
    public $mappedRoute;

    /**
     * The routes[] array, in the Router class, will be divided in
     * different modules (associative keys indicating the different
     * routes related to a particular module of the system).
     * This variable will hold the module accessed by the user.
     *
     * @var string
     */
    public $routeModule;

    /**
     * String containing the order options to be used on the query to the db.
     *
     * @var string
     */
    public $ordBy;

    /**
     * @var array Associative array containing key/values after the ?.
     */
    public $query;

    /**
     * Associative array containing pagination information.
     * @var array
     */
    public $pagParams;

    /**
     * The name of the controller the user has accessed
     * @var string
     */
    public $controller;

    /**
     * The name of the method the user has accessed
     * @var string
     */
    public $method;

    /**
     * @var Request
     */
    private static $instance;

    /**
     * @var array Contains the different parts of the route.
     */
    public $routeParts;

    private function __construct() {
        $this->query = '';
        $this->uriAdminPath = '';
    }

    public static function getInstance() {
        if ( !isset( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function setPagParams() {
        $this->pagParams = [
            'pag' => null,
            'ord' => null,
            'dir' => null,
            'search' => null
        ];

        if ( $this->uriParts[ 'params' ] ) {
            $params = explode( '/', trim( $this->uriParts[ 'params' ], '/' ) );
            foreach ( $params as $param ) {
                list( $key, $val ) = explode( ':', $param );
                $this->pagParams[ $key ] = $val;
            }
        }

        // TODO Refactor here and Mappers: search should be kept in $this->query
        $this->pagParams[ 'search' ] = filter_input( INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS );
    }

    /**
     * Retrieves data from $_POST (default) or $_GET (in case
     * the $post parameter is set to false), using filter_input.
     *
     * @param string $name
     * @param bool|true $post
     * @param int
     * @return string
     */
    public function getInput( $name = "", $post = true, $filter = FILTER_SANITIZE_SPECIAL_CHARS ) {
        $input = null;
        // FIXME filter xss attacks
        if ( $name != "" ) {
            // $_POST
            if ( $post ) {
                if ( isset( $_POST[ $name ] ) ) {
                    // if field was a checkbox, $input will be an array
                    if ( is_array( $_POST[ $name ] ) ) {
                        $input = array_map( function ( $value ) use ( $filter ) {
                            return filter_var( $value, $filter );
                        }, $_POST[ $name ] );
                    } else {
                        $input = filter_input( INPUT_POST, $name, $filter );
                    }
                }
            } // $_GET
            else if ( isset( $_GET[ $name ] ) ) {
                if ( is_array( $_GET[ $name ] ) ) {
                    $input = array_map( function ( $value ) use ( $filter ) {
                        return filter_var( $value, $filter );
                    }, $_GET[ $name ] );
                } else {
                    $input = filter_input( INPUT_GET, $name, $filter );
                }
            }
        } else {
            if ( $post ) {
                $input = filter_var_array( $_POST, $filter );
            } else {
                $input = filter_var_array( $_GET, $filter );
            }
        }

        return $input;
    }

    public function redirect( $url, $preferSession = false ) {
        if ( $preferSession && isset( $_SESSION[ 'redirect_to' ] ) ) {
            $redirTo = $_SESSION[ 'redirect_to' ];
            unset( $_SESSION[ 'redirect_to' ] );
            header( "Location: {$redirTo}" );
        }
        else {
            header( "Location: {$url}" );
        }
    }

    public function rmArg( $argName ) {
        $key = array_search( $argName, $this->uriParts[ 'args' ] );

        if ( $key === false )
            return;

        unset( $this->uriParts[ $key ] );

        // Remove string from array
        $this->uriParts[ 'args_str' ] = H::sanitizeSlashes( preg_replace( "%{$argName}%", '', $this->uriParts[ 'args_str' ] ) );
    }
}
