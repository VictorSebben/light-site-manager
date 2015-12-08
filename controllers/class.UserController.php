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
        // Load result of edit_other_users permission test
        $this->_view->editOtherUsers = $this->_user->hasPrivilege( 'edit_other_users' );

        // instantiate Pagination object and
        // pass it to the Mapper
        $pagination = new Pagination();
        $this->_mapper->pagination = $pagination;

        // load user-objects array for use in the view
        $this->_view->pagination = $pagination;
        $this->_view->objectList = $this->_mapper->index();
        $this->_view->render( 'users/index', 'pagination' );
    }

    public function create() {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }

        // Check if there is input data (we are redirecting the user back to the form
        // with an error message after he tried to submit it), in which case we will
        // give back the input data to the form
        $inputData = H::flashInput();
        if ( $inputData ) {
            $user = new UserModel();
            $user->name = $inputData[ 'name' ];
            $user->email = $inputData[ 'email' ];

            $this->_view->object = $user;
        }

        $this->_view->render( 'users/form' );
    }

    public function insert() {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error messages
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (data the user had typed in) so that we can
            // put it back in the form fields
            unset( $_POST[ 'password' ] );
            unset( $_POST[ 'password-confirm' ] );
            H::flashInput( Request::getInstance()->getInput() );

            header( 'Location: ' . $this->_url->make( 'users/create/' ) );
        } else {
            $this->_model->name = Request::getInstance()->getInput( 'name' );
            $this->_model->email = Request::getInstance()->getInput( 'email' );
            $this->_model->password = password_hash( $_POST[ 'password' ], PASSWORD_DEFAULT );

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Usuário criado com sucesso!' );
            header( 'Location: ' . $this->_url->make( 'users/' ) );
        }
    }

    public function edit( $id ) {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }

        $this->_view->user = $this->_mapper->find( $id );
        $this->_view->render( 'users/form' );
    }

    public function update( $id ) {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }
        echo "Let's update, baby!";
    }

    public function show( $id ) {
        $this->_view->user = $this->_mapper->show( $id );
        $this->_view->render( 'users/show' );
    }

    public function delete() {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }

        $id = Request::getInstance()->pk;

        // give the view the UserModel object
        $this->_view->object = $this->_mapper->find( $id );
        if ( ! ( $this->_view->object instanceof UserModel ) ) {
            throw new Exception( 'Erro: Usuário não encontrado!' );
        }

        $this->_view->render( 'users/delete' );
    }

    public function destroy() {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }

        if ( ! H::checkToken( Request::getInstance()->getInput( 'token' ) ) ) {
            H::flash( 'err-msg', "Não foi possível processar a requisição!" );
            header( 'Location: ' . $this->_url->make( "users/" ) );
        }

        $user = new UserModel();
        $user->id = Request::getInstance()->getInput( 'id' );

        try {
            $this->_mapper->destroy( $user );

            H::flash( 'success-msg', 'Usuário removido com sucesso!' );
            header( 'Location: ' . $this->_url->make( "users/" ) );
        } catch ( PDOException $e ) {
            H::flash( 'err-msg', "Não foi possível excluir o Usuário!" );
            header( 'Location: ' . $this->_url->make( "users/" ) );
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
