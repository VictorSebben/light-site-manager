<?php

class RoleMapper extends Mapper {

    public function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, name FROM roles WHERE id = ?"
        );
    }

    public function index() {
        // select roles
        $stmt = self::$_pdo->prepare(
            "SELECT id, name FROM roles ORDER BY id"
        );

        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_CLASS, 'RoleModel' );
        $roles = $stmt->fetchAll();
        $stmt->closeCursor();

        if ( !is_array( $roles ) ) return null;

        foreach ( $roles as $role ) {
            $this->populateRolePerms( $role );
        }

        // select all permissions
        $stmt = self::$_pdo->prepare(
            "SELECT id, description FROM permissions ORDER BY id"
        );

        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_ASSOC );
        $arrPerm = $stmt->fetchAll();

        return array( 'roles' => $roles, 'permissions' => $arrPerm );
    }

    /**
     * Populates the array or permission IDs of a given Role object.
     *
     * @param RoleModel $role
     * @return RoleModel
     * @throws Exception
     */
    public function populateRolePerms( RoleModel $role ) {
        if ( !is_numeric( $role->id ) )
            return;

        $stmt = self::$_pdo->prepare(
            "SELECT p.id
               FROM permissions p
               JOIN role_perm rp ON rp.perm_id = p.id
              WHERE rp.role_id = :role_id"
        );

        $stmt->bindParam( ':role_id', $role->id );
        $stmt->execute();

        while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
            $role->setPermission( $row[ 'id' ] );
        }
    }

    public function getPermissionsArray() {
        // select all permissions
        $stmt = self::$_pdo->prepare(
            "SELECT id, description FROM permissions ORDER BY id"
        );

        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_ASSOC );
        return $stmt->fetchAll();
    }

    public function save( RoleModel $role, $overrideNullData = false ) {
        self::$_pdo->prepare( "DELETE FROM role_perm WHERE role_id = ?" )->execute( array( $role->id ) );

        $stmt = self::$_pdo->prepare( "INSERT INTO role_perm (role_id, perm_id) VALUES (:role_id, :perm_id)" );

        // call parent method to save role
        parent::save( $role, true );

        // save role_perm entries
        foreach ( $role->permissions as $permId ) {
            $stmt->bindParam( ':role_id', $role->id, PDO::PARAM_INT );
            $stmt->bindParam( ':perm_id', $permId, PDO::PARAM_INT );
            $stmt->execute();
            $stmt->closeCursor();
        }
    }

    public function destroy( RoleModel $role ) {
        if ( ! is_numeric( $role->id ) ) {
            throw new Exception( 'Não foi possível remover: chave primária sem valor!' );
        }

        // remove all role_perm and role_user entries
        $this->destroyRolePerm( $role );
        $this->destroyRoleUser( $role );

        parent::destroy( $role );
    }

    protected function destroyRolePerm( RoleModel $role ) {
        self::$_pdo->prepare( "DELETE FROM role_perm WHERE role_id = ?" )
            ->execute( array( $role->id ) );
    }

    protected function destroyRoleUser( RoleModel $role ) {
        self::$_pdo->prepare( "DELETE FROM user_role WHERE role_id = ?" )
            ->execute( array( $role->id ) );
    }
}
