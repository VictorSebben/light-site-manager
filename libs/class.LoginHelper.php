<?php

class LoginHelper extends Base {

    private $_strPassword;
    private $_strEmail;

    /***** Validate creation of email/id and password *****/
    public function chkPassMin() {
        if ( strlen($this->_strPassword) < 10 ) {
            return false;
        }

        return true;
    }

    public function chkPassMax() {
        if ( strlen($this->_strPassword) > 128 ) {
            return false;
        }

        return true;
    }

    /**
     * Checks for password complexity.
     * Returns an array showing if the 4
     * rules of complexity are met, and
     * if 3 identical characters in a row
     * were found.
     *
     * @return array
     */
    public function chkPassComplexity() {
        // we'll count the amount of complexity rules met.
        // To be safe, the password must meet at least 3.
        $arrRules = [
            'upper' => false, // upper-case letter
            'lower' => false, // lower-case letter
            'digit' => false, // numerical value
            'schar' => false  // special characters
        ];

        // check if there are three identical characters in a row:
        $arrRules[ 'trow' ] = false;

        for ( $i = 0, $count = 1; $i < strlen( $this->_strPassword ); $i++ ) {
            $char = $this->_strPassword[ $i ];

            if ( ctype_upper( $char ) ) {
                $arrRules[ 'upper' ] = true;
            } else if ( ctype_lower( $char ) ) {
                $arrRules[ 'lower' ] = true;
            } else if ( ctype_digit( $char ) ) {
                $arrRules[ 'digit' ] = true;
            } else if ( H::isSpecialChar( $char ) ) {
                $arrRules[ 'schar' ] = true;
            }

            // test if it's third character in a row
            if ( $i != 0 ) {
                if ( $char == $this->_strPassword[ $i - 1 ] ) {
                    $count++;
                    if ( $count == 3 ) {
                        // three identical chars in a row:
                        // set trow to true
                        $arrRules[ 'trow' ] = true;
                        $count = 0;
                    }
                }
            }
        }

        return $arrRules;
    }

    public function chkLogin() {
        if ( ! isset( $_SESSION[ 'userid' ] ) || ! isset( $_SESSION[ 'username' ] ) ) {
            // This if prevents infinite redirections. @TODO: Find a better, more elegant way.
            if ( ! strstr( $_SERVER[ 'REQUEST_URI' ], 'login' ) ) {
                header( 'Location: ' . $this->_config['base_url'] . '/login' );
            }
        }
    }

    public function login( $email = null, $password = null ) {
        if ( empty( $email ) || empty( $password ) ) {
            return false;
        }

        $userMapper = new UserMapper();

        // UserMapper::findByEmail() will return a user in case
        // it finds 1 entry for the e-mail given, and false otherwise
        $user = $userMapper->findByEmail( $email );

        // if user was not found, return false
        if ( ! $user ) return false;
        // else, check password
        else {
            if ( password_verify( $password, $user->password ) ) {
                // test if password needs rehash (in case PHPs implementation
                // of the hash function has changed the default algorithm)
                if ( password_needs_rehash( $user->password, PASSWORD_DEFAULT ) ) {
                    $user->password = password_hash( $password, PASSWORD_DEFAULT );
                    $userMapper->save( $user );
                }

                $_SESSION[ 'user' ] = $user->id;
                $_SESSION[ 'userid' ] = password_hash( $password, PASSWORD_DEFAULT );
                $_SESSION[ 'username' ] = $user->name;
                session_write_close();

                return true;
            }
        }

        return false;
    }

    /**
     * Logs user out destroying session data.
     */
    public function logout() {
        unset( $_SESSION[ 'userid' ] );
        unset( $_SESSION[ 'username' ] );
        session_destroy();
        header( 'Location: ./' );
    }
}

