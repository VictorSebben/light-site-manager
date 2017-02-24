<?php
session_start();

header( 'Content-Type: text/html; Charset=UTF-8' );

define( 'ROOT_DIR', __DIR__ );
define( 'CONF_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR );

define( 'DEBUG', true );

setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');

// require the Base class
require_once( 'conf/class.Base.php' );

// autoload classes
spl_autoload_extensions( '.php' );
spl_autoload_register( function ( $class ) {
    $class = str_replace( array( 'lsm\\', '\\' ), array( '', '/' ), $class );
    $file = preg_replace( '@([^/]+)$@', 'class.$1.php', $class );
    if ( file_exists( $file ) )
        require $file;
}, true );

require __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
