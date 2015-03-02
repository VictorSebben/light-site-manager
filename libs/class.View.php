<?php

class View {

    /**
     * File path.
     *
     * @var string
     */
    public $file;

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
     * @var Model
     */
    public $object;

    /**
     * Content to be filled in the template.
     *
     * @var string
     */
    public $mainContent;

    public function __construct( $templateName = 'main.html.php' ) {
        $this->_template = 'views/' . $templateName;
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
    public function render( $path, $load_pagination = false ) {
        $this->file = "views/{$path}.html.php";

        require $this->_template;
    }

}
