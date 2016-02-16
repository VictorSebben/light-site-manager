<?php

class CategoryController extends BaseController {
    /**
     * The Model object.
     *
     * @var CategoryModel
     */
    protected $_model;

    /**
     * The Mapper object, used to deal with database operations.
     *
     * @var CategoryMapper
     */
    protected $_mapper;

    public function __construct( $model_base_name ) {
        parent::__construct( $model_base_name );

        $mapper_name = $model_base_name . 'Mapper';
        $this->_mapper = new $mapper_name();
    }

    public function index() {
        // Load result of edit_categories permission test
        $this->_view->editCat = $this->_user->hasPrivilege( 'edit_categories' );

        // instantiate Pagination object and
        // pass it to the Mapper
        $pagination = new Pagination();
        $this->_mapper->pagination = $pagination;

        // load category-objects array for use in the view
        $this->_view->pagination = $pagination;
        $this->_view->objectList = $this->_mapper->index();

        $this->_view->addExtraScript( 'js/list.js' );

        $this->_view->render( 'categories/index', 'pagination' );
    }

    public function create() {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        // Check if there is input data (we are redirecting the user back to the form
        // with an error message after he tried to submit it), in which case we will
        // give back the input data to the form
        $inputData = H::flashInput();
        if ( $inputData ) {
            $category = new CategoryModel();
            $category->name = $inputData[ 'name' ];
            $category->description = $inputData[ 'description' ];

            $this->_view->object = $category;
        }

        $this->_view->render( 'categories/form' );
    }

    public function insert() {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error messages
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (data the user had typed in) so that we can
            // put it back in the form fields
            H::flashInput( Request::getInstance()->getInput() );

            header( 'Location: ' . $this->_url->make( 'categories/create/' ) );
        } else {
            $this->_model->name = Request::getInstance()->getInput( 'name' );
            $this->_model->description = Request::getInstance()->getInput( 'description' );

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Categoria criada com sucesso!' );
            header( 'Location: ' . $this->_url->make( 'categories/' ) );
        }
    }

    public function edit() {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        $id = Request::getInstance()->pk;

        $this->_view->object = $this->_mapper->find( $id );

        // Try to get input data from session (data that the user had typed
        // in the form before). There will be input data if the validation
        // failed, and we want to redirect the user to the form with an
        // error message, putting back the data she had typed
        $inputData = H::flashInput();
        if ( $inputData ) {
            $this->_view->object->name = $inputData[ 'name' ];
            $this->_view->object->description = $inputData[ 'description' ];
        }

        if ( ! ( $this->_view->object instanceof CategoryModel ) ) {
            throw new Exception( 'Erro: Categoria não encontrada!' );
        }

        $this->_view->render( 'categories/form' );
    }

    public function update() {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        // Get id from $_POST
        $id = Request::getInstance()->getInput( 'id' );

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error message
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (the data the user had typed int he form)
            H::flashInput( Request::getInstance()->getInput() );

            header( 'Location: ' . $this->_url->make( "categories/{$id}/edit/" ) );
        } else {
            $this->_model->id = $id;
            $this->_model->name = Request::getInstance()->getInput( 'name' );
            $this->_model->description = Request::getInstance()->getInput( 'description' );

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Categoria atualizada com sucesso!' );
            header( 'Location: ' . $this->_url->make( 'categories/' ) );
        }
    }

    public function show( $id ) {
        $this->_view->user = $this->_mapper->show( $id );
        $this->_view->render( 'categories/show' );
    }

    public function delete() {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        $id = Request::getInstance()->pk;

        // give the view the CategoryModel object
        $this->_view->object = $this->_mapper->find( $id );
        if ( ! ( $this->_view->object instanceof CategoryModel ) ) {
            throw new Exception( 'Erro: Categoria não encontrada!' );
        }

        $this->_view->render( 'categories/delete' );
    }

    public function destroy() {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        if ( ! H::checkToken( Request::getInstance()->getInput( 'token' ) ) ) {
            H::flash( 'err-msg', "Não foi possível processar a requisição!" );
            header( 'Location: ' . $this->_url->make( "categories/" ) );
        }

        $id = Request::getInstance()->getInput( 'id' );

        // Validate if there are posts associated to this category,
        // in which case deletion will not be allowed
        if ( ! $this->_mapper->getPostsByCategory( $id, true ) ) {
            H::flash( 'err-msg', "Não foi possível excluir a categoria, pois ela possui Posts associados!" );
            header( 'Location: ' . $this->_url->make( "categories/" ) );
        }

        $category = new CategoryModel();
        $category->id = $id;

        try {
            $this->_mapper->destroy( $category );

            H::flash( 'success-msg', 'Categoria removida com sucesso!' );
            header( 'Location: ' . $this->_url->make( "categories/" ) );
        } catch ( PDOException $e ) {
            H::flash( 'err-msg', "Não foi possível excluir a Categoria!" );
            header( 'Location: ' . $this->_url->make( "categories/" ) );
        }
    }
}
