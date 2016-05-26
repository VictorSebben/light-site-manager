<?php

namespace lsm\libs;

class Url {

    /**
     * The base url location for the system.
     *
     * @var string
     */
    public $base;

    public function __construct() {
        $arrAppConf = include 'conf/inc.appconfig.php';
        $this->base = $arrAppConf[ 'base_url' ];

        if ( Request::getInstance()->uriAdminPath != '' ) {
            $this->base .= '/' . Request::getInstance()->uriAdminPath;
        }
    }

    /**
     * Builds the url using the base url.
     *
     * @param string $path The path the link leads to.
     * @return string The path.
     */
    public function make( $path = null ) {
        return ( $path ) ? "{$this->base}/{$path}" : "{$this->base}/";
    }

    /**
     * Based on an action that it gets as a parameter, this method
     * builds a link using all the information from the current URL,
     * from Request
     *
     * @param $action
     * @param $pk
     * @param $incPagn
     * @return string
     */
    public function act( $action, $pk = null, $incPagn = true ) {
        $request = Request::getInstance();

        $uri = "{$request->uriParts[ 'ctrl' ]}/";

        if ( $request->uriParts[ 'args_str' ] ) {
            $uri .= "{$request->uriParts[ 'args_str' ]}/";
        }

        if ( $pk ) {
            $uri .= "{$pk}/";
        }

        $uri .= $action;

        if ( $incPagn ) {
            $uri .= "/{$this->getPagnParams()}";
        }

        return $this->make( $uri );
    }

    public function index( $pag = true ) {
        return $this->act( 'index', null, $pag );
    }

    public function create() {
        return $this->act( 'create', null, false );
    }

    public function insert() {
        return $this->act( 'insert', null, false );
    }

    public function edit( $id ) {
        return $this->act( 'edit', $id );
    }

    public function update() {
        return $this->act( 'update' );
    }

    public function delete( $id ) {
        return $this->act( 'delete', $id, false );
    }

    public function destroy() {
        return $this->act( 'destroy', null, false );
    }

    /**
     * Gets the pagination parameter in URL format.
     *
     * @param $pagNum
     * @return string
     */
    public function getPagnParams( $pagNum = null, $incDefaultPage = false ) {
        $params = '';
        $request = Request::getInstance();

        // If the page number was provided as a parameter, use it. Else, try to get it from the
        // pagination parameters in the Request object. If none of them exists, assume page 1
        $pagNum = $pagNum ? $pagNum : ( $request->pagParams[ 'pag' ] ? $request->pagParams[ 'pag' ] : 1 );

        // Assemble pagination link using the pagination params
        foreach( $request->pagParams AS $key => $value ) {
            if ( $value || ( $incDefaultPage && $key === 'pag' ) ) {
                if ( $key === 'pag' ) {
                    $params .= "{$key}:{$pagNum}/";
                } // ! empty so we don't end up with urls with params without value like ord:/search=/
                else if ( ! empty( $value ) && $key != 'search' ) {
                    $params .= "{$key}:{$value}/";
                }
            }
        }

        if ( $request->pagParams['search'] !== null ) {
            $params .= "?search={$request->pagParams['search']}";
        }

        return $params;
    }
}
