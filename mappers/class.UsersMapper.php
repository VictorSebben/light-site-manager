<?php

namespace lsm\mappers;

use lsm\models\UsersModel;
use PDO;
use Exception;
use PDOException;

class UsersMapper extends Mapper {

    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email, status FROM users WHERE id = ?"
        );
    }

    /**
     * @param $email
     * @return bool|UsersModel
     */
    public function findByEmail( $email ) {
        $selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email, password, status FROM users WHERE email = :email"
        );

        $selectStmt->bindParam( ':email', $email, PDO::PARAM_STR, 64 );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'lsm\models\UsersModel' );
        $users = $selectStmt->fetch();
        $selectStmt->closeCursor();

        if ( $selectStmt->rowCount() != 1 ) {
            return false;
        }

        return $users;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function show( $id ) {
        return $this->find( $id );
    }

    /**
     * @return array|null
     */
    public function index() {
        // Get pagination parameters from the request (the pagn. parameters that were in the URL)
        $params = $this->request->pagParams;

        // If order and direction were not specified in the route, get default values
        // from the configuration array in the mapper itself
        if ( ! $params[ 'ord' ] ) {
            $params[ 'ord' ] = $this->pagParams[ 'ord' ];
        }

        if ( ! $params[ 'dir' ] ) {
            $params[ 'dir' ] = $this->pagParams[ 'dir' ];
        }

        $offset = $this->pagination->getOffset();

        // validate $params[ 'dir' ] to make sure it contains a valid value
        if ( $params[ 'dir' ] !== 'ASC' && $params[ 'dir' ] !== 'DESC' ) {
            $params[ 'dir' ] = 'ASC';
        }

        $ord = 'id';
        $rs = self::$_pdo->query( 'SELECT * FROM users LIMIT 0' );
        for ( $i = 0; $i < $rs->columnCount(); $i++ ) {
            if ( $rs->getColumnMeta( $i )[ 'name' ] == $params[ 'ord' ] ) {
                $ord = $params[ 'ord' ];
                break;
            }
        }

        // Set number of records in the pagination object
        $this->_setNumRecordsPagn();

        $sql = "SELECT id, name, email, status
                  FROM users
                 WHERE deleted = 0 ";

        if ( $this->request->pagParams[ 'search' ] != null ) {
            if ( self::$db == 'pgsql' ) {
                $sql .= 'AND (name ILIKE :search
                              OR email ILIKE :search) ';
            } else {
                $sql .= 'AND (name LIKE :search
                              OR email LIKE :search) ';
            }
        }

        $sql .= " ORDER BY {$ord} {$params['dir']}
                  LIMIT :lim
                 OFFSET :offset ";

        $selectStmt = self::$_pdo->prepare( $sql );
        if ( $this->request->pagParams[ 'search' ] != null ) {
            $search = "%{$this->request->pagParams[ 'search' ]}%";
            $selectStmt->bindParam( ':search', $search );
        }
        $lim = $this->pagination->getLimit();
        $selectStmt->bindParam( ':lim', $lim, PDO::PARAM_INT );
        $selectStmt->bindParam( ':offset', $offset, PDO::PARAM_INT );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\models\UsersModel' );
        $users = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        if ( !is_array( $users ) ) return null;

        return $users;
    }

    protected function _setNumRecordsPagn() {
        $sql = "SELECT count(*) AS count
                  FROM users
                 WHERE deleted = 0 ";

        if ( $this->request->pagParams['search'] != null ) {
            if ( self::$db == 'pgsql' ) {
                $sql .= 'AND (name ILIKE :search
                              OR email ILIKE :search) ';
            } else {
                $sql .= 'AND (name LIKE :search
                              OR email LIKE :search) ';
            }
        }

        $selectStmt = self::$_pdo->prepare($sql);
        if ( $this->request->pagParams[ 'search' ] != null ) {
            $search = "%{$this->request->pagParams[ 'search' ]}%";
            $selectStmt->bindParam( ':search', $search );
        }
        $selectStmt->execute();
        $this->pagination->numRecords = $selectStmt->fetch( PDO::FETCH_OBJ )->count;
        $selectStmt->closeCursor();
    }

    /**
     * @param UsersModel $user
     * @throws Exception
     */
    public function initRoles( UsersModel $user ) {
        $user->roles = array();

        if ( ! $user->id ) {
            throw new Exception( "Could not retrieve roles for user: user id not specified!" );
        }

        $sql = "SELECT role_id AS id, name
                  FROM user_role
                  JOIN roles ON role_id = id
                 WHERE user_id = :user_id";

        $stmt = self::$_pdo->prepare( $sql );
        $stmt->bindParam( ':user_id', $user->id, PDO::PARAM_INT );
        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\models\RolesModel' );

        $user->roles = $stmt->fetchAll();
        $stmt->closeCursor();

        $this->initRolePermissions( $user );
    }

    /**
     * @param UsersModel $user
     */
    protected function initRolePermissions( UsersModel $user ) {
        $rolesMapper = new RolesMapper();

        array_walk(
            $user->roles,
            function ( &$role, $key, $rolesMapper ) {
                $rolesMapper->populateRolePerms( $role );
            },
            $rolesMapper
        );
    }

    /**
     * @param $user
     * @throws Exception
     */
    public function destroy( $user ) {
        if ( ! is_numeric( $user->id ) ) {
            throw new Exception( 'Não foi possível remover: chave primária sem valor!' );
        }

        self::$_pdo->beginTransaction();

        try {
            // Remove all user_role entries
            $this->destroyUserRole( $user );

            // Actually, we are not going to remove the user
            // from the database. Instead, we will mark her
            // as deleted, so that she can be audited later
            // if necessary
            $user->deleted = 1;
            $this->save( $user );

            self::$_pdo->commit();
        } catch ( Exception $e ) {
            // If something went wrong, rollback transaction
            // and throw a new exception to be caught in the
            // Router class
            self::$_pdo->rollBack();
            throw new Exception( $e->getMessage() );
        }
    }

    protected function destroyUserRole( UsersModel $user ) {
        self::$_pdo->prepare( "DELETE FROM user_role WHERE user_id = ?" )
            ->execute( array( $user->id ) );
    }

    /***** Ajax Methods *****/
    /**
     * @ajax
     * @param $userId
     * @return bool
     */
    public function toggleStatus( $userId ) {
        if ( !is_numeric( $userId ) ) {
            return false;
        }

        try {
            $this->_selectStmt = self::$_pdo->prepare(
                "SELECT id, status FROM users WHERE id = ?"
            );
            $user = $this->find( $userId );

            if ( ! $user ) return false;

            // toggle user's status
            $user->status = ( $user->status == 0 ) ? 1 : 0;
            $this->save( $user );

            // if everything worked out, return true
            return true;
        } catch ( PDOException $e ) {
            echo $e->getMessage();
            return false;
        }
    }

    public function activate( $userIds ) {
        return $this->_toggleStatusArr( $userIds, 1 );
    }

    public function deactivate( $userIds ) {
        return $this->_toggleStatusArr( $userIds, 0 );
    }

    private function _toggleStatusArr( $userIds, $status ) {
        try {
            $sql = "UPDATE users SET status = {$status} WHERE id IN (";

            foreach ( $userIds as $id ) {
                $sql .= '?, ';
            }

            $sql = trim( $sql, ', ' ) . ')';
            $stmt = self::$_pdo->prepare( $sql );
            $stmt->execute( $userIds );

            // If everything worked out, return true
            return true;
        } catch ( Exception $e ) {
            echo $e->getMessage();
            return false;
        }
    }

    public function deleteAjax( $userIds ) {
        try {
            $sql = 'UPDATE users SET deleted = 1 WHERE id IN (';

            foreach ( $userIds as $id ) {
                $sql .= '?, ';
            }

            $sql = trim( $sql, ', ' ) . ')';
            $stmt = self::$_pdo->prepare( $sql );
            $stmt->execute( $userIds );

            // If everything worked out, return true
            return true;
        } catch ( PDOException $e ) {
            echo $e->getMessage();
            return false;
        }
    }
}
