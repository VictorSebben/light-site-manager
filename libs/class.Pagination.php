<?php

class Pagination extends Base {

    private $_limit;

    /**
     * Total number of records found on the DB.
     *
     * @var int
     */
    public $numRecords;

    /**
     * Set by the controller. Tells Pagination what to allow for pagination links.
     *
     * @var Array
     */
    private $_link_params;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * Number of links to be shown before and after the current page number.
     */
    const LIM_LINKS = 1;

    public function __construct() {
        parent::__construct();
        $this->_request = Request::getInstance();

        // default: x per page
        $this->_limit = 2;
    }

    public function getCurrentPage() {
        return $this->_request->pagParams["pag"];
    }

    public function getOffset() {
        return ( ($this->_request->pagParams["pag"] - 1) * $this->_limit );
    }

    public function getTotalPages() {
        return ceil( $this->numRecords / $this->_limit );
    }

    public function getPreviousPage() {
        return $this->_request->pagParams["pag"] - 1;
    }

    public function getNextPage() {
        return $this->_request->pagParams["pag"] + 1;
    }

    public function getMinLimit() {
        // TODO -> if (TotalPages - $this->getCurrentPage() > LIM_LINKS) -> soma o que sobre do limite (porque faltou página no fim) no começo
        if ( $this->getTotalPages() - $this->getCurrentPage() < self::LIM_LINKS )
            return $this->getCurrentPage() - self::LIM_LINKS - (self::LIM_LINKS - ($this->getTotalPages() - $this->getCurrentPage()));
        else if ( $this->getCurrentPage() >= self::LIM_LINKS + 2 )
            return $this->getCurrentPage() - self::LIM_LINKS;
        else
            return 1;
    }

    public function hasPreviousPage() {
        return $this->getPreviousPage() >= 1;
    }

    public function hasNextPage() {
        return $this->getNextPage() <= $this->getTotalPages();
    }

    public function getPagnLink( $pagNum = 1 ) {
        $uri = $this->_config['base_url'] . '/' . $this->_request->uriParts[0] . '/';

        // assemble pagination link using the pagination params
        foreach( $this->_request->pagParams AS $key => $value ) {
            if ( $key === 'pag' ) {
                $uri .= "{$key}:{$pagNum}/";
            }

            // ! empty so we don't end up with urls with params without value like ord:/search=/
            else if ( ! empty( $value ) && $key != 'search' ) {
                $uri .= "{$key}:{$value}/";
            }
        }

        if ( $this->_request->pagParams['search'] !== null ) {
            $uri .= "?search={$this->_request->pagParams['search']}";
        }

        return $uri;
    }
}
