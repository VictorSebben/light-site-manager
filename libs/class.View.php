<?php

class View extends stdClass {

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

    public function __construct( $templateName = 'main.html.php' ) {
        $this->Url = new Url();

        $this->_config = include CONF_DIR . 'inc.appconfig.php';

        if ( $templateName ) {
            $this->_template = '../views/' . $templateName;
        } else {
            $this->_template = null;
        }

        $this->extraLink = array();
        $this->extraScript = array(
            'js/jquery-2.1.4.min.js',
            'js/lsmhelper.js'
        );
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
    public function render( $path, $pathPaginationFile = null ) {
        $this->_file = "../views/{$path}.html.php";

        if ( $pathPaginationFile ) {
            $this->_pagFile = "../views/{$pathPaginationFile}.html.php";
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
}
