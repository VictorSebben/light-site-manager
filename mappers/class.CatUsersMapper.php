<?php

class CatUsersMapper extends Mapper {

    function __construct( CatUsersModel $model ) {
        parent::__construct( $model );
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, description FROM cat_users WHERE id = :id"
        );
    }
}
