<?php

namespace lsm\mappers;

use lsm\models\ImagesModel;
use lsm\models\UsersModel;
use PDO;
use Exception;
use PDOException;

class ImagesMapper extends Mapper {

    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, post_id, position, caption, created, updated FROM images WHERE id = ?"
        );
    }


    /**
     * List all images from a given post.
     * @param $post_id
     * @return array|null
     */
    public function index( $post_id ) {
        $sql = "SELECT
                      id
                    , post_id
                    , caption
                    , position
                    , extension
                FROM images
                WHERE post_id = :post_id
                ORDER BY position ASC;";

        $stmt = self::$_pdo->prepare( $sql );
        $stmt->bindParam( ':post_id', $post_id, PDO::PARAM_INT );
        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\models\ImagesModel' );
        $images = $stmt->fetchAll();

        return is_array( $images) ? $images : NULL;
    }

    /**
     * @Override
     * Overrite save() because we need max(position) + 1 on insert, etc.
     *
     * @param ImagesModel $image
     * @param boolean $overrideNullData
     * @param mixed $oldPKValue
     * @return array AssociativeArray containing insertd `id` and inserted `position` columns.
     */
    public function save( $image, $overrideNullData = false, $oldPKValue = null ) {

        // 160wx100h

        // SQL deals with the fact that if images table is empty and therefore the ‘position’
        // column is null (max(position) returns null), we make it 0 + 1. Otherwise, we make
        // it “max(position) + 1”.

        $sqlPos = "SELECT COALESCE(MAX(position), 0) AS maxpos FROM images WHERE post_id = :post_id";
        $stmtPos = self::$_pdo->prepare($sqlPos);
        $stmtPos->bindParam( ':post_id', $image->post_id, PDO::PARAM_INT );
        $stmtPos->execute();

        // The next available position for the images of the current post.
        $nextPos = $stmtPos->fetch( PDO::FETCH_ASSOC )['maxpos'] + 1;

        $sql = "INSERT INTO images (
                  post_id
                , extension
                , position
                , created_at) VALUES (
                    :post_id
                  , :extension
                  , :position
                  , CURRENT_TIMESTAMP
                )";

        $stmt = self::$_pdo->prepare( $sql );
        $stmt->bindParam( ':post_id', $image->post_id );
        $stmt->bindParam( ':extension', $image->extension );
        $stmt->bindParam( ':position', $nextPos );
        $stmt->execute();

        $lastId = self::$_pdo->lastInsertId();

        $sqlNewImage = "SELECT id, post_id, position, caption, extension
                        FROM images WHERE id = {$lastId}";
        $stmtNewImage = self::$_pdo->prepare( $sqlNewImage );
        $stmtNewImage->execute();
        return $stmtNewImage->fetch( PDO::FETCH_ASSOC );
    }


    /**
     * Destroy an image from DB and HD.
     *
     * @param ImagesModel $image.
     * @return Json.
     */
    public function destroy( $image ) {

        $sql = 'DELETE FROM images WHERE id = :image_id AND post_id = :post_id';

        $stmt = self::$_pdo->prepare( $sql );

        $stmt->bindParam( ':image_id', $image->id, PDO::PARAM_INT );
        $stmt->bindParam( ':post_id', $image->post_id, PDO::PARAM_INT );

        $status = $stmt->execute();

        if ( ! $status ) {
            return false;
        }

        return $this->repositionAfterDestroy( $image->position, $image->post_id );
    }


    // É necessário saber a posição do elemento que foi removido para atualizar
    // as posições de todos os elementos posicionados após ao que foi removido.
    // IMPORTANTE: Somente autalizar as posições dos conteúdos do produto em questão.
    protected function repositionAfterDestroy( $position, $post_id ) {

        $sql = "UPDATE images SET
            position = position - 1
            WHERE post_id = :post_id
            AND position > :position";

        $stmt = self::$_pdo->prepare( $sql );
        $stmt->bindParam( ':post_id', $post_id, PDO::PARAM_INT );
        $stmt->bindParam( ':position', $position, PDO::PARAM_INT );

        return $stmt->execute();
    }

    public function setPosition( $image, $oldpos, $newpos ) {

        if ( $newpos < $oldpos ) {
            $res = $this->_moveUpToPos( $image, $oldpos, $newpos );
        }
        else {
            $res = $this->_moveDownToPos( $image, $oldpos, $newpos );
        }

        return $res;
    }


    public function _moveUpToPos( $image, $oldpos, $newpos ) {

        // TODO: What about using BETWEEN?

        try {
            $sql1 = 'UPDATE images SET
                        position = position + 1
                        WHERE post_id = :post_id
                        AND position >= :newpos
                        AND position < :oldpos';

            $stmt1 = self::$_pdo->prepare( $sql1 );

            $stmt1->bindParam( ':post_id', $image->post_id, PDO::PARAM_INT );
            $stmt1->bindParam( ':newpos', $newpos, PDO::PARAM_INT );
            $stmt1->bindParam( ':oldpos', $oldpos, PDO::PARAM_INT );

            $sql2 = 'UPDATE images SET position = :newpos WHERE id = :image_id';

            $stmt2 = self::$_pdo->prepare( $sql2 );
            $stmt2->bindParam( ':newpos', $newpos, PDO::PARAM_INT );
            $stmt2->bindParam( ':image_id', $image->id, PDO::PARAM_INT );

            self::$_pdo->beginTransaction();
            $stmt1->execute();
            $stmt2->execute();
            self::$_pdo->commit();

            return [ 'status' => 'success' ];
        }
        catch ( PDOException $err ) {
            self::$_pdo->rollBack();
            return [ 'status' => 'error' ];
        }

    }


    public function _moveDownToPos( $image, $oldpos, $newpos ) {

        try {
            $sql1 = 'UPDATE images SET
                        position = position - 1
                        WHERE post_id = :post_id
                        AND position <= :newpos
                        AND position > :oldpos';

            $stmt1 = self::$_pdo->prepare( $sql1 );

            $stmt1->bindParam( ':post_id', $image->post_id, PDO::PARAM_INT );
            $stmt1->bindParam( ':newpos', $newpos, PDO::PARAM_INT );
            $stmt1->bindParam( ':oldpos', $oldpos, PDO::PARAM_INT );

            $sql2 = 'UPDATE images SET position = :newpos WHERE id = :image_id';

            $stmt2 = self::$_pdo->prepare( $sql2 );
            $stmt2->bindParam( ':newpos', $newpos, PDO::PARAM_INT );
            $stmt2->bindParam( ':image_id', $image->id, PDO::PARAM_INT );

            self::$_pdo->beginTransaction();
            $stmt1->execute();
            $stmt2->execute();
            self::$_pdo->commit();

            return [ 'status' => 'success' ];
        }
        catch ( PDOException $err ) {
            self::$_pdo->rollBack();
            return [ 'status' => 'error' ];
        }
    }

    /**
     * Get the max value for column position for a given post/gallery.
     */
    //private function getMaxPositionForGallery( $post_id ) {
    //    $sql = "SELECT MAX(position) AS position FROM galleries WHERE post_id = :post_id";
    //    $stmt = self::$_pdo->prepare( $sql );
    //    $stmt->bindParam( ':post_id', $post_id, PDO::PARAM_INT );
    //    $stmt->execute();
    //    return $stmt->fetch()->position;
    //}

    /**
     * Find images related to a specific post.
     *
     * @param integer $post_id
     * @return bool|UsersModel
     */
    public function findByPost( $post_id ) {
        $selectStmt = self::$_pdo->prepare(
            "SELECT
                  id
                , post_id
                , position
                , caption
                , created
                , updated
             FROM images
             WHERE post_id = :post_id"
        );

        $selectStmt->bindParam( ':post_id', $post_id, PDO::PARAM_INT );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, '\lsm\classes\ImagesModel' );
        $users = $selectStmt->fetch();
        $selectStmt->closeCursor();

        if ( $selectStmt->rowCount() != 1 ) {
            return false;
        }

        return $users;
    }

}
