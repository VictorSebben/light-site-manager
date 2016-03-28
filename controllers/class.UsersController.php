<?php

class UsersController extends BaseController {
    /**
     * The Model object.
     *
     * @var UsersModel
     */
    protected $_model;

    /**
     * The Mapper object, used to deal with database operations.
     *
     * @var UsersMapper
     */
    protected $_mapper;

    public function __construct() {
        parent::__construct( 'Users' );

        $mapper_name = 'UsersMapper';
        $this->_mapper = new $mapper_name();
    }

    public function index() {
        // Load result of edit_other_users permission test
        $this->_view->editOtherUsers = $this->_user->hasPrivilege( 'edit_other_users' );
        $this->_view->disableOwnUser = $this->_user->hasPrivilege( 'disable_own_user' );

        // instantiate Pagination object and
        // pass it to the Mapper
        $pagination = new Pagination();
        $this->_mapper->pagination = $pagination;

        // load user-objects array for use in the view
        $this->_view->pagination = $pagination;
        $this->_view->objectList = $this->_mapper->index();

        $this->_view->addExtraScript( 'js/list.js' );
        $this->_view->addExtraScript( 'js/user.js' );

        $this->prepareFlashMsg( $this->_view );

        $this->_view->render( 'users/index', 'pagination' );
    }

    public function create() {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }

        $user = new UsersModel();

        // Check if there is input data (we are redirecting the user back to the form
        // with an error message after he tried to submit it), in which case we will
        // give back the input data to the form
        $inputData = H::flashInput();
        if ( $inputData ) {
            $user->name = $inputData[ 'name' ];
            $user->email = $inputData[ 'email' ];
            $user->status = $inputData[ 'status' ];
        }

        $this->_view->object = $user;

        $this->prepareFlashMsg( $this->_view );

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

