<?php

class UserMapper extends Mapper {

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
     * @return bool|UserModel
     */
    public function findByEmail( $email ) {
        $selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email, password, status FROM users WHERE email = :email"
        );

        $selectStmt->bindParam( ':email', $email, PDO::PARAM_STR, 64 );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'UserModel' );
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
            $sql .= 'AND name ~* :search
                      OR email ~* :search ';
        }

        $sql .= " ORDER BY {$ord} {$params['dir']}
                  LIMIT :lim
                 OFFSET :offset ";

        $selectStmt = self::$_pdo->prepare( $sql );
        if ( $this->request->pagParams[ 'search' ] != null ) {
            $selectStmt->bindParam( ':search', $this->request->pagParams[ 'search' ] );
        }
        $lim = 2;
        $selectStmt->bindParam( ':lim', $lim, PDO::PARAM_INT );
        $selectStmt->bindParam( ':offset', $offset, PDO::PARAM_INT );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'UserModel' );
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
            $sql .= 'AND name ~* :search
                      OR email ~* :search ';
        }

        $selectStmt = self::$_pdo->prepare($sql);
        if ( $this->request->pagParams['search'] != null ) {
            $selectStmt->bindParam( ':search', $this->request->pagParams['search'] );
        }
        $selectStmt->execute();
        $this->pagination->numRecords = $selectStmt->fetch( PDO::FETCH_OBJ )->count;
        $selectStmt->closeCursor();
    }

    public function initRoles( UserModel $user ) {
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
        $stmt->setFetchMode( PDO::FETCH_CLASS, 'RoleModel' );

        $user->roles = $stmt->fetchAll();
        $stmt->closeCursor();

        $this->initRolePermissions( $user );
    }

    protected function initRolePermissions( UserModel $user ) {
        $roleMapper = new RoleMapper();

        array_walk(
            $user->roles,
            function ( &$role, $key, $roleMapper ) {
                $roleMapper->populateRolePerms( $role );
            },
            $roleMapper
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

    protected function destroyUserRole( UserModel $user ) {
        self::$_pdo->prepare( "DELETE FROM user_role WHERE user_id = ?" )
            ->execute( array( $user->id ) );
    }

    /***** Ajax Methods *****/
    /**
     * @ajax
     * @param $userId
     * @return bool
     */
    public static function toggleStatus( $userId ) {
        if ( !is_numeric( $userId ) ) {
            return false;
        }

        try {
            $userMapper = new UserMapper();

            $userMapper->_selectStmt = self::$_pdo->prepare(
                "SELECT id, status FROM users WHERE id = ?"
            );
            $user = $userMapper->find( $userId );

            if ( ! $user ) return false;

            // toggle user's status
            $user->status = ( $user->status == 0 ) ? 1 : 0;
            $userMapper->save( $user );

            // if everything worked out, return true
            return true;
        } catch ( PDOException $e ) {
            echo $e->getMessage();
            return false;
        }
    }
}
