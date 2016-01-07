<?php

class CategoryMapper extends Mapper {

    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, name, description FROM categories WHERE id = ?"
        );
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function show( $id ) {
        return $this->find( $id );
    }

    /**
     * @return array|null
     */
    public function index() {

        // set additional parameters for the pagination
        // in the request object
        $this->request->setPagParams();
        $params = $this->request->pagParams;

        // validate $params[ 'dir' ] to make sure it contains a valid value
        if ( $params[ 'dir' ] !== 'ASC' && $params[ 'dir' ] !== 'DESC' ) {
            $params[ 'dir' ] = 'ASC';
        }

        $ord = 'id';
        $rs = self::$_pdo->query( 'SELECT * FROM categories LIMIT 0' );
        for ( $i = 0; $i < $rs->columnCount(); $i++ ) {
            if ( $rs->getColumnMeta( $i )[ 'name' ] == $params[ 'ord' ] ) {
                $ord = $params[ 'ord' ];
                break;
            }
        }

        // Set number of records in the pagination object
        $this->_setNumRecordsPagn();

        $sql = "SELECT id, name, description
                  FROM categories
                 WHERE TRUE ";

        // Search category by either name or description
        if ( $this->request->pagParams[ 'search' ] != null ) {
            $sql .= 'AND name ~* :search
                      OR description ~* :search ';
        }

        $sql .= " ORDER BY {$ord} {$params['dir']}
                  LIMIT :lim
                 OFFSET :offset ";

        $selectStmt = self::$_pdo->prepare( $sql );
        if ( $this->request->pagParams[ 'search' ] != null ) {
            $selectStmt->bindParam( ':search', $this->request->pagParams[ 'search' ] );
        }
        $lim = 2;
        $selectStmt->bindParam( ':lim', $lim, PDO::PARAM_INT );
        $selectStmt->bindParam( ':offset', $this->pagination->getOffset(), PDO::PARAM_INT );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'CategoryModel' );
        $users = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        if ( !is_array( $users ) ) return null;

        return $users;
    }

    protected function _setNumRecordsPagn() {
        $sql = "SELECT count(*) AS count
                  FROM categories
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

    /**
     * @param CategoryModel $model
     * @param bool|false $overrideNullData
     */
    public function save( CategoryModel $model, $overrideNullData = false ) {
        parent::save( $model, $overrideNullData );
    }

    /**
     * @param CategoryModel $model
     * @throws Exception
     */
    public function destroy( CategoryModel $model ) {
        if ( ! is_numeric( $model->id ) ) {
            throw new Exception( 'Não foi possível remover: chave primária sem valor!' );
        }

        parent::destroy( $model );
    }

    public function getPostsByCategory( $catId, $count = false ) {
        if ( $count ) {
            $select = "COUNT(*) AS count";
        } else {
            $select = "*";
        }

        $selectStmt = self::$_pdo->prepare(
            "SELECT {$select} FROM posts WHERE category_id = :cat_id"
        );

        $selectStmt->bindParam( ':cat_id', $catId, PDO::PARAM_INT );
        $selectStmt->execute();

        if ( $count ) {
            return $selectStmt->fetchColumn();
        }

        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'PostModel' );
        $posts = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        return $posts;
    }
}
