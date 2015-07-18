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
    public $created_at;

    /**
     * The date at which a particular object was last
     * updated in the DB.
     *
     * @var DateTime
     */
    public $updated_at;

    /**
     * This property stores the name of the primary key column(s) of the Model.
     * It can be either a string or an array (in case it is a compound primary key).
     * It defaults to 'id'.
     * @var string|array
     */
    public $primaryKey = 'id';

    /**
     * The name of the table in the DataBase represented by the Model class.
     * @var string
     */
    public $tableName;

    public function __construct( $id = null ) {
        $this->_id = $id;
        $this->setTableName();
    }

    public function getId() {
        return $this->_id;
    }

    protected function setTableName() {
        // TODO pluralize
        $this->tableName = strtolower( str_replace( 'Model', '', get_class( $this ) ) ) . 's';
    }
}
