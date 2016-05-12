<?php

class ContactController extends BaseController {
    /**
     * The Model object.
     *
     * @var CategoriesModel
     */
    protected $_model;

    /**
     * The Mapper object, used to deal with database operations.
     *
     * @var CategoriesMapper
     */
    protected $_mapper;

    public function __construct() {
        parent::__construct( 'Contact' );

        $mapper_name = 'ContactMapper';
        $this->_mapper = new $mapper_name();
    }

    public function index() {
        // instantiate Pagination object and
        // pass it to the Mapper
        $pagination = new Pagination();
        $this->_mapper->pagination = $pagination;

        // load category-objects array for use in the view
        $this->_view->pagination = $pagination;
        $this->_view->objectList = $this->_mapper->index();

        $this->_view->addExtraLink( 'css/contact.css' );

        $this->_view->render( 'contact/index', 'pagination' );
    }
}
