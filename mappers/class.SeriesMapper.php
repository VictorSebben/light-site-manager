<?php

namespace lsm\mappers;

use lsm\libs\H;
use lsm\models\PostsModel;
use lsm\models\SeriesModel;
use PDO;
use PDOException;
use Exception;

class SeriesMapper extends Mapper {
    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, title, intro, status FROM series WHERE id = ?"
        );
    }

    public function index( $catId = null ) {

        // set additional parameters for the pagination
        // in the request object
        $this->request->setPagParams();
        $params = $this->request->pagParams;

        $offset = $this->pagination->getOffset();

        // validate $params[ 'dir' ] to make sure it contains a valid value
        if ( $params[ 'dir' ] !== 'ASC' && $params[ 'dir' ] !== 'DESC' ) {
            $params[ 'dir' ] = 'DESC';
        }

        $ord = 'id';
        $rs = self::$_pdo->query( 'SELECT * FROM series LIMIT 0' );
        for ( $i = 0; $i < $rs->columnCount(); $i++ ) {
            if ( $rs->getColumnMeta( $i )[ 'name' ] == $params[ 'ord' ] ) {
                $ord = $params[ 'ord' ];
                break;
            }
        }

        if ( ! $catId && ( isset( $_GET[ 'search-category' ] ) && $_GET[ 'search-category' ] ) ) {
            $catId = $this->request->getInput( 'search-category', false );
        }

        // Set number of records in the pagination object
        $this->_setNumRecordsPagn( $catId );

        $sql = "SELECT DISTINCT 
                       id, title, intro, status,
                       (SELECT COUNT(*) FROM posts WHERE series_id = s.id) AS count_posts
                  FROM series s
                  LEFT JOIN series_categories sc ON sc.series_id = s.id
                 WHERE TRUE ";

        // Search series by title or intro
        if ( $this->request->pagParams[ 'search' ] != null ) {
            $sql .= 'AND (title ILIKE :search OR intro ILIKE :search)';
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
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'lsm\models\SeriesModel' );
        $series = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        if ( ! is_array( $series ) ) return null;

        array_map( function( $seriesObj ) {
            $this->initCategories( $seriesObj );
            return $seriesObj;
        }, $series );

        return $series;
    }

    protected function _setNumRecordsPagn( $catId ) {
        $sql = "SELECT count(DISTINCT s.id) AS count
                  FROM series s
                  LEFT JOIN series_categories sc ON sc.series_id = s.id
                 WHERE TRUE ";

        if ( $this->request->pagParams['search'] != null ) {
            $sql .= 'AND (title ILIKE :search OR intro ILIKE :search) ';
        }

        if ( $catId ) {
            $sql .= 'AND sc.category_id = :cat ';
        }

        $selectStmt = self::$_pdo->prepare( $sql );

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
     * @param $seriesIds
     * @param $actionPosts
     * @return bool
     */
    public function deleteAjax( $seriesIds, $actionPosts ) {
        self::$_pdo->beginTransaction();

        $strIdsPdo = implode( ',', array_map( function( $value ) { return '?'; }, $seriesIds ) );

        try {
            if ( $actionPosts === SeriesModel::DISSOCIATE_POSTS ) {

                $stmt = self::$_pdo->prepare(
                    "UPDATE posts SET series_id = NULL WHERE series_id IN ({$strIdsPdo})"
                );
                $stmt->execute( $seriesIds );
                $stmt->closeCursor();
            } else if ( $actionPosts === SeriesModel::DELETE_POSTS ) {

                // Find posts related to all the series
                $stmt = self::$_pdo->prepare( "SELECT id FROM posts WHERE series_id IN ({$strIdsPdo})" );
                $stmt->execute( $seriesIds );

                $postIds = $stmt->fetchAll( PDO::FETCH_OBJ );
                $postIds = array_map( function( $post ) {
                    return $post->id;
                }, $postIds );

                $stmt->closeCursor();

                if ( count( $postIds ) ) {
                    $postsMapper = new PostsMapper();
                    $postsMapper->destroyMany( $postIds );
                }
            }

            // SQL to delete series_categories entries
            $sqlCat = "DELETE FROM series_categories WHERE series_id IN ({$strIdsPdo})";

            $stmt = self::$_pdo->prepare( $sqlCat );
            $stmt->execute( $seriesIds );
            $stmt->closeCursor();

            // SQL do delete agenda items
            $sql = "DELETE FROM series WHERE id IN ({$strIdsPdo})";

            $stmt = self::$_pdo->prepare( $sql );
            $stmt->execute( $seriesIds );
            $stmt->closeCursor();

            // If everything worked out, commit transaction and return true
            self::$_pdo->commit();
            return true;
        } catch ( Exception $e ) {
            self::$_pdo->rollBack();
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * Destroys the Series entry. If $destroyPosts is set to true,
     * all posts belonging to the series will be destroyed too.
     * Otherwise, they will be merely disassociated.
     *
     * @param SeriesModel $series
     * @param $actionPosts
     * @throws Exception
     */
    public function destroy( $series, $actionPosts = SeriesModel::DISSOCIATE_POSTS ) {
        self::$_pdo->beginTransaction();

        try {
            if ( $actionPosts == SeriesModel::DISSOCIATE_POSTS ) {

                $stmt = self::$_pdo->prepare( 'UPDATE POSTS SET series_id = NULL WHERE series_id = :series_id' );
                $series_id = $series->id;
                $stmt->bindParam( ':series_id', $series_id, PDO::PARAM_INT );
                $stmt->execute();
                $stmt->closeCursor();

            } else if ( $actionPosts == SeriesModel::DELETE_POSTS ) {

                // First of all, we need to find the posts related to the series, so that we
                // can call the PostsMapper::destroyMany() method, that will do all the required
                // actions to properly remove the posts
                $postIds = array_map( function( $post ) {
                    return $post->id;
                }, $this->getRelatedPosts( $series->id ) );

                if ( count( $postIds ) ) {
                    $postsMapper = new PostsMapper();
                    $postsMapper->destroyMany( $postIds );
                }
            }

            // Destroy the series_categories entries
            self::$_pdo->prepare( 'DELETE FROM series_categories WHERE series_id = ?' )->execute( array( $series-> id ) );

            parent::destroy( $series );

            self::$_pdo->commit();
        } catch ( Exception $e ) {
            self::$_pdo->rollBack();
            throw new Exception( $e->getMessage() );
        }
    }

    public function getRelatedPosts( $seriesId ) {
        $stmt = self::$_pdo->prepare( 'SELECT * FROM posts WHERE series_id = :series_id' );
        $stmt->bindParam( ':series_id', $seriesId, PDO::PARAM_INT );
        $stmt->execute();

        $posts = $stmt->fetchAll( PDO::FETCH_CLASS, 'lsm\models\PostsModel' );

        $stmt->closeCursor();

        return $posts;
    }

    public function toggleStatus( $id ) {
        if ( !is_numeric( $id ) ) {
            return false;
        }

        try {
            $this->_selectStmt = self::$_pdo->prepare(
                "SELECT id, status FROM series WHERE id = ?"
            );
            $series = $this->find( $id );

            if ( ! $series ) return false;

            // toggle series' status
            $series->status = ( $series->status == 0 ) ? 1 : 0;
            parent::save( $series );

            // if everything worked out, return true
            return true;
        } catch ( PDOException $e ) {
            echo $e->getMessage();
            return false;
        }
    }

    public function activate( $seriesIds ) {
        return $this->_toggleStatusArr( $seriesIds, 1 );
    }

    public function deactivate( $seriesIds ) {
        return $this->_toggleStatusArr( $seriesIds, 0 );
    }

    private function _toggleStatusArr( $seriesIds, $status ) {
        try {
            $sql = "UPDATE series SET status = {$status} WHERE id IN (";

            foreach ( $seriesIds as $id ) {
                $sql .= '?, ';
            }

            $sql = trim( $sql, ', ' ) . ')';
            $stmt = self::$_pdo->prepare( $sql );
            $stmt->execute( $seriesIds );

            // If everything worked out, return true
            return true;
        } catch ( Exception $e ) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * @param SeriesModel $series
     * @throws Exception
     */
    public function initCategories( SeriesModel $series ) {
        $series->categories = array();

        if ( ! $series->id ) {
            throw new Exception( "Could not retrieve categories for series: series id not specified!" );
        }

        $sql = "SELECT c.* 
                  FROM categories c
                  JOIN series_categories sc ON sc.category_id = c.id
                 WHERE sc.series_id = :series_id";

        $stmt = self::$_pdo->prepare( $sql );
        $stmt->bindParam( ':series_id', $series->id, PDO::PARAM_INT );
        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\models\CategoriesModel' );

        $series->categories = $stmt->fetchAll();
        $stmt->closeCursor();
    }

    public function save( $series, $overrideNullData = false, $oldPKValue = null ) {
        // Start transaction: in case anything goes wrong when inserting,
        // we cancel everything
        self::$_pdo->beginTransaction();

        try {
            // If it is an update, let's delete the old entries for series_categories
            if ( $series->id ) {
                self::$_pdo->prepare( "DELETE FROM series_categories WHERE series_id = ?" )->execute( array( $series->id ) );
            }

            // Call parent method to save post
            parent::save( $series, $overrideNullData, $oldPKValue );

            // If it is an insert operation, we have to retrieve the last inserted id
            if ( ! $series->id ) {
                $series->id = self::$_pdo->lastInsertId( 'series_id_seq' );
            }

            // Insert new values for series_categories
            $stmt = self::$_pdo->prepare( "INSERT INTO series_categories (series_id, category_id) VALUES (:series_id, :category_id)" );

            // Save entries for posts_categories
            foreach ( $series->categories as $category ) {
                $stmt->bindParam( ':series_id', $series->id, PDO::PARAM_INT );
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