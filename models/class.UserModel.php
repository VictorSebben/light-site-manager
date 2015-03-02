<?php

class UserModel extends BaseModel {

    protected $_id;

    protected $_name;

    public function index() {
        $user1 = new UserModel();
        $user1->_id = 1;
        $user1->_name = 'foobar';

        $user2 = new UserModel();
        $user2->_id = 2;
        $user2->_name = 'lorem-ipsum';

        return array($user1, $user2);
    }
}
