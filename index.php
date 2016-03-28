<?php

require_once 'init.php';

$app = Router::getInstance();

$loginHelper = new LoginHelper();
$loginHelper->chkLogin();

if ( isset( $_GET[ 'logout' ] ) ) {
    $loginHelper->logout();
}

$app->start();
