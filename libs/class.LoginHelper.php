<?php

class LoginHelper extends Base {

    public function chkLogin() {
        if ( ! isset( $_SESSION[ 'userid' ] ) || ! isset( $_SESSION[ 'username' ] ) ) {
            // This if prevents infinite redirections. @TODO: Find a better, more elegant way.
            if ( ! strstr( $_SERVER[ 'REQUEST_URI' ], 'login' ) ) {
                header( 'Location: ' . $this->_config['base_url'] . '/login' );
            }
        }

        // If user is logged in, we'll check if she's not inactive or deleted
        else {
            // Validate if user can really use the system (if user has been deleted or
            // set as inactive, any functionality will be disallowed
            $userMapper = new UserMapper();
            $userMapper->selectStmt( 'SELECT status, deleted FROM users WHERE id = :id' );
            $user = $userMapper->find( $_SESSION[ 'user' ] );
            if ( ( $user->status == 0 ) || ( $user->deleted == 1 ) ) {
                echo "Usuário inexistente ou inativo";
                unset( $_SESSION[ 'userid' ] );
                unset( $_SESSION[ 'username' ] );
                session_destroy();
                exit;
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

        // If user was not found, or if it was found but is not active, return false
        if ( ! $user || ( $user->status == 0 ) || ( $user->deleted == 1 ) ) return false;
        // Else, check password
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

