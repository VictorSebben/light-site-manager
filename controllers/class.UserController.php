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
        $token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Validate token
        if ( ! H::checkToken( $token ) ) {
            return json_encode( array( 'isOk' => false, 'error' => 'Não foi possível processar a requisição!' ) );
        }

        // Validate permission to edit all users
        if ( ! $this->_user->hasPrivilege( 'edit_other_users', $this->_permList ) ) {
            return json_encode( array( 'isOk' => false, 'error' => 'Permissão negada.' ) );
        }

        // TODO give back a new token to the page, and
        // TODO insert new token in the session
        // TODO ver com o Fernando se essa approach é legal

        echo H::generateToken();

        // $this->_mapper->toggleStatus( $id );
    }
}
