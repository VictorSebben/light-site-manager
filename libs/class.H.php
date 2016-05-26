<?php

namespace lsm\libs;

class H {

    /**
     * Helper function to help debugging objects or arrays.
     * @param $obj
     */
    public static function ppr( $obj ) {
        echo '<pre>';
        print_r( $obj );
        echo '</pre>';
    }

    /**
     * Helper function to help debugging objects or arrays but this time using var_dump.
     * @param $object
     */
    public static function vd( $object ) {
        echo '<pre>';
        var_dump( $object );
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
        if ( $msg ) {
            $_SESSION[ $name ] = $msg;
            return true;
        }

        $text = '';

        if ( isset( $_SESSION[ $name ] ) ) {
            $text = $_SESSION[ $name ];
            unset( $_SESSION[ $name ] );
        }

        return $text;
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

    public static function sanitizeSlashes( $uri ) {
        return preg_replace( '%/{2,}%', '/', $uri );
    }

    public static function str2Url( $str, $replace = array(), $delimiter = '-' ) {
        if( ! empty( $replace ) ) {
            $str = str_replace( ( array ) $replace, ' ', $str);
        }

        $clean = iconv( 'UTF-8', 'ASCII//TRANSLIT', $str );
        $clean = preg_replace( '/[^a-zA-Z0-9\/_|+ -]/', '', $clean );
        $clean = strtolower( trim( $clean, '-' ) );
        $clean = preg_replace( '/[\/_|+ -]+/', $delimiter, $clean );

        return $clean;
    }

    /**
     * Just return the parameter value if it exists or NULL. Values are not sanitized
     * or anything. Be careful when you use this.
     *
     * @param String $paramName - The name of the parameter to find in get, post or request.
     * @param string $method
     * @return NULL/String - The value as a string or NULL.
     */
    public static function param( $paramName, $method = 'REQUEST' ) {
        $arg = NULL;
        if ( $method === 'GET' ) {
            $arg = $_GET[ $paramName ];
        }
        else if ( $method === 'POST' ) {
            $arg = $_POST[ $paramName ];
        }
        else {
            $arg = $_REQUEST[ $paramName ];
        }

        return $arg;
    }

}
