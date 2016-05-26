<?php

namespace lsm\mappers;

use lsm\models\RolesModel;
use PDO;
use Exception;
use PDOException;

class RolesMapper extends Mapper {

    public function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, name FROM roles WHERE id = ?"
        );
    }

    public function index() {
        // set additional parameters for the pagination
        // in the request object
        $this->request->setPagParams();
        $params = $this->request->pagParams;

        $offset = $this->pagination->getOffset();

        // validate $params[ 'dir' ] to make sure it contains a valid value
        if ( $params[ 'dir' ] !== 'ASC' && $params[ 'dir' ] !== 'DESC' ) {
            $params[ 'dir' ] = 'ASC';
        }

        $ord = 'id';
        $rs = self::$_pdo->query( 'SELECT id, name FROM roles LIMIT 0' );
        for ( $i = 0; $i < $rs->columnCount(); $i++ ) {
            if ( $rs->getColumnMeta( $i )[ 'name' ] == $params[ 'ord' ] ) {
                $ord = $params[ 'ord' ];
                break;
            }
        }

        // Set number of records in the pagination object
        $this->_setNumRecordsPagn();

        $stmt = self::$_pdo->prepare(
            "SELECT id, name FROM roles ORDER BY {$ord} {$params[ 'dir' ]} LIMIT :lim OFFSET :offset"
        );

        $lim = $this->pagination->getLimit();

        $stmt->bindParam( ':lim', $lim, PDO::PARAM_INT );
        $stmt->bindParam( ':offset', $offset, PDO::PARAM_INT );
        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\models\RolesModel' );
        $roles = $stmt->fetchAll();
        $stmt->closeCursor();

        if ( !is_array( $roles ) ) return null;

        foreach ( $roles as $role ) {
            $this->populateRolePerms( $role );
        }

        return $roles;
    }

    protected function _setNumRecordsPagn() {
        $sql = "SELECT count(*) AS count
                  FROM roles ";

        $selectStmt = self::$_pdo->prepare( $sql );
        $selectStmt->execute();
        $this->pagination->numRecords = $selectStmt->fetch( PDO::FETCH_OBJ )->count;
        $selectStmt->closeCursor();
    }

    /**
     * Populates the array of permissions of a given Role object
     *
     * @param RolesModel $role
     * @return RolesModel
     * @throws Exception
     */
    public function populateRolePerms( RolesModel $role ) {
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

    /**
     * @param $role
     * @param bool $overrideNullData
     * @param $oldPKValue
     *
     * @throws Exception
     */
    public function save( $role, $overrideNullData = false, $oldPKValue = null ) {
        // Start transaction: in case anything goes wrong when inserting,
        // we cancel the deletion that precedes it
        self::$_pdo->beginTransaction();

        try {
            self::$_pdo->prepare( "DELETE FROM role_perm WHERE role_id = ?" )->execute( array( $role->id ) );

            $stmt = self::$_pdo->prepare( "INSERT INTO role_perm (role_id, perm_desc) VALUES (:role_id, :perm_desc)" );

            // call parent method to save role
            parent::save( $role, true, $oldPKValue );

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

    /**
     * @param $role
     * @throws Exception
     */
    public function destroy( $role ) {
        if ( ! is_numeric( $role->id ) ) {
            throw new Exception( 'Não foi possível remover: chave primária sem valor!' );
        }

        // remove all role_perm and role_user entries
        $this->destroyRolePerm( $role );
        $this->destroyRoleUser( $role );

        parent::destroy( $role );
    }

    protected function destroyRolePerm( RolesModel $role ) {
        self::$_pdo->prepare( "DELETE FROM role_perm WHERE role_id = ?" )
            ->execute( array( $role->id ) );
    }

    protected function destroyRoleUser( RolesModel $role ) {
        self::$_pdo->prepare( "DELETE FROM user_role WHERE role_id = ?" )
            ->execute( array( $role->id ) );
    }
}
