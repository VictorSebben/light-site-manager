<?php

namespace lsm\libs;

use lsm\conf\Base;
use lsm\libs\H;

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
     * @var array
     */
    private $_link_params;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var int
     */
    protected $_pageNum;

    /**
     * @var Url
     */
    protected $_url;

    /**
     * Number of links to be shown before and after the current page number.
     */
    const LIM_LINKS = 1;

    public function __construct() {
        parent::__construct();
        $this->_request = Request::getInstance();
        $this->_pageNum = H::ifnull( $this->_request->pagParams[ 'pag' ], 1 );

        $this->_url = new Url();

        // Default: x per page
        $this->_limit = 2; // TODO TEST
    }

    public function getLimit() {
        return $this->_limit;
    }

    public function getCurrentPage() {
        return $this->_pageNum;
    }

    public function getOffset() {
        return ( ( $this->_pageNum - 1 ) * $this->_limit );
    }

    public function getTotalPages() {
        return ceil( $this->numRecords / $this->_limit );
    }

    public function getPreviousPage() {
        return $this->_pageNum - 1;
    }

    public function getNextPage() {
        return $this->_pageNum + 1;
    }

    public function getMinLimit() {
        // TODO -> if (TotalPages - $this->getCurrentPage() > LIM_LINKS) -> soma o que sobre do limite (porque faltou página no fim) no começo
        if ( $this->getTotalPages() - $this->getCurrentPage() < self::LIM_LINKS ) {
            $minLimit = $this->getCurrentPage() - self::LIM_LINKS - ( self::LIM_LINKS - ( $this->getTotalPages() - $this->getCurrentPage() ) );

            // If the minimum number found was 0, return 1
            return ( $minLimit ) ? $minLimit : 1;
        }
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
        $uri = $this->_url->act( $this->_request->uriParts[ 'act' ], $this->_request->uriParts[ 'pk' ], false );
        $uri .= "/{$this->_url->getPagnParams( $pagNum, true )}";

        return $uri;
    }
}
