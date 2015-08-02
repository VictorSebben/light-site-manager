<?php

class LoginController extends BaseController {

    /**
     * @var LoginHelper
     */
    protected $_loginHelper;

    protected $_url;

    public function __construct( $model_base_name ) {
        $login_helper_obj = $model_base_name . 'Helper';
        $this->_loginHelper = new $login_helper_obj;

        $this->_view = new View( null );

        $this->_url = new Url();
    }

    public function index() {
        $this->_view->render( 'login/index' );
    }

    public function run() {
        $request = Request::getInstance();

        if ( H::checkToken( $request->getInput( 'token' ) ) ) {
            $email = $request->getInput( 'email' );
            $password = $request->getInput( 'password' );

            if ( $this->_loginHelper->login( $email, $password ) ) {
                header( 'Location: ' . $this->_url->make( '' ) );
                // TODO -> adjust later to redirect to ifnull("the page the user originally wanted to go", "index page")
            } else {
                H::flash( 'login-error', 'E-mail e/ou senha incorreto(s).' );
                header( 'Location: ' . $this->_url->make( 'login/' ) );
            }
        }

        exit;
    }
}
