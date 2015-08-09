<?php

class UserMapper extends Mapper {

    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email FROM users WHERE id = ?"
        );
    }

    /**
     * @param $email
     * @return bool|UserModel
     */
    public function findByEmail( $email ) {
        $selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email, password FROM users WHERE email = :email"
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

        // set number of records in the pagination object
        $this->_setNumRecordsPagn();

        $sql = "SELECT id, name, email, status
                  FROM users ";

        if ( $this->request->pagParams['search'] != null ) {
            $sql .= 'WHERE name ~* :search
                        OR email ~* :search ';
        }

        $sql .= " ORDER BY {$ord} {$params['dir']}
                  LIMIT :lim
                 OFFSET :offset ";

        $selectStmt = self::$_pdo->prepare( $sql );
        if ( $this->request->pagParams['search'] != null ) {
            $selectStmt->bindParam( ':search', $this->request->pagParams['search'] );
        }
        $lim = 2;
        $selectStmt->bindParam( ':lim', $lim, PDO::PARAM_INT );
        $selectStmt->bindParam( ':offset', $this->pagination->getOffset(), PDO::PARAM_INT );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'UserModel' );
        $users = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        if ( !is_array( $users ) ) return null;

        return $users;
    }

    protected function _setNumRecordsPagn() {
        $sql = "SELECT count(*) AS count
                  FROM users ";

        if ( $this->request->pagParams['search'] != null ) {
            $sql .= 'WHERE name ~* :search
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

        $sql = "SELECT role_id, name
                  FROM user_role
                  JOIN roles ON role_id = id
                 WHERE user_id = :user_id";

        $stmt = self::$_pdo->prepare( $sql );
        $stmt->bindParam( ':user_id', $this->model->id, PDO::PARAM_INT );
        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_CLASS, 'RoleModel' );

        $user->roles = $stmt->fetchAll();
        $stmt->closeCursor();
    }

    /**
     * @param UserModel $model
     * @param bool|false $overrideNullData
     */
    public function save( UserModel $model, $overrideNullData = false ) {
        parent::save( $model, $overrideNullData );
    }
}
