<?php

abstract class Mapper {

    /**
     * PDO object for handling connections.
     *
     * @var PDO
     */
    protected static $_pdo;

    /**
     * Prepared statement to be executed by PDO.
     *
     * @var PDOStatement
     */
    protected $_selectStmt;

    /**
     * An instance of the Pagination class.
     *
     * @var Pagination
     */
    public $pagination;

    /**
     * An instance of the Request class.
     *
     * @var Request
     */
    public $request;

    /**
     * An instance of the Model class.
     *
     * @var BaseModel
     */
    public $model;

    public function __construct( BaseModel $model ) {

        $this->request = Request::getInstance();

        if ( !isset( self::$_pdo ) ) {

            $db_config = include '../conf/inc.dbconfig.php';
            if ( is_null( $db_config ) ) {
                throw new Exception( 'No data specified for configuring the Dababase.' );
            }

            try {
                $arrAttrs = [
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => $db_config[ 'fetch' ],
                ];

                $dsn = sprintf(
                    "%s:host=%s;dbname=%s",
                    $db_config['driver'],
                    $db_config['host'],
                    $db_config['database']
                );

                self::$_pdo = new PDO(
                    $dsn,
                    $db_config[ 'username' ],
                    $db_config[ 'password' ],
                    $arrAttrs
                );
            } catch ( PDOException $e ) {
                die( $e->getMessage() );
            }

            $this->model = $model;
        }
    }

    public function find( $id ) {
        $this->selectStmt()->execute( array( $id ) );
        $this->selectStmt()->setFetchMode( PDO::FETCH_CLASS, 'UserModel' );
        $object = $this->selectStmt()->fetch();
        $this->selectStmt()->closeCursor();

        if ( ! is_object( $object ) ) { return null; }
        if ( ! $object->getId() == null ) { return null; }

        return $object;
    }

    public function selectStmt() {
        return $this->_selectStmt;
    }

    /**
     * Mapper::save() will first look in the DB for an entry with the
     * primary key value of the object, if set. In case an entry is found,
     * it will be updated. In case the primary key is not set or an entry
     * is not found, and INSERT operation will be done.
     *
     * The boolean parameter $overrideNullData will only be considered
     * UPDATE operations. If set to TRUE, null values in the object WILL
     * be used to update the corresponding columns.
     *
     * @param bool|false $overrideNullData
     */
    public function save( $overrideNullData = false ) {
        if ( !is_array( $this->model->primaryKey ) ) {
            $arrPrimaryKey = array( $this->model->primaryKey );
        } else {
            $arrPrimaryKey = $this->model->primaryKey;
        }

        $hasPKValues = true;

        $sql = "SELECT * FROM {$this->model->tableName} WHERE true ";

        foreach ( $arrPrimaryKey as $key ) {
            if ( ! empty( $this->model->$key ) ) {
                $sql .= " AND {$key} = :{$key} ";
            } else {
                $hasPKValues = false;
            }
        }

        if ( !$hasPKValues ) {
            $sql .= " LIMIT 0 ";
        }

        $stmt = self::$_pdo->prepare( $sql );

        if ( $hasPKValues ) {
            foreach ( $arrPrimaryKey as $key ) {
                $stmt->bindParam( ":{$key}", $this->model->$key );
            }
        }

        $stmt->execute();
        $rowCount = $stmt->rowCount();
        $arrColMeta = array();
        for ( $i = 0; $i < $stmt->columnCount(); $i++ ) {
            $arrColMeta[ 'names' ][] = $stmt->getColumnMeta( $i )[ 'name' ];
            $arrColMeta[ 'pdo_type' ][] = $stmt->getColumnMeta( $i )[ 'pdo_type' ];
        }
        $stmt->closeCursor();

        // check if there already is an entry in the DB with this
        // (set of) value(s) for the primary key
        // if there is, perform an update operation
        if ( $rowCount ) {
            $this->performUpdate( $arrColMeta, $overrideNullData );
        } else {
            // perform insert operation
            $this->performInsert( $arrColMeta );
        }
    }

