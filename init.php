<?php
session_start();

header( 'Content-Type: text/html; Charset=UTF-8' );

define( 'ROOT_DIR', __DIR__ );
define( 'CONF_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR );

define( 'DEBUG', true );

// require the Base class
require_once( 'conf/class.Base.php' );

// autoload classes
spl_autoload_extensions( '.php' );
spl_autoload_register( function ( $class ) {
    $class = str_replace( 'lsm\\', '', $class );
    $class = explode( '\\', $class );
    if ( count( $class ) === 1 ) require "class.{$class[ 0 ]}.php";
    else require "{$class[ 0 ]}/class.{$class[ 1 ]}.php";
}, true );

require __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
