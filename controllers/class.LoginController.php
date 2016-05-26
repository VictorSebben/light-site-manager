<?php

namespace lsm\controllers;

use lsm\libs\LoginHelper;
use lsm\libs\View;
use lsm\libs\Url;
use lsm\libs\Request;
use lsm\libs\H;

class LoginController {

    /**
     * @var LoginHelper
     */
    protected $_loginHelper;

    protected $_url;

    public function __construct() {
        $this->_loginHelper = new LoginHelper();

        $this->_view = new View( null );

        $this->_url = new Url();
    }

    public function index() {
        $this->_view->render( 'login/index' );
    }

    public function run() {
        $request = Request::getInstance();

        $email = $request->getInput( 'email' );
        $password = $request->getInput( 'password' );

        if ( $this->_loginHelper->login( $email, $password ) ) {
            header( 'Location: ' . $this->_url->make( '' ) );
            // TODO -> adjust later to redirect to ifnull("the page the user originally wanted to go", "index page")
        } else {
            H::flash( 'login-error', 'E-mail e/ou senha incorreto(s).' );
            header( 'Location: ' . $this->_url->make( 'login/' ) );
        }

        exit;
    }
}
