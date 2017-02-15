<?php

namespace lsm\controllers;

use lsm\mappers\CategoriesMapper;
use lsm\models\SeriesModel;
use lsm\mappers\SeriesMapper;
use lsm\libs\Pagination;
use lsm\libs\H;
use lsm\libs\Request;
use lsm\libs\Validator;
use Exception;
use PDOException;
use lsm\exceptions\PermissionDeniedException;

class SeriesController extends BaseController {
    /**
     * The Model object.
     *
     * @var SeriesModel
     */
    protected $_model;

    /**
     * The Mapper object, used to deal with database operations.
     *
     * @var SeriesMapper
     */
    protected $_mapper;

    public function __construct() {
        parent::__construct( 'Series' );

        $this->_mapper = new SeriesMapper();
    }

    public function index( $args = null ) {
        // Load result of edit_contents permission test
        $this->_view->editContents = $this->_user->hasPrivilege( 'edit_contents' );

        $args = (array) $args;

        // instantiate Pagination object and
        // pass it to the Mapper
        $pagination = new Pagination();
        $this->_mapper->pagination = $pagination;

        // load series objects array for use in the view
        $this->_view->pagination = $pagination;
        $this->_view->objectList = $this->_mapper->index( array_pop( $args ) );

        $this->_view->categories = $this->_mapper->getAllCat();

        $this->_view->addExtraScript( 'js/jquery-ui/jquery-ui.min.js' );
        $this->_view->addExtraScript( 'js/list.js' );
        $this->_view->addExtraScript( 'js/series.js' );

        $this->_view->addExtraLink( 'js/jquery-ui/jquery-ui.min.css' );

        $this->prepareFlashMsg( $this->_view );

        $this->_view->render( 'series/index', 'pagination' );
    }

    public function create() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $series = new SeriesModel();

        // Populate categories for the select field
        $this->_view->categories = $this->_mapper->getAllCat();

        // Check if there is input data (we are redirecting the user back to the form
        // with an error message after he tried to submit it), in which case we will
        // give back the input data to the form
        $inputData = H::flashInput();
        if ( $inputData ) {
            $series->title = $inputData[ 'title' ];
            $series->intro = $inputData[ 'intro' ];
            $series->status = $inputData[ 'status' ];
        }

        $this->_view->object = $series;

        $this->prepareFlashMsg( $this->_view );

        $this->_view->addExtraScript( 'js/lsmhelper.js' );

