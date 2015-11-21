<?php

class BaseModel {

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
     * Array specifying the rules for form validation.
     * This array will be passed to the Validator class
     * upon performing a DB operation.
     *
     * @var array
     */
    public $rules;

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

    public function __construct() {
        $this->setTableName();
    }

    protected function setTableName() {
        // TODO pluralize
        $this->tableName = strtolower( str_replace( 'Model', '', get_class( $this ) ) ) . 's';
    }
}
