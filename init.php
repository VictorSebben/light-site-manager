<?php
header( 'Content-Type: text/html; Charset=UTF-8' );
session_start();

define( 'LIBS_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR );
define( 'CONTROLLERS_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR );
define( 'MODELS_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR );
define( 'MAPPERS_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'mappers' . DIRECTORY_SEPARATOR );

define( 'DEBUG', true );

// require the Base class
require_once( 'conf/class.Base.php' );

// autoload classes
spl_autoload_register( function ( $className ) {
    if ( is_readable( LIBS_DIR . 'class.' . $className . '.php' ) ) {
        require LIBS_DIR . 'class.' . $className . '.php';
    } else if ( is_readable( CONTROLLERS_DIR . 'class.' . $className . '.php' ) ) {
        require CONTROLLERS_DIR. 'class.' . $className . '.php';
    } else if ( is_readable( MODELS_DIR . 'class.' . $className . '.php' ) ) {
        require MODELS_DIR . 'class.' . $className . '.php';
    } else if ( is_readable( MAPPERS_DIR . 'class.' . $className . '.php' ) ) {
        require MAPPERS_DIR . 'class.' . $className . '.php';
    }
});
