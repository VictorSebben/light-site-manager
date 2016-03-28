<?php

class PostsMapper extends Mapper {

    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT p.id, title, intro, image, p.status,
                    category_id, post_text,
                    c.name AS category_name, u.name AS user_name
               FROM posts p
               JOIN categories c ON c.id = p.category_id
               JOIN users u ON u.id = p.user_id
              WHERE p.id = ?"
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
     * @param $catId mixed
     * @return array|null
     */
    public function index( $catId = null ) {
        // set additional parameters for the pagination
        // in the request object
        $this->request->setPagParams();
        $params = $this->request->pagParams;

        $offset = $this->pagination->getOffset();

        // validate $params[ 'dir' ] to make sure it contains a valid value
        if ( $params[ 'dir' ] !== 'ASC' && $params[ 'dir' ] !== 'DESC' ) {
            $params[ 'dir' ] = 'ASC';
        }

        $ord = 'id';
        $rs = self::$_pdo->query('
             SELECT posts.*, categories.name AS category_name, users.name AS user_name
               FROM posts
               JOIN categories ON categories.id = posts.category_id
               JOIN users ON users.id = posts.user_id
              LIMIT 0'
        );
        for ( $i = 0; $i < $rs->columnCount(); $i++ ) {
            if ( $rs->getColumnMeta( $i )[ 'name' ] == $params[ 'ord' ] ) {
                $ord = $params[ 'ord' ];
                break;
            }
        }

        // Set number of records in the pagination object
        $this->_setNumRecordsPagn( $catId );

        $sql = "SELECT p.id, title, image, p.status,
                       c.name AS category_name, u.name AS user_name
                  FROM posts p
                  JOIN categories c ON c.id = p.category_id
                  JOIN users u ON u.id = p.user_id
                 WHERE TRUE ";

        // Search category by either name or description
        if ( $this->request->pagParams[ 'search' ] != null ) {
            if ( self::$_db === 'pgsql' ) {
                $sql .= 'AND unaccent(title) ILIKE unaccent(:search)
                          OR unaccent(intro) ILIKE unaccent(:search)
                          OR unaccent(post_text) ILIKE unaccent(:search) ';
            } else {
                $sql .= 'AND title ILIKE :search
                          OR intro ILIKE :search
                          OR post_text ILIKE :search ';
            }
        }

        if ( $catId ) {
            $sql .= 'AND category_id = :cat ';
        }

        $sql .= " ORDER BY {$ord} {$params['dir']}
                  LIMIT :lim
                 OFFSET :offset ";

        $selectStmt = self::$_pdo->prepare( $sql );

        if ( $this->request->pagParams[ 'search' ] != null ) {
            $search = "%{$this->request->pagParams[ 'search' ]}%";
            $selectStmt->bindParam( ':search', $search );
        }

        if ( $catId ) {
            $selectStmt->bindParam( ':cat', $catId, PDO::PARAM_STR );
        }

        $lim = $this->pagination->getLimit();
        $selectStmt->bindParam( ':lim', $lim, PDO::PARAM_INT );
        $selectStmt->bindParam( ':offset', $offset, PDO::PARAM_INT );
        $selectStmt->execute();
        // Since the index of posts has info taken from the categories and users
        // tables, we are going to fetch a standard object here, instead of a model object
        $selectStmt->setFetchMode( PDO::FETCH_OBJ );
        $posts = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        if ( !is_array( $posts ) ) return null;

        return $posts;
    }

