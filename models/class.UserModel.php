<?php

class UserModel extends BaseModel {

    public $id;
    public $name;
    public $email;
    public $password;
    public $status;
    public $deleted;

    /**
     * Array to hold RoleModel objects.
     * @var array
     */
    public $roles;

    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 0;

    public static $statusString = [
        self::STATUS_ACTIVE => 'Ativo',
        self::STATUS_INACTIVE => 'Inativo'
    ];

    public function getId() {
        return $this->id;
    }

    /**
     * @param bool $getString
     * @return mixed
     */
    public function getStatus( $getString = false ) {
        if ( !$getString ) {
            return $this->status;
        }

        return self::$statusString[ $this->status ];
    }

    // TODO REFACTOR SYSTEM TO MAKE THE PERMISSION NAME (WHICH IS UNIQUE) THE PRIMARY KEY
    public function hasPrivilege( $permDesc ) {
        foreach ( $this->roles as $role ) {
            if ( $role->hasPerm( $permDesc ) ) {
                return true;
            }
        }

        return false;
    }
}
