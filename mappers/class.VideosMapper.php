<?php

namespace lsm\mappers;

use lsm\models\VideosModel;
use PDO;
use Exception;

class VideosMapper extends Mapper {

    /**
     * @throws Exception
     */
    public function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, post_id, url, video_id, video_provider, position FROM videos WHERE id = ?"
        );
    }

    public function index( $pk ) {
        $stmt = self::$_pdo->prepare(
            "SELECT id, post_id, url, video_id, video_provider, title, position
               FROM videos
              WHERE post_id = :post_id
              ORDER BY position"
        );

        $stmt->bindParam( 'post_id', $pk, PDO::PARAM_INT );
        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_CLASS, 'lsm\models\VideosModel' );
        $videos = $stmt->fetchAll();
        $stmt->closeCursor();

        if ( !is_array( $videos ) ) return null;

        return $videos;
    }

    protected function _setNumRecordsPagn() {
        $sql = "SELECT count(*) AS count
                  FROM videos ";

        $selectStmt = self::$_pdo->prepare( $sql );
        $selectStmt->execute();
        $this->pagination->numRecords = $selectStmt->fetch( PDO::FETCH_OBJ )->count;
        $selectStmt->closeCursor();
    }

    public function save( $model, $overrideNullData = false, $oldPKValue = null ) {
        self::$_pdo->beginTransaction();

        try {
            // Adjust the position of the other videos according to
            // the position set for the video being saved: we will only
            // adjust the positions if the model being saved has a position
            // set, or if it is an insert operation (in which case a null
            // value indicates that the user wants to insert in the default
            // position for new data, which is the last position).
            if ( $model->position || ( ! $model->id ) ) {
                $this->_updatePositions( $model );
            }

            // Call parent method to save video
            parent::save( $model, $overrideNullData, $oldPKValue );

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
            $this->_updatePositions( $model, true );

            // Call parent method to destroy video
            parent::destroy( $model );

            self::$_pdo->commit();
        } catch ( Exception $e ) {
            self::$_pdo->rollBack();
            throw new Exception( $e->getMessage() );
        }
    }

    // TODO PUBLIC TEST
    public function _updatePositions( VideosModel $model, $destroy = false ) {
        if ( ! $model->post_id ) {
            throw new Exception( 'Error at VideosMapper::_updatePositions(): No Post id specified!' );
        }

        /*
         * We will consider that, if the value of id was not provided, it is
         * an insertion. It will be treated as an update or delete operation otherwise.
         * We will not query the DB to check if the entry really exists for performance's sake.
         * If an error occurs, it will be thrown
         */

        // Get rowCount (to see how many videos there are in this gallery)
        $stmt = self::$_pdo->prepare( 'SELECT id FROM videos WHERE post_id = :post_id' );
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

            $sql = "UPDATE videos SET position = position + 1
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
                $sql = "UPDATE videos SET position = position - 1
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
                $stmt = self::$_pdo->prepare( 'SELECT position FROM videos WHERE id = :id' );
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
                    $sql = 'UPDATE videos SET position = position + 1
                             WHERE position >= :new_position
                               AND position < :old_position
                               AND post_id = :post_id';
                } // Upper to Lower position, i.g. 1 to 5
                else {
                    $sql = 'UPDATE videos SET position = position - 1
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
