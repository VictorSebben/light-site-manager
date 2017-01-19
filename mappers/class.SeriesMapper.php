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

        $sql = "SELECT id, title, intro, status
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

    public function deleteAjax( $seriesIds ) {
        self::$_pdo->beginTransaction();

        try {
            // SQL do delete agenda items
            $sql = 'DELETE FROM series WHERE id IN (' .
                implode( ',', array_map( function( $value ) { return '?'; }, $seriesIds ) ) . ')';

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
     * @param bool $destroyPosts
     * @throws Exception
     */
    public function destroy( $series, $destroyPosts = false ) {
        self::$_pdo->beginTransaction();

        try {
            // If $destroyPosts, remove posts related to the series
            if ( $destroyPosts ) {
                // First of all, we need to find the posts related to the series (series_posts entries)
                $stmt = self::$_pdo->prepare( 'SELECT post_id FROM series_posts WHERE series_id = :series_id' );
                $series_id = $series->id;
                $stmt->bindParam( ':series_id', $series_id, PDO::PARAM_INT );
                $stmt->execute();
                $stmt->setFetchMode( PDO::FETCH_ASSOC );
                $postIds = array_map( function( $series ) {
                    return $series[ 'post_id' ];
                }, $stmt->fetchAll() );
                $stmt->closeCursor();

                if ( count( $postIds ) ) {
                    $postsMapper = new PostsMapper();
                    $postsMapper->destroyMany( $postIds );
                }
            } else {
                // If the user does not want to remove the posts, let us simply
                // remove the series_posts entries, so that the posts will be disassociated from
                // the series that we are deleting
                $this->removeSeriesPosts( $series->id );
            }

            parent::destroy( $series );

            self::$_pdo->commit();
        } catch ( Exception $e ) {
            self::$_pdo->rollBack();
            throw new Exception( $e->getMessage() );
        }
    }

    private function removeSeriesPosts( $seriesId ) {
        $stmt = self::$_pdo->prepare( 'DELETE FROM series_posts WHERE series_id = :series_id' );
        $stmt->bindParam( ':series_id', $seriesId  );
        $stmt->execute();
        $stmt->closeCursor();
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