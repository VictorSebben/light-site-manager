<?php

namespace lsm\mappers;

use PDO;
use PDOStatement;
use lsm\libs\Pagination;
use lsm\libs\Request;
use lsm\models\BaseModel;
use Exception;
use PDOException;

class Mapper {

    /**
     * PDO object for handling connections.
     *
     * @var PDO
     */
    protected static $_pdo;

    /**
     * Name of the database being used. This static variable is used
     *
     * @var string
     */
    protected static $_db;

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
     * @var string
     */
    public $modelName;

    /**
     * Default pagination and order by parameters
     * @var array
     */
    public $pagParams;

    public function __construct() {
        $this->request = Request::getInstance();

        $this->pagParams = [
            'ord' => 'id',
            'dir' => 'ASC',
        ];

        if ( ! isset( self::$_pdo ) ) {

            $db_config = include 'conf/inc.dbconfig.php';
            if ( is_null( $db_config ) ) {
                throw new Exception( 'No data specified for configuring the Dababase.' );
            }

            try {
                $arrAttrs = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_CLASS,
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

                self::$_db = $db_config[ 'driver' ];
            } catch ( PDOException $e ) {
                die( $e->getMessage() );
            }
        }

        $this->setModelName();
    }

    public function rawQuery( $query, array $params = array(), $fetchMode = PDO::FETCH_ASSOC ) {
        $stmt = self::$_pdo->prepare( $query );
        $stmt->execute( $params );
        return $stmt->fetchAll( $fetchMode );
    }

    public function find( $id ) {
        $this->selectStmt()->execute( array( $id ) );
        $this->selectStmt()->setFetchMode( PDO::FETCH_CLASS, $this->modelName );
        $object = $this->selectStmt()->fetch();
        $this->selectStmt()->closeCursor();

        if ( ! is_object( $object ) ) { return null; }

        return $object;
    }

    public function selectStmt( $stmt = null ) {
        if ( ! $stmt )
            return $this->_selectStmt;
        else
            $this->_selectStmt = self::$_pdo->prepare( $stmt );
    }

    protected function setModelName() {
        $this->modelName = str_replace( array( 'Mapper', 'mappers' ), array( 'Model', 'models' ), get_class( $this ) );
    }

