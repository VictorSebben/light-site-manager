<?php

class H {

    /**
     * Helper funcion to help debugging objects or arrays
     */
    public static function ppr( $obj ) {
        echo '<pre>';
        print_r( $obj );
        echo '</pre>';
    }
}