    /**
     * Performs and update DB operation on a Model object.
     * If $overrideNullData is set to true, the null values
     * in the object will be ignored (they'll be left as they
     * are in the database. If it is set to true, null values
     * will be inserted in the database.
     *
     * @param array $arrColMeta
     * @param $overrideNullData
     */
    protected function performUpdate( array $arrColMeta, $overrideNullData = false ) {
        if ( $this->model->updated_at === null ) {
            $this->model->updated_at = date( 'Y-m-d G:i:s' );
        }

        // initialize array that will contain the column names to be updated.
        // It is used to avoid testing for $overrideNullData when binding
        // parameters later
        $arrUpdatedCols = array();

        $sql = "UPDATE {$this->model->tableName} SET ";

        // if $overrideNullData is set to true, update database according
        // to the current state of the object, including null data.
        if ( $overrideNullData ) {
            for ( $i = 0; $i < count( $arrColMeta[ 'names' ] ); $i++ ) {
                $colName = $arrColMeta[ 'names' ][ $i ];

                if ( $colName == 'created_at' ) continue;

                $arrUpdatedCols[] = array( 'name' => $colName,
                                           'type' => $arrColMeta[ 'pdo_type' ][ $i ] );

                $sql .= "{$colName} = :{$colName}, ";
            }
        }
        // otherwise, update only the properties that have value
        else {
            for ( $i = 0; $i < count( $arrColMeta[ 'names' ] ); $i++ ) {
                $colName = $arrColMeta[ 'names' ][ $i ];

                if ( $colName == 'created_at' ) continue;

                if ( isset( $this->model->$colName ) ) {
                    $arrUpdatedCols[] = array( 'name' => $colName,
                                               'type' => $arrColMeta[ 'pdo_type' ][ $i ] );

                    $sql .= "{$colName} = :{$colName}, ";
                }
            }
        }

        $sql = preg_replace( '/, $/', '', $sql );

        $sql .= " WHERE TRUE";

        // build where clause using the PK values from the Model object
        if ( !is_array( $this->model->primaryKey ) ) {
            $primaryKeys = array( $this->model->primaryKey );
        } else {
            $primaryKeys = $this->model->primaryKey;
        }

        foreach ( $primaryKeys as $pk ) {
            $sql .= " AND {$pk} = :{$pk}";
        }

        // Prepare query and bind parameters:
        $stmt = self::$_pdo->prepare( $sql );

        // bind updated values
        for ($i = 0; $i < count( $arrUpdatedCols ); $i++ ) {
            $colName = $arrUpdatedCols[ $i ][ 'name' ];
            $stmt->bindParam( $colName, $this->model->$colName, $arrUpdatedCols[ $i ][ 'type' ] );
        }

        // bind where values
        foreach ( $primaryKeys as $pk ) {
            $stmt->bindParam( $pk, $this->model->$pk );
        }

        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * Performs an insert DB operation for a Model object.
     *
     * @param array $arrColMeta
     * @throws Exception
     * @throws PDOException
     */
    protected function performInsert( array $arrColMeta ) {
        if ( $this->model->created_at === null ) {
            $this->model->created_at = date( 'Y-m-d G:i:s' );
        }

        if ( $this->model->updated_at === null ) {
            $this->model->updated_at = date( 'Y-m-d G:i:s' );
        }

        $sql = "INSERT INTO {$this->model->tableName} (";
        $values = '';

        for ( $i = 0; $i < count( $arrColMeta[ 'names' ] ); $i++ ) {
            $colName = $arrColMeta[ 'names' ][ $i ];

            if ( isset( $this->model->$colName ) ) {
                $sql .= "{$colName}, ";
                $values .= ":{$colName}, ";
            }
        }

        if ( strlen( $values ) === 0 ) {
            throw new Exception( "Informações insuficientes para inserção do usuário!" );
        }

        $sql = preg_replace( '/, $/', ')', $sql );
        $sql .= " VALUES (" . preg_replace( '/, $/', ')', $values );

        $stmt = self::$_pdo->prepare( $sql );

        for ( $i = 0; $i < count( $arrColMeta[ 'names' ] ); $i++ ) {
            $colName = $arrColMeta[ 'names' ][ $i ];

            if ( isset( $this->model->$colName ) ) {
                $stmt->bindParam( ":{$colName}", $this->model->$colName, $arrColMeta[ 'pdo_type' ][ $i ] );
            }
        }

        $stmt->execute();
        $stmt->closeCursor();
    }
}
