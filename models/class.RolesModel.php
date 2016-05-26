<?php

namespace lsm\models;

class RolesModel extends BaseModel {

    public $id;
    public $name;
    public $permissions;

    public function __construct() {
        parent::__construct();

        $this->permissions = array();

        $this->rules = array(
            "name" => array( 'fieldName' => 'nome', 'rules' => 'required|max:50' ),
        );
    }

    public function setPermission( $desc ) {
        $this->permissions[] = $desc;
    }

    public function hasPerm( $permDesc ) {
        return in_array( $permDesc, $this->permissions );
    }
}
