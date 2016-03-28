<?php

class RolesController extends BaseController {

    /**
     * The RolesMapper object, used to deal with database operations
     *
     * @var RolesMapper
     */
    protected $_mapper;

    /**
     * @var RolesModel
     */
    protected $_model;

    public function __construct( $model_base_name ) {
        parent::__construct( $model_base_name );

        if ( ! $this->_user->hasPrivilege( 'edit_roles' ) ) {
            throw new PermissionDeniedException();
        }

        $mapper_name = $model_base_name . 'Mapper';
        $this->_mapper = new $mapper_name();
    }

    public function index() {
        // instantiate Pagination object and
        // pass it to the Mapper
        $pagination = new Pagination();
        $this->_mapper->pagination = $pagination;

        $this->_view->pagination = $pagination;
        $this->_view->objectList = $this->_mapper->index();
        $this->_view->render( 'roles/index', 'pagination' );
    }

    public function edit( $id ) {
        // give the view the RolesModel object
        $this->_view->object = $this->_mapper->find( $id );

        // Try to get input data from session (data that the user had typed
        // in the form before). There will be input data if the validation
        // failed, and we want to redirect the user to the form with an
        // error message, putting back the data she had typed
        $input = H::flashInput();
        if ( $input ) {
            $this->_view->object->name = $input[ 'name' ];
        }

        // populate the permissions array of the Role object
        $this->_mapper->populateRolePerms( $this->_view->object );

        $this->_view->permArray = $this->_mapper->getPermissionsArray();

        if ( ! ( $this->_view->object instanceof RolesModel ) ) {
            throw new Exception( 'Erro: Role não encontrada!' );
        }

        $this->_view->render( 'roles/edit' );
    }

    public function insert() {
        $validator = new Validator();
        if ( !$validator->check( $_POST, $this->_model->rules ) ) {
            H::flash( 'err-msg', $validator->getErrorsJson() );
        } else {
            $this->_model->name = Request::getInstance()->getInput( 'name' );
            $this->_mapper->save( $this->_model );

            H::flash( 'success-msg', 'Role inserida com sucesso!' );
        }

        header( 'Location: ' . $this->_url->make( 'roles/' ) );
    }

    public function update() {
        // get id from $_POST
        $id = Request::getInstance()->getInput( 'id' );

        // Since we have a select field in the edition
        // form (to choose the permissions), let's create
        // a new validation rule enforcing that the value be
        // a valid permission (one that exists in the DB)
        $this->_model->rules[ "perms" ] = array(
            'fieldName' => 'permissão',
            'valueIn' => $this->_mapper->getPermissionsArray()
        );

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error message
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (the data the user had typed int he form)
            H::flashInput( Request::getInstance()->getInput() );

            header( 'Location: ' . $this->_url->make( "roles/{$id}/edit/" ) );
        } else {
            $this->_model->id = $id;
            $this->_model->name = Request::getInstance()->getInput( 'name' );
            $this->_model->permissions = Request::getInstance()->getInput( 'perms' );

            // call RolesMapper::save() method, which overrides the parent's method
            // to insert the permissions in the role_perm table after saving the role
            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Role atualizada com sucesso!' );
            header( 'Location: ' . $this->_url->make( 'roles/' ) );
        }
    }

    public function delete( $id ) {
        // give the view the RolesModel object
        $this->_view->object = $this->_mapper->find( $id );
        if ( ! ( $this->_view->object instanceof RolesModel ) ) {
            throw new Exception( 'Erro: Role não encontrada!' );
        }

        $this->_view->render( 'roles/delete' );
    }

    public function destroy() {
        if ( ! H::checkToken( Request::getInstance()->getInput( 'token' ) ) ) {
            H::flash( 'err-msg', "Não foi possível processar a requisição!" );
            header( 'Location: ' . $this->_url->make( "roles/" ) );
        }

        $role = new RolesModel();
        $role->id = Request::getInstance()->getInput( 'id' );

        try {
            $this->_mapper->destroy( $role );

            H::flash( 'success-msg', 'Role removida com sucesso!' );
            header( 'Location: ' . $this->_url->make( "roles/" ) );
        } catch ( PDOException $e ) {
            H::flash( 'err-msg', "Não foi possível excluir a Role!" );
            header( 'Location: ' . $this->_url->make( "roles/" ) );
        }
    }
}
