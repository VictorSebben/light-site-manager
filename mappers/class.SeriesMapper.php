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

    public function index() {

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

        // Set number of records in the pagination object
        $this->_setNumRecordsPagn();

        $sql = "SELECT id, title, intro, status,
                       (SELECT COUNT(*) FROM posts WHERE series_id = series.id) AS count_posts
                  FROM series
                 WHERE TRUE ";

        // Search series by title or intro
        if ( $this->request->pagParams[ 'search' ] != null ) {
            $sql .= 'AND (title ILIKE :search OR intro ILIKE :search)';
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
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'lsm\models\SeriesModel' );
        $series = $selectStmt->fetchAll();
        $selectStmt->closeCursor();

        if ( ! is_array( $series ) ) return null;

        return $series;
    }

    protected function _setNumRecordsPagn() {
        $sql = "SELECT count(*) AS count
                  FROM series
                 WHERE TRUE ";

        if ( $this->request->pagParams['search'] != null ) {
            $sql .= 'AND (title ILIKE :search OR intro ILIKE :search) ';
        }

        $selectStmt = self::$_pdo->prepare( $sql );
        if ( $this->request->pagParams['search'] != null ) {
            $selectStmt->bindParam( ':search', $this->request->pagParams['search'] );
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
                    "UPDATE POSTS SET series_id = NULL WHERE series_id IN ({$strIdsPdo})"
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
}