        $this->_view->render( 'series/form' );
    }

    public function insert() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $request = Request::getInstance();

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error messages
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (data the user had typed in) so that we can
            // put it back in the form fields
            H::flashInput( $request->getInput() );

            header( 'Location: ' . $this->_url->create() );
        } else {
            $this->_model->title = $request->getInput( 'title' );
            $this->_model->intro = $request->getInput( 'intro' );
            $this->_model->status = $request->getInput( 'status' );

            // Let us make sure no error will occur if the user changed the hidden id's manually
            // and duplicated one
            $categories = array_unique( $request->getInput( 'cat' ) );
            array_map( function( $catId ) {
                $category = ( new CategoriesMapper() )->find( $catId );
                if ( ! $category ) {
                    // Flash error messages
                    H::flash( 'err-msg', "Categoria inválida: {$catId}" );
                    header( 'Location: ' . $this->_url->create() );
                    exit;
                }
                $this->_model->categories[] = $category;
            }, $categories );

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Série criada com sucesso!' );
            header( 'Location: ' . $this->_url->index() );
        }
    }

    public function edit( $id ) {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $this->_view->object = $this->_mapper->find( $id );
        $this->_mapper->initCategories( $this->_view->object );

        // Populate categories for the select field
        $this->_view->categories = $this->_mapper->getAllCat();

        if ( ! ( $this->_view->object instanceof SeriesModel ) ) {
            throw new Exception( 'Erro: Série não encontrada!' );
        }

        // Try to get input data from session (data that the user had typed
        // in the form before). There will be input data if the validation
        // failed, and we want to redirect the user to the form with an
        // error message, putting back the data she had typed
        $inputData = H::flashInput();
        if ( $inputData ) {
            $series = new SeriesModel();
            $series->title = $inputData[ 'title' ];
            $series->intro = $inputData[ 'intro' ];
            $series->status = $inputData[ 'status' ];

            if ( isset( $inputData[ 'cat' ] ) ) {
                array_map( function( $catId ) use ( $series ) {
                    $category = ( new CategoriesMapper() )->find( $catId );
                    $series->categories[] = $category;
                }, $inputData[ 'cat' ] );
            }

            $this->_view->object = $series;
        }

        $this->_view->addExtraScript( 'js/lsmhelper.js' );
        $this->prepareFlashMsg( $this->_view );
        $this->_view->render( 'series/form' );
    }

    public function update() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $request = Request::getInstance();

        // Get id from $_POST
        $id = $request->getInput( 'id' );

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error message
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (the data the user had typed int he form)
            H::flashInput( $request->getInput() );

            header( 'Location: ' . $this->_url->edit( $id ) );
        } else {
            $this->_model->id = $id;
            $this->_model->title = $request->getInput( 'title' );
            $this->_model->intro = $request->getInput( 'intro' );
            $this->_model->status = $request->getInput( 'status' );

            // Let us make sure no error will occur if the user changed the hidden id's manually
            // and duplicated one
            $categories = array_unique( $request->getInput( 'cat' ) );
            array_map( function( $catId ) {
                $category = ( new CategoriesMapper() )->find( $catId );
                if ( ! $category ) {
                    // Flash error messages
                    H::flash( 'err-msg', "Categoria inválida: {$catId}" );
                    header( 'Location: ' . $this->_url->create() );
                    exit;
                }
                $this->_model->categories[] = $category;
            }, $categories );

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Série atualizada com sucesso!' );
            header( 'Location: ' . $this->_url->index() );
        }
    }

    public function delete( $id ) {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        // give the view the SeriesModel object
        $this->_view->object = $this->_mapper->find( $id );
        if ( ! ( $this->_view->object instanceof SeriesModel ) ) {
            throw new Exception( 'Erro: Série não encontrada!' );
        }

        // Find posts related to the series. If there are posts, we will ask
        // the user if she wants to remove them or just disassociate them
        $this->_view->posts = $this->_mapper->getRelatedPosts( $id );

        $this->_view->addExtraLink( 'css/delete-series.css' );
        $this->prepareFlashMsg( $this->_view );
        $this->_view->render( 'series/delete' );
    }

    public function destroy() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        if ( ! H::checkToken( Request::getInstance()->getInput( 'token' ) ) ) {
            H::flash( 'err-msg', "Não foi possível processar a requisição!" );
            header( 'Location: ' . $this->_url->make( "series/index" ) );
            exit;
        }

        $id = Request::getInstance()->getInput( 'id' );

        // Test if there are posts associated with the series.
        // If there are, test if the user chose an option on the radio buttons
        $actionPosts = SeriesModel::NO_POSTS;
        $posts = $this->_mapper->getRelatedPosts( $id );
        if ( count( $posts ) ) {
            if ( ! isset( $_POST[ 'action-posts' ] ) ) {
                H::flash( 'err-msg', 'Você deve selecionar alguma ação a ser feita com o posts relacionados à Série!' );
                header( 'Location: ' . $this->_url->delete( $id ) );
            } else {
                $actionPosts = $_POST[ 'action-posts' ];
            }
        }

        $series = new SeriesModel();
        $series->id = $id;

        try {
            $this->_mapper->destroy( $series, $actionPosts );

            H::flash( 'success-msg', 'Série removida com sucesso!' );
            header( 'Location: ' . $this->_url->index() );
        } catch ( PDOException $e ) {
            H::flash( 'err-msg', "Não foi possível excluir a Série!" );
            header( 'Location: ' . $this->_url->index() );
        }
    }

    public function toggleStatus( $id ) {
        // Initialize error message (to be used if update fails) and the $isOk flag
        $errorMsg = '';
        $isOk = false;

        // Get token that came with the request
        $token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        try {
            // Validate token
            if ( !H::checkToken( $token ) ) {
                // If token fails to validate, let's send back an error message
                $errorMsg = 'Não foi possível processar a requisição.';
            } // Validate permission to edit contents
            else if ( !$this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão negada.';
            } // No problems occurred: we can carry through with the request
            else {
                if ( $this->_mapper->toggleStatus( $id ) ) {
                    $isOk = true;
                } else {
                    $errorMsg = 'Não foi possível atualizar o status da série. Contate o suporte.';
                }
            }

            // At the end of the process, give back a new token
            // to the page, as well as the isOk flag and an eventual error message
            echo json_encode( array( 'isOk' => $isOk, 'token' => H::generateToken(), 'error' => $errorMsg ) );
        } catch ( Exception $e ) {
            // If any exceptions were thrown in the process, send an error message
            if ( DEBUG )
                echo json_encode( array( 'isOk' => false, 'error' => $e->getMessage() ) );
            else
                echo json_encode( array( 'isOk' => false, 'error' => $errorMsg ) );
        }
    }

    public function activate() {
        $this->_toggleStatusArr( 'activate' );
    }

    public function deactivate() {
        $this->_toggleStatusArr( 'deactivate' );
    }

    /**
     * @param $method
     */
    private function _toggleStatusArr( $method ) {
        // Initialize messages and the $isOk flag to be sent back to the page
        $errorMsg = '';
        $successMsg = '';
        $isOk = false;

        // Get token that came with the request
        $token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Get array os Post IDs
        $items = $_POST[ 'items' ];

        try {
            // Validate token
            if ( !H::checkToken( $token ) ) {
                // If token fails to validate, let's send back an error message
                $errorMsg = 'Não foi possível processar a requisição.';
            } // Validate permission to edit contents
            else if ( !$this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão negada.';
            } // No problems occurred: we can carry through with the request
            else {
                if ( $this->_mapper->{$method}( $items ) ) {
                    $isOk = true;
                    if ( $method == 'activate' )
                        $successMsg = 'Séries ativadas com sucesso!';
                    else
                        $successMsg = 'Séries desativadas com sucesso!';
                } else {
                    $errorMsg = 'Não foi possível atualizar o status das Séries. Contate o suporte.';
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
                    'success' => $successMsg,
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

    public function deleteAjax() {
        // Initialize error message and the $isOk flag to be sent back to the page
        $errorMsg = '';
        $isOk = false;

        // Get token that came with the request
        $token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Get array os Post IDs
        $items = $_POST[ 'items' ];

        $actionPosts = intval( $_POST[ 'action-posts' ] );

        try {
            // Validate token
            if ( ! H::checkToken( $token ) ) {
                // If token fails to validate, let's send back an error message
                $errorMsg = 'Não foi possível processar a requisição.';
            } // Validate permission to edit contents
            else if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão negada.';
            } // No problems occurred: we can carry through with the request
            else {
                if ( $this->_mapper->deleteAjax( $items, $actionPosts ) ) {
                    $isOk = true;

                    // If everything worked out, we are going to redirect the user
                    // back to the first page on the view. Therefore, we have to
                    // add a success message to the session
                    H::flash( 'success-msg', 'Séries removidas com sucesso!' );
                } else {
                    $errorMsg = 'Não foi possível excluir as Séries. Contate o suporte.';
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
                    'success' => 'Séries excluídas com sucesso',
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