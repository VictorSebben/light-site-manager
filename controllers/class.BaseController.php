<?php

namespace lsm\controllers;

use lsm\models\BaseModel;
use lsm\models\UsersModel;
use lsm\mappers\Mapper;
use lsm\mappers\UsersMapper;
use lsm\libs\Url;
use lsm\libs\View;
use lsm\libs\Request;
use lsm\libs\H;

class BaseController {

    /**
     * The Model object.
     *
     * @var BaseModel
     */
    protected $_model;

    /**
     * The Mapper object, used to deal with database operations.
     *
     * @var Mapper
     */
    protected $_mapper;

    /**
     * A Url object, used in Controllers mainly for redirection purposes.
     *
     * @var Url
     */
    protected $_url;

    /**
     * The View object.
     *
     * @var View
     */
    protected $_view;

    /**
     * @var UsersModel
     */
    protected $_user;

    public function __construct( $model_base_name ) {
        $model_name = '\lsm\models\\' . $model_base_name . 'Model';

        // instantiate Model
        $this->_model = new $model_name;
        // instantiate View
        $this->_view = new View();
        // instantiate Url
        $this->_url = new Url();

        /*
         * Populate logged User and Permissions
         * for authentication throughout the system's
         * execution
         */
        // populate User
        $usersMapper = new UsersMapper();

        $this->_user = new UsersModel();
        $this->_user->id = $_SESSION[ 'user' ];
        $usersMapper->initRoles( $this->_user );

        // If it is the edit method, set last URL in the session so we can
        // redirect the user to the same route, with the same parameters
        if ( Request::getInstance()->method === 'edit' ) {
            $this->flashRedirectTo( $_SERVER[ 'HTTP_REFERER' ] );
        }
    }

    public function flashRedirectTo( $url = '' ) {
        if ( $url ) {
            H::flash( 'redirect_to', $url );
        } else {
            return H::flash( 'redirect_to' );
        }
    }

    /**
     * Sets the flash message information (message and class) on
     * a View object
     * @param View $view
     */
    public function prepareFlashMsg( View $view ) {
        if ( isset( $_SESSION[ 'success-msg' ] ) ) {
            $view->flashMsg = H::flash( 'success-msg' );
            $view->flashMsgClass = 'success-msg';
        } else if ( isset( $_SESSION[ 'err-msg' ] ) ) {
            $errMsg = H::flash( 'err-msg' );
            $errMsg = json_decode( $errMsg ) ?: $errMsg;

            if ( is_array( $errMsg ) ) {
                $view->flashMsg = '<ul>';
                foreach ( $errMsg as $msg ) {
                    $view->flashMsg .= "<li>{$msg}</li>";
                }
                $view->flashMsg .= '</ul>';
            } else {
                $view->flashMsg = $errMsg;
            }

            $view->flashMsgClass = 'err-msg';
        }
    }
}
