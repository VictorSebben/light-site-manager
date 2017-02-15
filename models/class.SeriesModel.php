<?php

namespace lsm\models;

class SeriesModel extends BaseModel {

    public $id;
    public $title;
    public $intro;
    public $status;

    /**
     * Array to hold CategoriesModel objects.
     * @var array
     */
    public $categories = array();

    public $tableName;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public static $statusString = [
        self::STATUS_ACTIVE => 'Publicada',
        self::STATUS_INACTIVE => 'Despublicada'
    ];

    const DELETE_POSTS = 1;       // Action on delete series: delete posts associated
    const DISSOCIATE_POSTS = 0;   // Action on delete series: dissociate posts
    const NO_POSTS = -1;          // The Series contains no posts associated

    public function __construct() {
        parent::__construct();

        $this->tableName = 'series';

        $this->rules = array(
            'title' => array( 'fieldName' => 'título', 'rules' => 'max:200' ),
            'intro' => array( 'fieldName' => 'chamada', 'rules' => 'max:200' ),
            'status' => array( 'fieldName' => 'status', 'valueIn' => array( self::STATUS_INACTIVE, self::STATUS_ACTIVE ) )
        );
    }

    public function hasCat( $catId ) {
        if ( ! is_array( $this->categories ) ) {
            return false;
        }

        return in_array( $catId, array_map( function( $cat ) {
            return $cat->id;
        }, $this->categories ) );
    }
}
