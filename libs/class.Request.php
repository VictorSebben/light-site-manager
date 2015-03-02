<?php

class Request {

    /**
     * The routes[] array, in the Router class, will be divided in
     * different modules (associative keys indicating the different
     * routes related to a particular module of the system).
     * This variable will hold the module accessed by the user.
     *
     * @var string
     */
    protected $_routeModule;

    /**
     * The id of the current entity (ex: User) that was accessed via URL.
     *
     * @var integer
     */
    protected $_id;

    /**
     * The id of the category. Ex: one could list products by category.
     *
     * @var integer
     */
    protected $_catId;

    /**
     * The name of the mapped according to the URL accessed by the user.
     *
     * @var string
     */
    protected $_method;

    /**
     * @param string $routeModule
     */
    public function setRouteModule( $routeModule ) {
        $this->_routeModule = $routeModule;
    }

    /**
     * @param string $method
     */
    public function setMethod( $method ) {
        $this->_method = $method;
    }

    /**
     * @param int $id
     */
    public function setId( $id )
    {
        $this->_id = $id;
    }


    /**
     * @param int $catId
     */
    public function setCatId( $catId )
    {
        $this->_catId = $catId;
    }

}
