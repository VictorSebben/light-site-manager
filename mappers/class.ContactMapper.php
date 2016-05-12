<?php

class ContactMapper extends Mapper {

    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, name, email, phone, message, status FROM contact WHERE id = ?"
        );
    }

    /**
     * @return array|null
     */
    public function index() {
        // Set additional parameters for the pagination
        // in the request object
        $this->request->setPagParams();
        $params = $this->request->pagParams;

        $offset = $this->pagination->getOffset();

        // validate $params[ 'dir' ] to make sure it contains a valid value
        if ( $params[ 'dir' ] !== 'ASC' && $params[ 'dir' ] !== 'DESC' ) {
            $params[ 'dir' ] = 'ASC';
        }

        $ord = 'id';
        $rs = self::$_pdo->query( 'SELECT * FROM contact LIMIT 0' );
        for ( $i = 0; $i < $rs->columnCount(); $i++ ) {
            if ( $rs->getColumnMeta( $i )[ 'name' ] == $params[ 'ord' ] ) {
                $ord = $params[ 'ord' ];
                break;
            }
        }

        // Set number of records in the pagination object
        $this->_setNumRecordsPagn();

        $sql = "SELECT id, name, email, phone, message, status
                  FROM contact c
                 WHERE TRUE ";

        // Search category by either name or description
        if ( $this->request->pagParams[ 'search' ] != null ) {
            if ( self::$_db === 'pgsql' ) {
                $sql .= 'AND unaccent(name) ILIKE unaccent(:search)
                          OR unaccent(email) ILIKE unaccent(:search)
                          OR unaccent(message) ILIKE unaccent(:search) ';
            } else {
                $sql .= 'AND name ILIKE :search
                          OR email ILIKE :search
                          OR unaccent(message) ILIKE unaccent(:search) ';
            }
        }

        $sql .= " ORDER BY {$ord} {$params['dir']}
                  LIMIT :lim
                 OFFSET :offset ";

        $selectStmt = self::$_pdo->prepare( $sql );
        if ( $this->request->pagParams[ 'search' ] != null ) {
            $search = "%{$this->request->pagParams[ 'search' ]}%";
            $selectStmt->bindParam( ':search', $search );
        }
        $lim = $this->pagination->getLimit();
        $selectStmt->bindParam( ':lim', $lim, PDO::PARAM_INT );
        $selectStmt->bindParam( ':offset', $offset, PDO::PARAM_INT );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'ContactModel' );
        $contacts = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        if ( ! is_array( $contacts ) ) return null;

        return $contacts;
    }

    protected function _setNumRecordsPagn() {
        $sql = "SELECT count(*) AS count
                  FROM contact
                 WHERE TRUE ";

        if ( $this->request->pagParams['search'] != null ) {
            $sql .= 'AND name ~* :search
                      OR description ~* :search ';
        }

        $selectStmt = self::$_pdo->prepare($sql);
        if ( $this->request->pagParams['search'] != null ) {
            $selectStmt->bindParam( ':search', $this->request->pagParams['search'] );
        }
        $selectStmt->execute();
        $this->pagination->numRecords = $selectStmt->fetch( PDO::FETCH_OBJ )->count;
        $selectStmt->closeCursor();
    }
}
