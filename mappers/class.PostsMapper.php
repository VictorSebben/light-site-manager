<?php

namespace lsm\mappers;

use lsm\models\PostsModel;
use PDO;
use Exception;
use PDOException;

class PostsMapper extends Mapper {

    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT p.id, title, intro, 
                    image, p.status, post_text
               FROM posts p
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
        $rs = self::$_pdo->query('
             SELECT id, user_id, title, intro, post_text,
                    image, image_caption, status
               FROM posts
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

        $sql = "SELECT DISTINCT 
                       id, user_id, title, intro, post_text,
                       image, image_caption, status
                  FROM posts p
                  LEFT JOIN posts_categories pc ON pc.post_id = p.id
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
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\models\PostsModel' );
        $posts = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        if ( !is_array( $posts ) ) return null;

        array_map( function( $post ) {
            $this->loadUser( $post );
            $this->initCategories( $post );
            return $post;
        }, $posts );

        return $posts;
    }

    protected function loadUser( PostsModel $post ) {
        if ( ! $post->user_id ) return $post;

        $post->user = ( new UsersMapper() )->find( $post->user_id );

        return $post;
    }

    /**
     * @param $catId
     */
    protected function _setNumRecordsPagn( $catId ) {
        $sql = "SELECT count(DISTINCT p.id) AS count
                  FROM posts p
                  LEFT JOIN posts_categories pc ON pc.post_id = p.id
                 WHERE TRUE ";

        if ( $this->request->pagParams['search'] != null ) {
            $sql .= 'AND title ~* :search
                      OR intro ~* :search
                      OR post_text ~* :search ';
        }

        if ( $catId ) {
            $sql .= 'AND pc.category_id = :cat ';
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

    public function initCategories( PostsModel $post ) {
        $post->categories = array();

        if ( ! $post->id ) {
            throw new Exception( "Could not retrieve categories for post: post id not specified!" );
        }

        $sql = "SELECT c.* 
                  FROM categories c
                  JOIN posts_categories pc ON pc.category_id = c.id
                  JOIN posts p ON p.id = pc.post_id
                 WHERE p.id = :post_id";

        $stmt = self::$_pdo->prepare( $sql );
        $stmt->bindParam( ':post_id', $post->id, PDO::PARAM_INT );
        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\models\CategoriesModel' );

        $post->categories = $stmt->fetchAll();
        $stmt->closeCursor();
    }

    /**
     * @param $post
     * @throws Exception
     */
    public function destroy( $post ) {
        self::$_pdo->beginTransaction();

        try {
            // First, we will destroy the images and videos related to the post, if there are any
            self::$_pdo->prepare( 'DELETE FROM images WHERE post_id = ?' )->execute( array( $post->id ) );
            self::$_pdo->prepare( 'DELETE FROM videos WHERE post_id = ?' )->execute( array( $post->id ) );

            // Then, we destroy the posts_categories entries
            self::$_pdo->prepare( 'DELETE FROM posts_categories WHERE post_id = ?' )->execute( array( $post-> id ) );

            // Finally, destroy the post itself
            parent::destroy( $post );

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
        $stmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\models\CategoriesModel' );
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
            parent::save( $post );

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
        self::$_pdo->beginTransaction();

        try {
            // SQL to delete posts_categories entries
            $sqlCat = 'DELETE FROM posts_categories WHERE post_id IN (';

            // SQL to delete posts
            $sql = 'DELETE FROM posts WHERE id IN (';

            foreach ( $postIds as $id ) {
                $sqlCat .= '?, ';
                $sql .= '?, ';
            }

            $sql = trim( $sql, ', ' ) . ')';
            $sqlCat = trim( $sqlCat, ', ' ) . ')';

            $stmt = self::$_pdo->prepare( $sqlCat );
            $stmt->execute( $postIds );
            $stmt->closeCursor();

            $stmt = self::$_pdo->prepare( $sql );
            $stmt->execute( $postIds );
            $stmt->closeCursor();

            // If everything worked out, commit transaction and return true
            self::$_pdo->commit();
            return true;
        } catch ( PDOException $e ) {
            self::$_pdo->rollBack();
            echo $e->getMessage();
            return false;
        }
    }

    public function save( $post, $overrideNullData = false, $oldPKValue = null ) {
        // Start transaction: in case anything goes wrong when inserting,
        // we cancel everything
        self::$_pdo->beginTransaction();

        try {
            // If it is an update, let's delete the old entries for posts_categories
            if ( $post->id ) {
                self::$_pdo->prepare( "DELETE FROM posts_categories WHERE post_id = ?" )->execute( array( $post->id ) );
            }

            // Call parent method to save post
            parent::save( $post, $overrideNullData, $oldPKValue );

            // If it is an insert operation, we have to retrieve the last inserted id
            if ( ! $post->id ) {
                $post->id = self::$_pdo->lastInsertId( 'posts_id_seq' );
            }

            // Insert new values for posts_categories
            $stmt = self::$_pdo->prepare( "INSERT INTO posts_categories (post_id, category_id) VALUES (:post_id, :category_id)" );

            // Save entries for posts_categories
            foreach ( $post->categories as $category ) {
                $stmt->bindParam( ':post_id', $post->id, PDO::PARAM_INT );
                $stmt->bindParam( ':category_id', $category->id, PDO::PARAM_STR );
                $stmt->execute();
                $stmt->closeCursor();
            }

            self::$_pdo->commit();
        } catch ( Exception $e ) {
            // If something went wrong, rollback transaction
            // and throw a new exception to be caught by the
            // Router class
            self::$_pdo->rollBack();
            throw new Exception( $e->getMessage() );
        }
    }
}
