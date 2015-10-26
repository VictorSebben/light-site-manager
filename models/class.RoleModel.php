<?php

class RoleModel extends BaseModel {

    public $id;
    public $name;
    public $permissions;

    public function __construct() {
        parent::__construct();

        $this->permissions = array();

        $this->rules = array(
            "name" => array( 'fieldName' => 'nome', 'rules' => 'required|max:50' ),
            "perms" => array( 'fieldName' => 'permissão', 'type' => Validator::NUMERIC_INT )
        );
    }

    public function setPermission( $id ) {
        $this->permissions[] = $id;
    }

    public function hasPerm( $permId ) {
        return in_array( $permId, $this->permissions );
    }
}
