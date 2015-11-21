<?php

class UserController extends BaseController {
    /**
     * The Model object.
     *
     * @var UserModel
     */
    protected $_model;

    /**
     * The Mapper object, used to deal with database operations.
     *
     * @var UserMapper
     */
    protected $_mapper;

    public function __construct( $model_base_name ) {
        parent::__construct( $model_base_name );

        $mapper_name = $model_base_name . 'Mapper';
        $this->_mapper = new $mapper_name();
    }

    public function index() {

        // instantiate Pagination object and
        // pass it to the Mapper
        $pagination = new Pagination();
        $this->_mapper->pagination = $pagination;

        // load user-objects array for use in the view
        $this->_view->pagination = $pagination;
        $this->_view->objectList = $this->_mapper->index();
        $this->_view->render( 'users/index', 'pagination' );
    }

    public function delete( $id ) {
        echo 'Delete object ' . $this->_model->name . ' with id = ' . $id;
    }

    public function create() {
        $this->_view->render( 'users/form' );
    }

    public function insert() {
        echo "Let's insert, baby!";
    }

    public function edit( $id ) {
        $this->_view->user = $this->_mapper->find( $id );
        $this->_view->render( 'users/form' );
    }

    public function update( $id ) {
        echo "Let's update, baby!";
    }

    public function show( $id ) {
        $this->_view->user = $this->_mapper->show( $id );
        $this->_view->render( 'users/show' );
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
            } // Validate permission to edit all users
            else if ( !$this->_user->hasPrivilege( 'edit_other_users' ) ) {
                $errorMsg = 'Permissão negada.';
            } // No problems occurred: we can carry through with the request
            else {
                if ( $this->_mapper->toggleStatus( $id ) ) {
                    // At the end of the process, give back a new token
                    // to the page
                    $isOk = true;
                } else {
                    $errorMsg = 'Não foi possível atualizar o status do usuário. Contate o suporte.';
                }
            }

            echo json_encode( array( 'isOk' => $isOk, 'token' => H::generateToken(), 'error' => $errorMsg ) );
        } catch ( Exception $e ) {
            // If any exceptions were thrown in the process, send an error message
            if ( DEBUG )
                echo json_encode( array( 'isOk' => false, 'error' => $e->getMessage() ) );
            else
                echo json_encode( array( 'isOk' => false, 'error' => $errorMsg ) );
        }
    }
}
