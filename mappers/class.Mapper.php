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

    public function __construct() {

        $this->request = Request::getInstance();

        if ( !isset( self::$_pdo ) ) {

            $db_config = include 'conf/inc.dbconfig.php';
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
}
