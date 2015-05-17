<?php

class UserMapper extends Mapper {

    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email, cat_id FROM users WHERE id = ?"
        );
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

        // set number of records in the pagination object
        $this->_setNumRecordsPagn();

        $sql = "SELECT id, name, email, cat_id, status
                  FROM users ";

        if ( $this->request->pagParams['search'] != null ) {
            $sql .= 'WHERE name ~* :search
                        OR email ~* :search ';
        }

        // TODO -> research on how to make order string and direction string safe
        $sql .= " ORDER BY {$params[ 'ord' ]} {$params['dir']}
                  LIMIT 2
                 OFFSET :offset ";

        $selectStmt = self::$_pdo->prepare($sql);
        if ( $this->request->pagParams['search'] != null ) {
            $selectStmt->bindParam( ':search', $this->request->pagParams['search'] );
        }
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
