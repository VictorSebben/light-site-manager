<?php

require_once '../init.php';

$app = Router::getInstance();

// Require admin routes
require_once '../routes.php';

//$loginHelper = new LoginHelper();
//$loginHelper->chkLogin();

if ( isset( $_GET[ 'logout' ] ) ) {
    $loginHelper->logout();
}

$app->start();