            header( 'Location: ' . $this->_url->create() );
        } else {
            $this->_model->name = Request::getInstance()->getInput( 'name' );
            $this->_model->email = Request::getInstance()->getInput( 'email' );
            $this->_model->password = password_hash( $_POST[ 'password' ], PASSWORD_DEFAULT );
            $this->_model->status = Request::getInstance()->getInput( 'status' );

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Usuário criado com sucesso!' );
            header( 'Location: ' . $this->_url->make( 'users/index' ) );
        }
    }

    public function edit( $id ) {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }

        $this->_view->disableOwnUser = $this->_user->hasPrivilege( 'disable_own_user' );

        $this->_view->object = $this->_mapper->find( $id );

        // Try to get input data from session (data that the user had typed
        // in the form before). There will be input data if the validation
        // failed, and we want to redirect the user to the form with an
        // error message, putting back the data she had typed
        $inputData = H::flashInput();
        if ( $inputData ) {
            $this->_view->object->name = $inputData[ 'name' ];
            $this->_view->object->email = $inputData[ 'email' ];
            $this->_view->object->status = $inputData[ 'status' ];
        }

        if ( ! ( $this->_view->object instanceof UsersModel ) ) {
            throw new Exception( 'Erro: Usuário não encontrado!' );
        }

        $this->prepareFlashMsg( $this->_view );

        $this->_view->render( 'users/form' );
    }

    public function update() {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }

        // Get id from $_POST
        $id = Request::getInstance()->getInput( 'id' );

        if ( ! $this->_user->hasPrivilege( 'disable_own_user' )
             && ( $this->_user->id == $id )
             && ( Request::getInstance()->getInput( 'status' ) == 0 ) ) {
            throw new PermissionDeniedException( 'Você não pode desativar seu próprio usuário!' );
        }

        // Check if the user wants to create a new password:
        // if she doesn't (there is no value for the password field),
        // we will not validate password and password-confirm
        if ( ! Request::getInstance()->getInput( 'password' ) ) {
            unset( $this->_model->rules[ 'password' ] );
            unset( $this->_model->rules[ 'password-confirm' ] );
        }

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error message
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (the data the user had typed int he form)
            H::flashInput( Request::getInstance()->getInput() );

            header( 'Location: ' . $this->_url->edit( $id ) );
        } else {
            $this->_model->id = $id;
            $this->_model->name = Request::getInstance()->getInput( 'name' );
            $this->_model->email = Request::getInstance()->getInput( 'email' );
            $this->_model->status = Request::getInstance()->getInput( 'status' );

            if ( Request::getInstance()->getInput( 'password' ) ) {
                $this->_model->password = password_hash( $_POST[ 'password' ], PASSWORD_DEFAULT );
            }

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Usuário atualizado com sucesso!' );
            header( 'Location: ' . $this->_url->index() );
        }
    }

    public function show( $id ) {
        $this->_view->user = $this->_mapper->show( $id );
        $this->_view->render( 'users/show' );
    }

    public function delete( $id ) {
        if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
            throw new PermissionDeniedException();
        }

        // give the view the UsersModel object
        $this->_view->object = $this->_mapper->find( $id );
        if ( ! ( $this->_view->object instanceof UsersModel ) ) {
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
            header( 'Location: ' . $this->_url->index() );
        }

        $user = new UsersModel();
        $user->id = Request::getInstance()->getInput( 'id' );

        if ( ! $this->_user->hasPrivilege( 'disable_own_user' )
             && $this->_user->id == $user->id  ) {
            throw new PermissionDeniedException( 'Você não pode desativar seu próprio usuário!' );
        }

        try {
            $this->_mapper->destroy( $user );

            H::flash( 'success-msg', 'Usuário removido com sucesso!' );
            header( 'Location: ' . $this->_url->index() );
        } catch ( PDOException $e ) {
            H::flash( 'err-msg', "Não foi possível excluir o Usuário!" );
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
            } // Validate permission to edit all users
            else if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
                $errorMsg = 'Permissão negada.';
            } else if ( ! $this->_user->hasPrivilege( 'disable_own_user' )
                       && $this->_user->id == $id ) {
                $errorMsg = 'Você não pode desativar seu próprio usuário!';
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

        // If user is trying to deactivate herself and she doesn't have permission to do so,
        // we will gently remove her id from the list of users to be removed.
        // Note: the view should already be disallowing that by not providing a checkbox for her own user
        if ( in_array( $this->_user->id, $items ) && ( ! $this->_user->hasPrivilege( 'disable_own_user' ) ) ) {
            unset( $items[ array_search( $this->_user->id, $items ) ] );
        }

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
                if ( $this->_mapper->{$method}( $items ) ) {
                    $isOk = true;
                    if ( $method == 'activate' )
                        $successMsg = 'Usuários ativados com sucesso!';
                    else
                        $successMsg = 'Usuários desativados com sucesso!';
                } else {
                    $errorMsg = 'Não foi possível atualizar o status dos Usuários. Contate o suporte.';
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

        // If user is trying to deactivate herself and she doesn't have permission to do so,
        // we will gently remove her id from the list of users to be removed.
        // Note: the view should already be disallowing that by not providing a checkbox for her own user
        if ( in_array( $this->_user->id, $items ) && ( ! $this->_user->hasPrivilege( 'disable_own_user' ) ) ) {
            unset( $items[ array_search( $this->_user->id, $items ) ] );
        }

        try {
            // Validate token
            if ( ! H::checkToken( $token ) ) {
                // If token fails to validate, let's send back an error message
                $errorMsg = 'Não foi possível processar a requisição.';
            } // Validate permission to edit all users
            else if ( ! $this->_user->hasPrivilege( 'edit_other_users' ) ) {
                $errorMsg = 'Permissão negada.';
            } else {
                if ( $this->_mapper->deleteAjax( $items ) ) {
                    $isOk = true;

                    // If everything worked out, we are going to redirect the user
                    // back to the first page on the view. Therefore, we have to
                    // add a success message to the session
                    H::flash( 'success-msg', 'Usuários removidos com sucesso!' );
                } else {
                    $errorMsg = 'Não foi possível atualizar o status dos Usuários. Contate o suporte.';
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
                    'success' => 'Usuários excluídos com sucesso',
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
