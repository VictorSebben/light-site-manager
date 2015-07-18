<?php

class UserMapper extends Mapper {

    function __construct( UserModel $model ) {
        parent::__construct( $model );
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email, cat_id FROM users WHERE id = ?"
        );
    }

    /**
     * @param $email
     * @return bool|UserModel
     */
    public function findByEmail( $email ) {
        $selectStmt = self::$_pdo->prepare(
            "SELECT id, email, password FROM users WHERE email = :email"
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

        $sql = "SELECT id, name, email, cat_id, status
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
}
