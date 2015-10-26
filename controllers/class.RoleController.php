<?php

class RoleController extends BaseController {

    public function __construct( $model_base_name ) {
        parent::__construct( $model_base_name );

        if ( ! $this->_user->hasPrivilege( 'edit_roles', $this->_permList ) ) {
            throw new PermissionDeniedException();
        }

        $mapper_name = $model_base_name . 'Mapper';
        $this->_mapper = new $mapper_name();
    }

    public function index() {
        $this->_view->objectList = $this->_mapper->index();
        $this->_view->render( 'roles/index' );
    }

    public function edit() {
        $id = Request::getInstance()->pk;

        // give the view the RoleModel object
        $this->_view->object = $this->_mapper->find( $id );

        // populate the permissions array of the Role object
        $this->_mapper->populateRolePerms( $this->_view->object );

        $this->_view->permArray = $this->_mapper->getPermissionsArray();

        if ( ! ( $this->_view->object instanceof RoleModel ) ) {
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

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            H::flash( 'err-msg', $validator->getErrorsJson() );
            header( 'Location: ' . $this->_url->make( "roles/{$id}/edit/" ) );
        } else {
            $this->_model->id = $id;
            $this->_model->name = Request::getInstance()->getInput( 'name' );
            $this->_model->permissions = Request::getInstance()->getInput( 'perms' );

            // call RoleMapper::save() method, which overrides the parent's method
            // to insert the permissions in the role_perm table after saving the role
            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Role atualizada com sucesso!' );
            header( 'Location: ' . $this->_url->make( 'roles/' ) );
        }
    }

    public function delete() {
        $id = Request::getInstance()->pk;

        // give the view the RoleModel object
        $this->_view->object = $this->_mapper->find( $id );
        if ( ! ( $this->_view->object instanceof RoleModel ) ) {
            throw new Exception( 'Erro: Role não encontrada!' );
        }

        $this->_view->render( 'roles/delete' );
    }

    public function destroy() {
        if ( ! H::checkToken( Request::getInstance()->getInput( 'token' ) ) ) {
            H::flash( 'err-msg', "Não foi possível processar a requisição!" );
            header( 'Location: ' . $this->_url->make( "roles/" ) );
        }

        $role = new RoleModel();
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
