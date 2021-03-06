<?php

namespace lsm\libs;

class View extends \stdClass {

    /**
     * System configuration.
     *
     * @var array
     */
    protected $_config;

    /**
     * File path.
     *
     * @var string
     */
    protected $_file;

    /**
     * Pagination file path.
     *
     * @var string
     */
    protected $_pagFile;

    /**
     * Template name.
     *
     * @var string
     */
    protected $_template;

    /**
     * List of Model objects, to be used in the view.
     *
     * @var array
     */
    public $objectList;

    /**
     * Object of the type Model (a class that extends Model), to be used in the view.
     *
     * @var mixed
     */
    public $object;

    /**
     * Content to be filled in the template.
     *
     * @var string
     */
    public $mainContent;

    /**
     * Url helper class.
     *
     * @var Url
     */
    public $Url;

    /**
     * Array with extra link tags to set on the template
     *
     * @var array
     */
    public $extraLink;

    /**
     * Array with extra script tags to set on the template
     *
     * @var array
     */
    public $extraScript;

    /**
     * Flash message to be shown on the view
     * @var string
     */
    public $flashMsg;

    /**
     * Class name of the flash message
     * @var string
     */
    public $flashMsgClass;

    public $modules;

    public function __construct( $templateName = 'main.html.php' ) {
        $this->Url = new Url();

        $this->_config = include CONF_DIR . 'inc.appconfig.php';

        $this->modules = array();

        if ( $templateName ) {
            $this->modules = include 'modules/modules.php';

            $this->_template = 'views/' . $templateName;
        } else {
            $this->_template = null;
        }

        $this->extraLink = array();
        $this->extraScript = array(
            'js/jquery-2.1.4.min.js',
            'js/lsmhelper.js'
        );

        $this->flashMsg = $this->flashMsgClass = '';
    }

    public function addExtraLink( $href ) {
        $this->extraLink[] = $href;
    }

    public function addExtraScript( $src ) {
        $this->extraScript[] = $src;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->_template;
    }

    /**
     * @param mixed $template
     */
    public function setTemplate( $template )
    {
        $this->_template = $template;
    }

    /**
     * Includes a view.
     */
    public function render( $path, $pathPaginationFile = null, $basePath = 'views' ) {
        $this->_file = "{$basePath}/{$path}.html.php";

        if ( $pathPaginationFile ) {
            $this->_pagFile = "views/{$pathPaginationFile}.html.php";
        }

        if ( $this->_template ) {
            require $this->_template;
        } else {
            require $this->_file;
        }
    }

    public function getExtraLinkTags() {
        return array_reduce( $this->extraLink, function ( $tags, $href ) {
            return $tags . "<link rel='stylesheet' type='text/css' href='{$this->Url->make( $href )}'>\n";
        });
    }

    public function getExtraScriptTags() {
        return array_reduce( $this->extraScript, function ( $tags, $src ) {
            return $tags . "<script src='{$this->Url->make( $src )}'></script>\n";
        });
    }

    /**
     * @param $label
     * @param $ord
     * @return string
     */
    public function makeOrderByLink( $label, $ord ) {
        // We are going to use a hidden input here: the Request parameters
        // will be used to assemble the link
        $request = Request::getInstance();

        // If the link accessed by the user already had pagination for this link, we are going to
        // create the link with the opposite direction

        if ( $ord == $request->pagParams[ 'ord' ]
             && $request->pagParams[ 'dir' ] == 'ASC' ) {
            $dir = 'DESC';
            $arrow = '<span class="fa fa-caret-up"></span>';
        } else {
            $dir = 'ASC';
            $arrow = '<span class="fa fa-caret-down"></span>';
        }

        // Get the base of the link, which is the same page being rendered right now
        $path = "{$request->uriParts[ 'ctrl' ]}/";

        if ( $request->uriParts[ 'args_str' ] ) {
            $path .= "{$request->uriParts[ 'args_str' ]}/";
        }

        if ( $request->uriParts[ 'pk' ] ) {
            $path .= "{$request->uriParts[ 'pk' ]}/";
        }

        $path .= "{$request->uriParts[ 'act' ]}/";

        $path .= "ord:{$ord}/dir:{$dir}/";

        // Check search parameters
        if ( isset( $request->pagParams[ 'search' ] ) ) {
            $path .= "?search={$request->pagParams[ 'search' ]}";
        }

        return "<a href='{$this->Url->make( $path )}'>{$label}&nbsp;{$arrow}</a>";
    }
}
