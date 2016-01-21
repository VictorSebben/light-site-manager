<?php

class Request {

    /**
     * String containing the uri requested by the user.
     *
     * @var string
     */
    public $uri;

    /**
     * Array containing the uri parts after exploding the string.
     *
     * @var array
     */
    public $uriParts;

    /**
     * String containing the path to the server's root directory.
     * Used for PHP's paths (nor for HTML paths).
     *
     * @var string
     */
    public $servRootDir;

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
     * The id of the current entity (ex: User) that was accessed via URL.
     *
     * @var integer
     */
    public $pk;

    /**
     * The category. Ex: one could list products by category.
     *
     * @var integer
     */
    public $category;

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
        $this->query = "";
    }

    public static function getInstance() {
        if ( !isset( self::$instance ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function setPagParams() {

        $this->pagParams = array(
            'pag' => 1,
            'ord' => 'id',
            'dir' => 'ASC',
            'search' => null
        );

        foreach ( $this->uriParts as $uriPart ) {
            if ( strpos( $uriPart, ':' ) !== false ) {
                list( $key, $val ) = explode( ':', $uriPart );
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
     * @return string
     */
    public function getInput( $name = "", $post = true ) {
        $input = null;

        if ( $name != "" ) {
            // $_POST
            if ( $post ) {
                if ( isset( $_POST[ $name ] ) ) {
                    // if field was a checkbox, $input will be an array
                    if ( is_array( $_POST[ $name ] ) ) {
                        $input = array_map( function ( $value ) {
                            return filter_var( $value, FILTER_SANITIZE_SPECIAL_CHARS );
                        }, $_POST[ $name ] );
                    } else {
                        $input = filter_input( INPUT_POST, $name, FILTER_SANITIZE_SPECIAL_CHARS );
                    }
                }
            } // $_GET
            else if ( isset( $_GET[ $name ] ) ) {
                if ( is_array( $_GET[ $name ] ) ) {
                    $input = array_map( function ( $value ) {
                        return filter_var( $value, FILTER_SANITIZE_SPECIAL_CHARS );
                    }, $_GET[ $name ] );
                } else {
                    $input = filter_input( INPUT_GET, $name, FILTER_SANITIZE_SPECIAL_CHARS );
                }
            }
        } else {
            if ( $post ) {
                $input = filter_var_array( $_POST, FILTER_SANITIZE_SPECIAL_CHARS );
            } else {
                $input = filter_var_array( $_GET, FILTER_SANITIZE_SPECIAL_CHARS );
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
}
