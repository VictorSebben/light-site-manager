<?php

class BaseModel {

    /**
     * The id that identifies the object (and the row in the database).
     *
     * @var integer
     */
    public $id;

    /**
     * The date at which a particular object was inserted
     * in the DB.
     *
     * @var DateTime
     */
    public $createdAt;

    /**
     * The date at which a particular object was last
     * updated in the DB.
     *
     * @var DateTime
     */
    public $updatedAt;

    public function __construct( $id = null ) {
        $this->_id = $id;
    }

    public function getId() {
        return $this->_id;
    }
}
