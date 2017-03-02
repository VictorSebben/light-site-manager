<?php

namespace lsm\mappers;

use lsm\libs\H;
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
            "SELECT p.id, title, intro, series_id, position,
                    image, p.status, post_text
               FROM posts p
              WHERE p.id = ?"
        );
    }

    public function findBySlug( $slug ) {
        $stmt = self::$_pdo->prepare(
            'SELECT id, title, intro, series_id,
                    position, image, status, post_text
             FROM posts
             WHERE slug = ?
             ORDER BY slug'
        );
        $stmt->execute( array( $slug ) );
        $stmt->setFetchMode( PDO::FETCH_CLASS, $this->modelName );
        $posts = $stmt->fetchAll();
        $stmt->closeCursor();

        if ( !is_array( $posts ) ) return null;

        return $posts;
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
            $params[ 'dir' ] = 'DESC';
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

        if ( ! $catId && ( isset( $_GET[ 'search-category' ] ) && $_GET[ 'search-category' ] ) ) {
            $catId = $this->request->getInput( 'search-category', false );
        }

        $seriesId = null;
        if ( isset( $_GET[ 'search-series' ] ) && $_GET[ 'search-series' ] ) {
            $seriesId = $this->request->getInput( 'search-series', false );
        }

        // Set number of records in the pagination object
        $this->_setNumRecordsPagn( $catId, $seriesId );

        $sql = "SELECT DISTINCT 
                       p.id, user_id, p.title, p.intro, post_text,
                       image, image_caption, p.status
                  FROM posts p
                  LEFT JOIN posts_categories pc ON pc.post_id = p.id
                  LEFT JOIN series s ON s.id = p.series_id
                 WHERE TRUE ";

        // Search post by either name or description
        if ( $this->request->pagParams[ 'search' ] != null ) {
            if ( self::$db == 'pgsql' ) {
                $sql .= 'AND (p.title ILIKE :search
                              OR p.intro ILIKE :search
                              OR p.post_text ILIKE :search) ';
            } else {
                $sql .= 'AND (p.title LIKE :search
                              OR p.intro LIKE :search
                              OR p.post_text LIKE :search) ';
            }
        }

        if ( $catId ) {
            $sql .= 'AND category_id = :cat ';
        }

        if ( $seriesId ) {
            $sql .= 'AND series_id = :series ';
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

        if ( $seriesId ) {
            $selectStmt->bindParam( ':series', $seriesId, PDO::PARAM_INT );
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
     * @param $seriesId
     */
    protected function _setNumRecordsPagn( $catId, $seriesId ) {
        $sql = "SELECT count(DISTINCT p.id) AS count
                  FROM posts p
                  LEFT JOIN posts_categories pc ON pc.post_id = p.id
                  LEFT JOIN series s ON s.id = p.series_id
                 WHERE TRUE ";

        if ( $this->request->pagParams['search'] != null ) {
            if ( self::$db == 'pgsql' ) {
                $sql .= 'AND (p.title ILIKE :search
                              OR p.intro ILIKE :search
                              OR p.post_text ILIKE :search) ';
            } else {
                $sql .= 'AND (p.title LIKE :search
                              OR p.intro LIKE :search
                              OR p.post_text LIKE :search) ';
            }
        }

        if ( $catId ) {
            $sql .= 'AND pc.category_id = :cat ';
        }

        if ( $seriesId ) {
            $sql .= 'AND s.id = :series ';
        }

        $selectStmt = self::$_pdo->prepare($sql);

        if ( $this->request->pagParams['search'] != null ) {
            $search = "%{$this->request->pagParams[ 'search' ]}%";
            $selectStmt->bindParam( ':search', $search );
        }

        if ( $catId ) {
            $selectStmt->bindParam( ':cat', $catId, PDO::PARAM_STR );
        }

        if ( $seriesId ) {
            $selectStmt->bindParam( ':series', $seriesId, PDO::PARAM_INT );
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
     * @param $post PostsModel
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

            // Find the post: if series_id is set, we have to adjust the positions
            $post = $this->find( $post->id );
            if ( $post->series_id ) {
                $this->_updatePositions( $post, true );
            }

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

    /**
     * @param $postIds
     * @throws PDOException
     */
    public function destroyMany( $postIds ) {
        // First, we will destroy the images and videos related to the post, if there are any
        $sqlImgs = 'DELETE FROM images WHERE post_id IN (';
        $sqlVideos = 'DELETE FROM videos WHERE post_id IN (';

        // SQL to delete posts_categories entries
        $sqlCat = 'DELETE FROM posts_categories WHERE post_id IN (';

        // SQL to delete posts
        $sql = 'DELETE FROM posts WHERE id IN (';

        foreach ( $postIds as $id ) {
            $sqlImgs .= '?, ';
            $sqlVideos .= '?, ';
            $sqlCat .= '?, ';
            $sql .= '?, ';

            $post = $this->find( $id );
            if ( $post->series_id ) {
                $this->_updatePositions( $post, true );
            }
        }

        $sql       = trim( $sql, ', ' ) . ')';
        $sqlCat    = trim( $sqlCat, ', ' ) . ')';
        $sqlImgs   = trim( $sqlImgs, ', ' ) . ')';
        $sqlVideos = trim ( $sqlVideos, ', ' ) . ')';

        $stmt = self::$_pdo->prepare( $sqlImgs );
        $stmt->execute( $postIds );
        $stmt->closeCursor();

        $stmt = self::$_pdo->prepare( $sqlVideos );
        $stmt->execute( $postIds );
        $stmt->closeCursor();

        $stmt = self::$_pdo->prepare( $sqlCat );
        $stmt->execute( $postIds );
        $stmt->closeCursor();

        $stmt = self::$_pdo->prepare( $sql );
        $stmt->execute( $postIds );
        $stmt->closeCursor();
    }

    public function getAllSeries() {
        $stmt = self::$_pdo->prepare( 'SELECT id, title FROM series' );
        $stmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\models\SeriesModel' );
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
            $this->destroyMany( $postIds );

            // If everything worked out, commit transaction and return true
            self::$_pdo->commit();
            return true;
        } catch ( PDOException $e ) {
            self::$_pdo->rollBack();
            if ( DEBUG )
                echo $e->getMessage();
            else
                echo 'Não foi possível processar a requisição: contate o suporte';
            return false;
        }
    }

    /**
     * @param PostsModel $post
     * @param bool $overrideNullData
     * @param null $oldPKValue
     * @throws Exception
     */
    public function save( $post, $overrideNullData = false, $oldPKValue = null ) {
        // Start transaction: in case anything goes wrong when inserting,
        // we cancel everything
        self::$_pdo->beginTransaction();

        try {
            // If it is an update, let's delete the old entries for posts_categories
            if ( $post->id ) {
                self::$_pdo->prepare( "DELETE FROM posts_categories WHERE post_id = ?" )->execute( array( $post->id ) );
            }

            if ( intval( $post->series_id ) ) {
                $this->_updatePositions( $post, false );
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

    /**
     * @param PostsModel $model
     * @param bool $destroy
     * @throws Exception
     */
    protected function _updatePositions( PostsModel $model, $destroy = false ) {
        if ( ! $model->series_id ) {
            throw new Exception( 'Error at PostsMapper::_updatePositions(): No Series id specified!' );
        }

        // Get rowCount (to see how many posts there are in this series)
        $stmt = self::$_pdo->prepare( 'SELECT id FROM posts WHERE series_id = :series_id' );
        $stmt->bindParam( ':series_id', $model->series_id, PDO::PARAM_INT );
        $stmt->execute();

        $rowCount = $stmt->rowCount();

        $stmt->closeCursor();

        // Get the previous position of the object
        $stmt = self::$_pdo->prepare( 'SELECT position FROM posts WHERE id = :id' );
        $stmt->bindParam( ':id', $model->id );
        $stmt->execute();

        $oldPosition = $stmt->fetch( PDO::FETCH_OBJ )->position;

        $stmt->closeCursor();

        // When a new posts is being inserted, or when it is a post that did not have series
        // and position set yet, just set the position to what the user specified, incrementing
        // the ones from that particular position on, or just set to the last position
        // in case the user did not specify a position or set it to 0
        if ( ( ! $model->id ) || ( ! $oldPosition ) ) {
            // If no position was specified or it was greater than what should be the last position,
            // set it to the last position
            if ( ! $model->position || ( $model->position > ( $rowCount + 1 ) ) ) {
                $model->position = $rowCount + 1;
            }

            $sql = "UPDATE posts SET position = position + 1
                     WHERE position >= :position
                       AND series_id = :series_id ";

            $stmt = self::$_pdo->prepare( $sql );
            $stmt->bindParam( ':position', $model->position, PDO::PARAM_INT );
            $stmt->bindParam( ':series_id', $model->series_id, PDO::PARAM_INT );
            $stmt->execute();
            $stmt->closeCursor();
        }

        // Update or Delete
        else {
            // Destroy
            if ( $destroy ) {
                $sql = "UPDATE posts SET position = position - 1
                         WHERE position >= :position
                           AND series_id = :series_id";

                $stmt = self::$_pdo->prepare( $sql );
                $stmt->bindParam( ':position', $model->position, PDO::PARAM_INT );
                $stmt->bindParam( ':series_id', $model->series_id, PDO::PARAM_INT );
                $stmt->execute();
                $stmt->closeCursor();
            }

            // Update: we will need to check the model's previous position in the DB.
            // If it changed, we will process all the changes
            else {
                // If no position was specified or it was greater than what should be the last position,
                // set it to the last position
                if ( ! $model->position || ( $model->position > ( $rowCount ) ) ) {
                    $model->position = $rowCount;
                }

                if ( $oldPosition == $model->position ) {
                    return;
                }

                // Lower to Upper position, i.g. 5 to 1
                if ( $model->position < $oldPosition ) {
                    // Increment the positions of the posts whose position is greater than
                    // or equal to the new position and lesser than the old position
                    $sql = 'UPDATE posts SET position = position + 1
                             WHERE position >= :new_position
                               AND position < :old_position
                               AND series_id = :series_id';
                } // Upper to Lower position, i.g. 1 to 5
                else {
                    $sql = 'UPDATE posts SET position = position - 1
                             WHERE position > :old_position
                               AND position <= :new_position
                               AND series_id = :series_id';
                }

                $stmt = self::$_pdo->prepare( $sql );
                $stmt->bindParam( ':new_position', $model->position, PDO::PARAM_INT );
                $stmt->bindParam( ':old_position', $oldPosition, PDO::PARAM_INT );
                $stmt->bindParam( ':series_id', $model->series_id, PDO::PARAM_INT );
                $stmt->execute();
                $stmt->closeCursor();
            }
        }
    }
}
