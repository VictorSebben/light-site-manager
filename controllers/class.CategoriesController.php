<?php

namespace lsm\controllers;

use lsm\libs\View;
use lsm\models\CategoriesModel;
use lsm\mappers\CategoriesMapper;
use lsm\libs\Pagination;
use lsm\libs\H;
use lsm\libs\Request;
use lsm\libs\Validator;
use Exception;
use PDOException;
use lsm\exceptions\PermissionDeniedException;

class CategoriesController extends BaseController {
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
        parent::__construct( 'Categories' );

        $this->_mapper = new CategoriesMapper();
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
        $this->_view->addExtraScript( 'js/category.js' );

        $this->prepareFlashMsg( $this->_view );

        $this->_view->render( 'categories/index', 'pagination' );
    }

    /**
     * We'll overwrite this method here, because this is the only case
     * where we can have both a success and an error message
     * (deletion with ajax - it may work for some categories and not for others
     * if they have posts associated)
     *
     * @param View $view
     */
    public function prepareFlashMsg( View $view ) {
        
        // TODO OVERWRITE AND REFACTOR ALL USES HERE.
        // TODO IN THE INDEX VIEW, TEST FOR BOTH MESSAGES.
        // TODO TEST EXCLUIR 2 CATEGORIES, ONE WITH AND ANOTHER WITHOUT POSTS
        if ( isset( $_SESSION[ 'success-msg' ] ) ) {
            $view->flashMsg[ 'success' ] = H::flash( 'success-msg' );
        }

        if ( isset( $_SESSION[ 'err-msg' ] ) ) {
            $errMsg = H::flash( 'err-msg' );
            $errMsg = json_decode( $errMsg ) ?: $errMsg;

            if ( is_array( $errMsg ) ) {
                $view->flashMsg[ 'err' ] = '<ul>';
                foreach ( $errMsg as $msg ) {
                    $view->flashMsg[ 'err' ] .= "<li>{$msg}</li>";
                }
                $view->flashMsg[ 'err' ] .= '</ul>';
            } else {
                $view->flashMsg[ 'err' ] = $errMsg;
            }
        }
    }

    public function create() {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        $category = new CategoriesModel();

        // Check if there is input data (we are redirecting the user back to the form
        // with an error message after he tried to submit it), in which case we will
        // give back the input data to the form
        $inputData = H::flashInput();
        if ( $inputData ) {
            $category->name = $inputData[ 'name' ];
            $category->description = $inputData[ 'description' ];
        }

        $this->_view->object = $category;

        $this->prepareFlashMsg( $this->_view );

        $this->_view->render( 'categories/form' );
    }

    public function insert() {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        $name = Request::getInstance()->getInput( 'name' );

        // We will generate the id by URLizing the name that
        // the user typed
        $id = H::str2Url( $name );

        // TODO Validate if the id is unique

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error messages
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (data the user had typed in) so that we can
            // put it back in the form fields
            H::flashInput( Request::getInstance()->getInput() );

            header( 'Location: ' . $this->_url->create() );
        } else {
            $this->_model->id = $id;
            $this->_model->name = $name;
            $this->_model->description = Request::getInstance()->getInput( 'description' );

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Categoria criada com sucesso!' );
            header( 'Location: ' . $this->_url->make( 'categories/index' ) );
        }
    }

    public function edit( $args ) {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        // Since this is a category, instead of getting a numeric id from the
        // URI, we get the categories (args). This may be a subcategory, so
        // its ID is actually the last category from $args
        $id = array_pop( $args );

        $this->_view->object = $this->_mapper->find( $id );

        if ( ! ( $this->_view->object instanceof CategoriesModel ) ) {
            throw new Exception( 'Erro: Categoria não encontrada!' );
        }

        // Try to get input data from session (data that the user had typed
        // in the form before). There will be input data if the validation
        // failed, and we want to redirect the user to the form with an
        // error message, putting back the data she had typed
        $inputData = H::flashInput();
        if ( $inputData ) {
            $this->_view->object->name = $inputData[ 'name' ];
            $this->_view->object->description = $inputData[ 'description' ];
        }

        $this->prepareFlashMsg( $this->_view );

        $this->_view->render( 'categories/form' );
    }

    public function update() {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        $name = Request::getInstance()->getInput( 'name' );

        // We will generate the id by URLizing the name that
        // the user typed
        $id = H::str2Url( $name );

        // Get the old id, to be used in where clauses
        $oldId = Request::getInstance()->getInput( 'id' );

        // TODO Validate if the id is unique

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error message
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (the data the user had typed int he form)
            H::flashInput( Request::getInstance()->getInput() );

            // Before redirecting, let's remove the category that is the primary key
            // from the URL arguments
            Request::getInstance()->rmArg( $oldId );

            header( 'Location: ' . $this->_url->edit( $oldId ) );
        } else {
            $this->_model->id = $id;
            $this->_model->name = Request::getInstance()->getInput( 'name' );
            $this->_model->description = Request::getInstance()->getInput( 'description' );

            $this->_mapper->save( $this->_model, false, $oldId );

            H::flash( 'success-msg', 'Categoria atualizada com sucesso!' );

            // Before redirecting, let's remove the category that is the primary key
            // from the URL arguments
            Request::getInstance()->rmArg( $oldId );

            header( 'Location: ' . $this->_url->index() );
        }
    }

    public function delete( $args ) {
        if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
            throw new PermissionDeniedException();
        }

        // Since this is a category, instead of getting a numeric id from the
        // URI, we get the categories (args). This may be a subcategory, so
        // its ID is actually the last category from $args
        $id = array_pop( $args );

        // give the view the CategoriesModel object
        $this->_view->object = $this->_mapper->find( $id );
        if ( ! ( $this->_view->object instanceof CategoriesModel ) ) {
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
        if ( $this->_mapper->getPostsByCategory( $id, true ) ) {
            H::flash( 'err-msg', "Não foi possível excluir a categoria, pois ela possui Posts associados!" );
            header( 'Location: ' . $this->_url->make( "categories/index" ) );
            exit;
        }

        $category = new CategoriesModel();
        $category->id = $id;

        try {
            $this->_mapper->destroy( $category );

            H::flash( 'success-msg', 'Categoria removida com sucesso!' );
            header( 'Location: ' . $this->_url->index() );
        } catch ( PDOException $e ) {
            H::flash( 'err-msg', "Não foi possível excluir a Categoria!" );
            header( 'Location: ' . $this->_url->index() );
        }
    }

    public function deleteAjax() {
        // Initialize error message and the $isOk flag to be sent back to the page
        $errorMsg = '';
        $isOk = false;

        // Get token that came with the request
        $token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Get array os Category IDs
        $items = $_POST[ 'items' ];

        try {
            // Validate token
            if ( ! H::checkToken( $token ) ) {
                // If token fails to validate, let's send back an error message
                $errorMsg = 'Não foi possível processar a requisição.';
            } // Validate permission to edit contents
            else if ( ! $this->_user->hasPrivilege( 'edit_categories' ) ) {
                $errorMsg = 'Permissão negada.';
            } // No problems occurred: we can carry through with the request
            else {
                $errorMsg = array();
                foreach ( $items as $key => $catId ) {
                    if ( $this->_mapper->getPostsByCategory( $catId, true ) ) {
                        $errorMsg[] = "Não foi possível excluir a categoria {$catId}, pois ela possui Posts associados!";
                        unset( $items[ $key ] );
                    }
                }

                if ( count( $errorMsg ) ) {
                    H::flash( 'err-msg', json_encode( $errorMsg ) );
                }

                if ( count( $items ) ) {
                    if ( $this->_mapper->deleteAjax( $items ) ) {
                        $isOk = true;

                        // If everything worked out, we are going to redirect the user
                        // back to the first page on the view. Therefore, we have to
                        // add a success message to the session
                        H::flash( 'success-msg', 'Categorias removidas com sucesso!' );
                    } else {
                        $errorMsg = 'Não foi possível excluir as Categorias. Contate o suporte.';
                    }
                }
            }

            // At the end of the process, give back a new token
            // to the page, as well as the isOk flag and an eventual message.
            // We'll also send back the ids that were changed, so that we can
            // toggle the status checkboxes in the table.
            echo json_encode(
                array(
                    'isOk' => $isOk,
                    'token' => H::generateToken(),
                    'error' => $errorMsg,
                    'success' => 'Categorias excluídas com sucesso',
                    'items' => $items
                )
            );
        } catch ( Exception $e ) {
            // If any exceptions were thrown in the process, send an error message
            if ( DEBUG )
                echo json_encode( array( 'isOk' => false, 'error' => $e->getMessage() ) );
            else
                echo json_encode( array( 'isOk' => false, 'error' => $errorMsg ) );
        }
    }
}