    /**
     * Mapper::save() will first look in the DB for an entry with the
     * primary key value of the object, if set. In case an entry is found,
     * it will be updated. In case the primary key is not set or an entry
     * is not found, and INSERT operation will be done.
     *
     * The boolean parameter $overrideNullData will only be considered in
     * UPDATE operations. If set to TRUE, null values in the object WILL
     * be used to update the corresponding columns.
     *
     * If $oldPKValue is given, the save method will override the Primary Key
     * and with the value in the object, using the $oldPKValue in the where
     * clause. The $oldPKValue is, therefore, used in the update operations
     * exclusively.
     *
     * @param $obj
     * @param bool|false $overrideNullData
     * @param int $oldPKValue
     * @throws Exception
     */
    public function save( $obj, $overrideNullData = false, $oldPKValue = null ) {
        if ( ! isset( $obj->tableName ) || ! $obj->tableName ) {
            throw new Exception( 'Object given to Mapper::save() is not a valid Model' );
        }

        if ( ! is_array( $obj->primaryKey ) ) {
            $arrPrimaryKey = array( $obj->primaryKey );
        } else {
            $arrPrimaryKey = $obj->primaryKey;
        }

        $hasPKValues = true;

        $sql = "SELECT * FROM {$obj->tableName} WHERE true ";

        foreach ( $arrPrimaryKey as $key ) {
            if ( ! empty( $obj->$key ) ) {
                $sql .= " AND {$key} = :{$key} ";
            } else {
                $hasPKValues = false;
            }
        }

        if ( ! $hasPKValues ) {
            $sql .= " LIMIT 0 ";
        }

        $stmt = self::$_pdo->prepare( $sql );

        if ( $hasPKValues ) {
            foreach ( $arrPrimaryKey as $key ) {
                $stmt->bindParam( ":{$key}", $obj->$key );
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
        if ( $rowCount || $oldPKValue ) {
            $this->performUpdate( $obj, $arrColMeta, $overrideNullData, $oldPKValue );
        } else {
            // perform insert operation
            $this->performInsert( $obj, $arrColMeta );
        }
    }

    /**
     * Performs and update DB operation on a Model object.
     * If $overrideNullData is set to true, the null values
     * in the object will be ignored (they'll be left as they
     * are in the database. If it is set to true, null values
     * will be inserted in the database.
     *
     * @param BaseModel $obj
     * @param array $arrColMeta
     * @param $overrideNullData
     * @param $oldPKValue
     * 
     * @throws Exception
     */
    protected function performUpdate( BaseModel $obj, array $arrColMeta, $overrideNullData = false, $oldPKValue = null ) {
        if ( $obj->updated_at === null ) {
            $obj->updated_at = date( 'Y-m-d G:i:s' );
        }

        // initialize array that will contain the column names to be updated.
        // It is used to avoid testing for $overrideNullData when binding
        // parameters later
        $arrUpdatedCols = array();

        $sql = "UPDATE {$obj->tableName} SET ";

        // Create array with the name of the column(s) that compose the primary key
        if ( ! is_array( $obj->primaryKey ) ) {
            $primaryKeys = array( $obj->primaryKey );
        } else {
            $primaryKeys = $obj->primaryKey;
        }

        // if $overrideNullData is set to true, update database according
        // to the current state of the object, including null data.
        if ( $overrideNullData ) {
            for ( $i = 0; $i < count( $arrColMeta[ 'names' ] ); $i++ ) {
                $colName = $arrColMeta[ 'names' ][ $i ];

                if ( ( $colName == 'created_at' ) || ( ! $oldPKValue && in_array( $colName, $primaryKeys ) ) ) continue;

                $arrUpdatedCols[] = array( 'name' => $colName,
                                           'type' => $arrColMeta[ 'pdo_type' ][ $i ] );

                $sql .= "{$colName} = :{$colName}, ";
            }
        }
        // otherwise, update only the properties that have value
        else {
            for ( $i = 0; $i < count( $arrColMeta[ 'names' ] ); $i++ ) {
                $colName = $arrColMeta[ 'names' ][ $i ];

                if ( ( $colName == 'created_at' ) || ( ! $oldPKValue && in_array( $colName, $primaryKeys ) ) ) continue;

                if ( isset( $obj->$colName ) ) {
                    $arrUpdatedCols[] = array( 'name' => $colName,
                                               'type' => $arrColMeta[ 'pdo_type' ][ $i ] );

                    $sql .= "{$colName} = :{$colName}, ";
                }
            }
        }

        $sql = preg_replace( '/, $/', '', $sql );

        $sql .= " WHERE TRUE";

        // Build where clause using the PK values from the Model object
        foreach ( $primaryKeys as $pk ) {
            $sql .= " AND {$pk} = :where_{$pk}";
        }

        // Prepare query and bind parameters:
        $stmt = self::$_pdo->prepare( $sql );

        // bind updated values
        for ($i = 0; $i < count( $arrUpdatedCols ); $i++ ) {
            $colName = $arrUpdatedCols[ $i ][ 'name' ];
            $stmt->bindParam( $colName, $obj->$colName, $arrUpdatedCols[ $i ][ 'type' ] );
        }

        if ( $oldPKValue ) {
            if ( ! is_array( $oldPKValue ) ) {
                if ( count( $primaryKeys ) > 1 ) {
                    throw new Exception( 'Too little PK values given to Mapper::save()!' );
                }

                $pk = array_shift( $primaryKeys );
                $stmt->bindParam( "where_{$pk}", $oldPKValue );
            } else {
                if ( count( $primaryKeys ) != count( $oldPKValue ) ) {
                    throw new Exception( 'Too little PK values given to Mapper::save()!' );
                }

                foreach ( $primaryKeys as $pk ) {
                    if ( ! isset( $oldPKValue[ $pk ] ) ) {
                        throw new Exception( "PK value {$pk} not given to Mapper::save()!" );
                    }

                    $stmt->bindParam( "where_{$pk}", $oldPKValue[ $pk ] );
                }
            }
        } else {
            // bind where values
            foreach ( $primaryKeys as $pk ) {
                $stmt->bindParam( "where_{$pk}", $obj->$pk );
            }
        }

        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * Performs an insert DB operation for a Model object.
     *
     * @param BaseModel $obj
     * @param array $arrColMeta
     * @throws Exception
     * @throws PDOException
     */
    protected function performInsert( BaseModel $obj, array $arrColMeta ) {
        if ( $obj->created_at === null ) {
            $obj->created_at = date( 'Y-m-d G:i:s' );
        }

        if ( $obj->updated_at === null ) {
            $obj->updated_at = date( 'Y-m-d G:i:s' );
        }

        $sql = "INSERT INTO {$obj->tableName} (";
        $values = '';

        for ( $i = 0; $i < count( $arrColMeta[ 'names' ] ); $i++ ) {
            $colName = $arrColMeta[ 'names' ][ $i ];

            if ( isset( $obj->$colName ) ) {
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

            if ( isset( $obj->$colName ) ) {
                $stmt->bindParam( ":{$colName}", $obj->$colName, $arrColMeta[ 'pdo_type' ][ $i ] );
            }
        }

        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * @param $obj
     * @throws Exception
     */
    public function destroy( $obj ) {
        if ( ! isset( $obj->tableName ) || ! $obj->tableName ) {
            throw new Exception( 'Object given to Mapper::save() is not a valid Model' );
        }

        if ( !is_array( $obj->primaryKey ) ) {
            $arrPrimaryKey = array( $obj->primaryKey );
        } else {
            $arrPrimaryKey = $obj->primaryKey;
        }

        foreach ( $arrPrimaryKey as $key ) {
            if ( empty( $obj->$key ) ) {
                throw new Exception( 'Não foi possível remover: chave primária sem valor!' );
            }
        }

        // build where clause using the PK values from the Model object
        if ( !is_array( $obj->primaryKey ) ) {
            $primaryKeys = array( $obj->primaryKey );
        } else {
            $primaryKeys = $obj->primaryKey;
        }

        $where = "WHERE TRUE ";

        foreach ( $primaryKeys as $pk ) {
            $where .= " AND {$pk} = :{$pk}";
        }

        $stmt = self::$_pdo->prepare(
            "DELETE FROM {$obj->tableName} {$where}"
        );

        foreach ( $primaryKeys as $pk ) {
            $stmt->bindParam( $pk, $obj->$pk );
        }

        $stmt->execute();
        $stmt->closeCursor();
    }
}
