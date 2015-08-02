<?php

class UserModel extends BaseModel {

    public $name;
    public $email;
    public $password;
    public $status;

    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 0;

    public static $statusString = [
        self::STATUS_ACTIVE => 'Ativo',
        self::STATUS_INACTIVE => 'Inativo'
    ];

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
}
