<?php

class CatUsersMapper extends Mapper {

    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, description FROM cat_users WHERE id = :id"
        );
    }
}
