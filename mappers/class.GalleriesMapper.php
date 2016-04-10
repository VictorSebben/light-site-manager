<?php

class GalleriesMapper extends Mapper {

    /**
     * @throws Exception
     */
    function __construct() {
        parent::__construct();
        $this->_selectStmt = self::$_pdo->prepare(
            "SELECT id, post_id, position, caption, created, updated FROM galleries WHERE id = ?"
        );
    }

    /**
     * @Override
     * Overrite save() because we need max(position) + 1 on insert, etc.
     *
     * @param GalleryMode $image
     * @param boolean $overrideNullData
     * @param mixed oldPKValue
     * @return AssociativeArray containing insertd `id` and inserted `position` columns.
     */
    public function save( $image, $overrideNullData = false, $oldPKValue = null ) {

        // 160wx100h

        // SQL deals with the fact that if galleires table is empty and therefore the ‘position’
        // column is null (max(position) returns null), we make it 0 + 1. Otherwise, we make
        // it “max(position) + 1”.
        $sql = "INSERT INTO galleries (
                  post_id
                , position
                , created_at)
                (SELECT
                      :post_id
                    , COALESCE(MAX(position), 0) + 1
                    , CURRENT_TIMESTAMP
                    FROM galleries
                    WHERE post_id = :post_id)
                    RETURNING id, position";

        $stmt = self::$_pdo->prepare( $sql );
        $stmt->bindParam( ':post_id', $image->post_id );
        $stmt->execute();
        return $stmt->fetch( PDO::FETCH_ASSOC );
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
             FROM galleries
             WHERE post_id = :post_id"
        );

        $selectStmt->bindParam( ':post_id', $post_id, PDO::PARAM_INT );
        $selectStmt->execute();
        $selectStmt->setFetchMode( PDO::FETCH_CLASS, 'GalleriesModel' );
        $users = $selectStmt->fetch();
        $selectStmt->closeCursor();

        if ( $selectStmt->rowCount() != 1 ) {
            return false;
        }

        return $users;
    }

}

