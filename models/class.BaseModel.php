<?php

class BaseModel {

    /**
     * The id that identifies the object (and the row in the database).
     *
     * @var integer
     */
    protected $_id;

    public function __construct( $id = null ) {
        $this->_id = $id;
    }

    public function getId() {
        return $this->_id;
    }
}
