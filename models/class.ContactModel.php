<?php

class ContactModel extends BaseModel {

    public $id;
    public $name;
    public $email;
    public $phone;
    public $message;
    public $status;

    public $tableName;

    public function __construct() {
        parent::__construct();

        $this->tableName = 'contact';
    }
}
