<?php

class UserMapper extends Mapper {

    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email, cat_id FROM users WHERE id = ?"
        );
    }

    public function show( $id ) {
        return $this->find( $id );
    }

    public function index() {
        $selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email, cat_id, status FROM users"
        );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'UserModel' );
        $users = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        if ( !is_array( $users ) ) return null;

        return $users;
    }

}
