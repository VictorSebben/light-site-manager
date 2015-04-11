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
     * @var Array
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
     * Current pagination page number.
     *
     * @var integer
     */
    public $pageNum;

    /**
     * String containing the order options to be used on the query to the db.
     *
     * @var string
     */
    public $ordBy;

    /**
     * @var Array Associative array containing key/values after the ?.
     */
    public $query;

    /**
     * Associative array containing pagination information.
     * @var Array
     */
    public $pagn;

    /**
     * @var Array Contains the different parts of the route.
     */
    public $routeParts;

    public function __construct() {
        $this->query = [
            'search' => NULL,
            'pag' => 1,
            'ord' => 'id',
            'dir' => 'DESC'
        ];
    }

}
