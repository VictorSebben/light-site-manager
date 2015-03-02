<?php

abstract class Mapper {

    /**
     * PDO object for handling connections.
     *
     * @var PDO
     */
    protected static $_pdo;

    function __construct() {

        if ( !isset( self::$_pdo ) ) {

            $db_config = include 'include/inc.dbconfig.php';
            if ( is_null( $db_config ) ) {
                throw new Exception( 'No data specified for configuring the Dababase.' );
            }

            try {
                self::$_pdo = new PDO( "{$db_config['driver']}:host={$db_config['host']};dbname={$db_config['database']}",
                    $db_config[ 'username' ], $db_config[ 'password' ] );
                self::$_pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                self::$_pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, $db_config[ 'fetch' ] );
            } catch ( PDOException $e ) {
                die( $e->getMessage() );
            }
        }
    }

    function find( $id ) {
        $this->selectStmt()->execute( array( $id ) );
        $object = $this->selectStmt()->fetch();
        $this->selectStmt()->closeCursor();
        if ( ! is_object( $object ) ) { return null; }
        if ( ! $object->getId() == null ) { return null; }
        return $object;
    }
}
