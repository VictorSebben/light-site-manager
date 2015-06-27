<?php

class View extends stdClass {

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
     * @var BaseModel
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

    public function __construct( $templateName = 'main.html.php' ) {
        $this->Url = new Url();

        if ( $templateName ) {
            $this->_template = 'views/' . $templateName;
        } else {
            $this->_template = null;
        }
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
        $this->_file = "views/{$path}.html.php";

        if ( $pathPaginationFile ) {
            $this->_pagFile = "views/{$pathPaginationFile}.html.php";
        }

        if ( $this->_template ) {
            require $this->_template;
        } else {
            require $this->_file;
        }
    }

}
