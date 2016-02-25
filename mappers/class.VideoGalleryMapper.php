<?php

class VideoGalleryMapper extends Mapper {

    /**
     * @throws Exception
     */
    public function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, post_id, video_iframe, position FROM video_galleries WHERE id = ?"
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
            $params[ 'dir' ] = 'ASC';
        }

        $ord = 'id';
        $rs = self::$_pdo->query( 'SELECT id, title FROM video_galleries LIMIT 0' );
        for ( $i = 0; $i < $rs->columnCount(); $i++ ) {
            if ( $rs->getColumnMeta( $i )[ 'name' ] == $params[ 'ord' ] ) {
                $ord = $params[ 'ord' ];
                break;
            }
        }

        // Set number of records in the pagination object
        $this->_setNumRecordsPagn();

        $stmt = self::$_pdo->prepare(
            "SELECT id, post_id, video_iframe, title, position
               FROM video_galleries
              ORDER BY {$ord} {$params[ 'dir' ]}
              LIMIT :lim
             OFFSET :offset"
        );

        $lim = $this->pagination->getLimit();

        $stmt->bindParam( ':lim', $lim, PDO::PARAM_INT );
        $stmt->bindParam( ':offset', $offset, PDO::PARAM_INT );
        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_CLASS, 'VideoGalleryModel' );
        $videos = $stmt->fetchAll();
        $stmt->closeCursor();

        if ( !is_array( $videos ) ) return null;

        return $videos;
    }

    protected function _setNumRecordsPagn() {
        $sql = "SELECT count(*) AS count
                  FROM video_galleries ";

        $selectStmt = self::$_pdo->prepare( $sql );
        $selectStmt->execute();
        $this->pagination->numRecords = $selectStmt->fetch( PDO::FETCH_OBJ )->count;
        $selectStmt->closeCursor();
    }

    public function save( $model, $overrideNullData = false ) {
        self::$_pdo->beginTransaction();

        try {
            // Adjust the position of the other videos according to
            // the position set for the video being saved
            $this->_updatePositions( $model );

            // Call parent method to save video
            parent::save( $model, $overrideNullData );

            self::$_pdo->commit();
        } catch ( Exception $e ) {
            self::$_pdo->rollBack();
            throw new Exception( $e->getMessage() );
        }
    }

    public function destroy( $model ) {
        self::$_pdo->beginTransaction();

        try {
            // Adjust the position of the other videos according to
            // the position of the video being removed
            // TODO POPULATE POST_ID AND POSITION
            $this->_updatePositions( $model, true );

            // Call parent method to destroy video
            parent::save( $model );

            self::$_pdo->commit();
        } catch ( Exception $e ) {
            self::$_pdo->rollBack();
            throw new Exception( $e->getMessage() );
        }
    }

    // TODO PUBLIC TEST
    public function _updatePositions( VideoGalleryModel $model, $destroy = false ) {
        if ( ! $model->post_id ) {
            throw new Exception( 'Error at VideoGalleryMapper::_updatePositions(): No Post id specified!' );
        }

        /*
         * We will consider that, if the value of id was not provided, it is
         * an insertion. It will be treated as an update or delete operation otherwise.
         * We will not query the DB to check if the entry really exists for performance's sake.
         * If an error occurs, it will be thrown
         */

        // Get rowCount (to see how many videos there are in this gallery)
        $stmt = self::$_pdo->prepare( 'SELECT id FROM video_galleries WHERE post_id = :post_id' );
        $stmt->bindParam( ':post_id', $model->post_id, PDO::PARAM_INT );
        $stmt->execute();

        $rowCount = $stmt->rowCount();

        $stmt->closeCursor();

        // Insert: just set the position to what the user specified, incrementing
        // the ones from that particular position on, or just set to the last position
        // in case the user did not specify a position or set it to 0
        if ( ! $model->id ) {
            // If no position was specified or it was greater than what should be the last position,
            // set it to the last position
            if ( ! $model->position || ( $model->position > ( $rowCount + 1 ) ) ) {
                $model->position = $rowCount + 1;
            }

            $sql = "UPDATE video_galleries SET position = position + 1
                     WHERE position >= :position
                       AND post_id = :post_id ";

            $stmt = self::$_pdo->prepare( $sql );
            $stmt->bindParam( ':position', $model->position, PDO::PARAM_INT );
            $stmt->bindParam( ':post_id', $model->post_id, PDO::PARAM_INT );
            $stmt->execute();
            $stmt->closeCursor();
        }

        // Update or Delete
        else {
            // Destroy
            if ( $destroy ) {
                $sql = "UPDATE video_galleries SET position = position - 1
                         WHERE position >= :position
                           AND post_id = :post_id";

                $stmt = self::$_pdo->prepare( $sql );
                $stmt->bindParam( ':position', $model->position, PDO::PARAM_INT );
                $stmt->bindParam( ':post_id', $model->post_id, PDO::PARAM_INT );
                $stmt->execute();
                $stmt->closeCursor();
            }

            // Update: we will need to check the model's previous position in the DB.
            // If it changed, we will process all the changes
            else {
                // Get the previous position of the object
                $stmt = self::$_pdo->prepare( 'SELECT position FROM video_galleries WHERE id = :id' );
                $stmt->bindParam( ':id', $model->id );
                $stmt->execute();

                $oldPosition = $stmt->fetch( PDO::FETCH_OBJ )->position;

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
                    // Increment the positions of the videos whose position is greater than
                    // or equal to the new position and lesser than the old position
                    $sql = 'UPDATE video_galleries SET position = position + 1
                             WHERE position >= :new_position
                               AND position < :old_position
                               AND post_id = :post_id';
                } // Upper to Lower position, i.g. 1 to 5
                else {
                    $sql = 'UPDATE video_galleries SET position = position - 1
                             WHERE position > :old_position
                               AND position <= :new_position
                               AND post_id = :post_id';
                }

                $stmt = self::$_pdo->prepare( $sql );
                $stmt->bindParam( ':new_position', $model->position, PDO::PARAM_INT );
                $stmt->bindParam( ':old_position', $oldPosition, PDO::PARAM_INT );
                $stmt->bindParam( ':post_id', $model->post_id, PDO::PARAM_INT );
                $stmt->execute();
                $stmt->closeCursor();
            }
        }
    }
}
