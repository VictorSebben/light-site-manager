<?php

class H {

    /**
     * Helper function to help debugging objects or arrays
     */
    public static function ppr( $obj ) {
        echo '<pre>';
        print_r( $obj );
        echo '</pre>';
    }

    public static function isSpecialChar( $char ){
        $strAscii = '\'"@#$%&*()-_+=´`^~<>/\\?!:;|[]{}çÇ¨';

        if ( strpos( $strAscii, $char ) !== false ) {
            return true;
        }

        return false;
    }

    /**
     * Sanitizes a string, converting special chars to HTML entities.
     *
     * @param $string
     * @return string
     */
    public static function escape( $string ) {
        return htmlentities( $string, ENT_QUOTES, 'UTF-8' );
    }

    /**
     * Generates a random challenge token to protect requests from CSRF.
     * This method will store the token value generated in a session and
     * return it.
     *
     * It is intended to be used in forms that have state-changing effects
     * in the application. The value of the token should be used as a hidden
     * field in the form, to be compared with the value stored in the session
     * whenever a request is sent to the server.
     *
     * @return string
     */
    public static function generateToken() {
        return $_SESSION[ 'token' ] = md5( uniqid() );
    }

    /**
     * Gets a token value and compares it to the token value stored in the session.
     * Used together with H::generateToken() to protect sensitive forms against
     * CSRF attacks.
     *
     * @param $token
     * @return bool
     */
    public static function checkToken( $token ) {
        if ( isset( $_SESSION[ 'token' ] ) && ( $token === $_SESSION[ 'token' ] ) ) {
            unset( $_SESSION[ 'token' ] );
            return true;
        }

        return false;
    }

    /**
     * If the name specified already exists in the session, the session
     * value is returned. Otherwise, a new session entry is created, with
     * the value specified int he $msg parameter.
     *
     * @param $name
     * @param string $msg
     * @return mixed
     */
    public static function flash( $name, $msg = '' ) {
        if ( isset( $_SESSION[ $name ] ) ) {
            $text = $_SESSION[ $name ];
            unset( $_SESSION[ $name ] );
            return $text;
        } else {
            $_SESSION[ $name ] = $msg;
        }
    }

    /**
     * This method is similar to H::flash(), except that it handles a particular
     * kind of flash information: it is used to flash input info to the session
     * so that, when something goes wrong when the user uses a form, the information
     * the use had typed is not lost (and can be loaded again in the form).
     *
     * @param array $input
     * @return mixed
     */
    public static function flashInput( $input = array() ) {
        if ( count( $input ) ) {
            $_SESSION[ 'input' ] = json_encode( $input );
        }
        else {
            if ( !isset( $_SESSION[ 'input' ] ) ) {
                return array();
            }

            $input = $_SESSION[ 'input' ];
            unset( $_SESSION[ 'input' ] );
            return json_decode( $input, true );
        }
    }

    public static function ifnull() {
        foreach ( func_get_args() as $val ) {
            if ( $val || ( $val === 0 ) ) {
                return $val;
            }
        }

        return null;
    }
}