    /**
     * @param $catId
     */
    protected function _setNumRecordsPagn( $catId ) {
        $sql = "SELECT count(*) AS count
                  FROM posts
                 WHERE TRUE ";

        if ( $this->request->pagParams['search'] != null ) {
            $sql .= 'AND title ~* :search
                      OR intro ~* :search
                      OR post_text ~* :search ';
        }

        if ( $catId ) {
            $sql .= 'AND category_id = :cat ';
        }

        $selectStmt = self::$_pdo->prepare($sql);

        if ( $this->request->pagParams['search'] != null ) {
            $selectStmt->bindParam( ':search', $this->request->pagParams['search'] );
        }

        if ( $catId ) {
            $selectStmt->bindParam( ':cat', $catId, PDO::PARAM_STR );
        }

        $selectStmt->execute();
        $this->pagination->numRecords = $selectStmt->fetch( PDO::FETCH_OBJ )->count;
        $selectStmt->closeCursor();
    }

    /**
     * @param $model
     * @throws Exception
     */
    public function destroy( $model ) {
        self::$_pdo->beginTransaction();

        try {
            // First, we will destroy the gallery images related to the post, if there are any

            // Populate the gallery images of the post, if there are any
            $this->populatePostGallery( $model );

            $galleryMapper = new GalleryMapper();

            foreach ( $model->galleries as $gallery ) {
                $galleryMapper->destroy( $gallery );
            }

            // Destroy the post itself
            parent::destroy( $model );

            self::$_pdo->commit();
        } catch ( Exception $e ) {
            // If something went wrong, rollback transaction and
            // rethrow the error message
            self::$_pdo->rollBack();
            throw new Exception( $e->getMessage() );
        }
    }

    public function getAllCat() {
        $stmt = self::$_pdo->prepare( 'SELECT * FROM categories' );
        $stmt->setFetchMode( PDO::FETCH_CLASS, 'CategoriesModel' );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /***** Ajax Methods *****/
    /**
     * @ajax
     * @param $id
     * @return bool
     */
    public function toggleStatus( $id ) {
        if ( !is_numeric( $id ) ) {
            return false;
        }

        try {
            $this->_selectStmt = self::$_pdo->prepare(
                "SELECT id, status FROM posts WHERE id = ?"
            );
            $post = $this->find( $id );

            if ( ! $post ) return false;

            // toggle post's status
            $post->status = ( $post->status == 0 ) ? 1 : 0;
            $this->save( $post );

            // if everything worked out, return true
            return true;
        } catch ( PDOException $e ) {
            echo $e->getMessage();
            return false;
        }
    }

    public function activate( $postIds ) {
        return $this->_toggleStatusArr( $postIds, 1 );
    }

    public function deactivate( $postIds ) {
        return $this->_toggleStatusArr( $postIds, 0 );
    }

    private function _toggleStatusArr( $postIds, $status ) {
        try {
            $sql = "UPDATE posts SET status = {$status} WHERE id IN (";

            foreach ( $postIds as $id ) {
                $sql .= '?, ';
            }

            $sql = trim( $sql, ', ' ) . ')';
            $stmt = self::$_pdo->prepare( $sql );
            $stmt->execute( $postIds );

            // If everything worked out, return true
            return true;
        } catch ( Exception $e ) {
            echo $e->getMessage();
            return false;
        }
    }

    public function deleteAjax( $postIds ) {
        try {
            $sql = 'DELETE FROM posts WHERE id IN (';

            foreach ( $postIds as $id ) {
                $sql .= '?, ';
            }

            $sql = trim( $sql, ', ' ) . ')';
            $stmt = self::$_pdo->prepare( $sql );
            $stmt->execute( $postIds );

            // If everything worked out, return true
            return true;
        } catch ( PDOException $e ) {
            echo $e->getMessage();
            return false;
        }
    }

    public function populatePostGallery( PostsModel $post ) {
        if ( !is_numeric( $post->id ) )
            return;

        $stmt = self::$_pdo->prepare(
            "SELECT id, post_id, image
               FROM galleries
              WHERE post_id = :post_id"
        );

        $stmt->bindParam( ':post_id', $post->id );
        $stmt->execute();

        $post->galleries = $stmt->fetchAll( PDO::FETCH_CLASS, 'GalleryModel' );
    }
}
