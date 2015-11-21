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

        return $roles;
    }

    /**
     * Populates the array of permissions of a given Role object
     *
     * @param RoleModel $role
     * @return RoleModel
     * @throws Exception
     */
    public function populateRolePerms( RoleModel $role ) {
        if ( !is_numeric( $role->id ) )
            return;

        $stmt = self::$_pdo->prepare(
            "SELECT p.description
               FROM permissions p
               JOIN role_perm rp ON rp.perm_desc = p.description
              WHERE rp.role_id = :role_id"
        );

        $stmt->bindParam( ':role_id', $role->id );
        $stmt->execute();

        while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
            $role->setPermission( $row[ 'description' ] );
        }
    }

    public function getPermissionsArray() {
        // select all permissions
        $stmt = self::$_pdo->prepare(
            "SELECT description FROM permissions ORDER BY description"
        );

        $stmt->execute();
        return $stmt->fetchAll( PDO::FETCH_COLUMN );
    }

    public function save( RoleModel $role, $overrideNullData = false ) {
        // Start transaction: in case anything goes wrong when inserting,
        // we cancel the deletion that precedes it
        self::$_pdo->beginTransaction();

        try {
            self::$_pdo->prepare( "DELETE FROM role_perm WHERE role_id = ?" )->execute( array( $role->id ) );

            $stmt = self::$_pdo->prepare( "INSERT INTO role_perm (role_id, perm_desc) VALUES (:role_id, :perm_desc)" );

            // call parent method to save role
            parent::save( $role, true );

            // save role_perm entries
            foreach ( $role->permissions as $permDesc ) {
                $stmt->bindParam( ':role_id', $role->id, PDO::PARAM_INT );
                $stmt->bindParam( ':perm_desc', $permDesc, PDO::PARAM_STR );
                $stmt->execute();
                $stmt->closeCursor();
            }

            self::$_pdo->commit();
        } catch ( PDOException $e ) {
            // If something went wrong, rollback transaction
            // and throw a new exception to be caught by the
            // Router class
            self::$_pdo->rollBack();
            throw new Exception( $e->getMessage() );
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