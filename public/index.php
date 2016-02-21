<?php

require_once '../init.php';

$app = Router::getInstance();

// TODO Check if *URL - baseUrl* has '/lsm/'
if ( strpos( $_SERVER[ "REQUEST_URI" ], "/{$app->getConfig()[ 'admin_path' ]}/" ) !== false ) {
    $app->setNamespace( 'lsm' );

    // Require admin routes
    require_once '../routes.php';

    $loginHelper = new LoginHelper();
    $loginHelper->chkLogin();

    if ( isset( $_GET[ 'logout' ] ) ) {
        $loginHelper->logout();
    }
}

// TODO Require site (public) routes

$app->start();
