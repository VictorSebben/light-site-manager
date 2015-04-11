<?php

class CatUsersMapper extends Mapper {

    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, description FROM cat_users WHERE id = :id"
        );
    }

    public function find( $id ) {
        $this->_selectStmt->bindParam( ':id', $id );
        $this->_selectStmt->execute();
        $this->_selectStmt->setFetchMode( PDO::FETCH_CLASS, 'CatUsersModel' );
        $catUser = $this->_selectStmt->fetch();
        $this->_selectStmt->closeCursor();

        if ( !$catUser ) return null;

        return $catUser;
    }
}
