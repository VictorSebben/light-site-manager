<?php

class UserModel extends BaseModel {

    public $name;
    public $email;
    public $password;
    public $cat_id;
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
    public function getCategory( $getString = false ) {
        if ( !$getString ) {
            return $this->cat_id;
        }

        $catUser = ( new CatUsersMapper( new CatUsersModel() ) )->find( $this->cat_id );
        return $catUser->description;
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
}
