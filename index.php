<?php

require_once 'init.php';

use lsm\libs\Router;
use lsm\libs\LoginHelper;

$app = Router::getInstance();

$loginHelper = new LoginHelper();
$loginHelper->chkLogin();

if ( isset( $_GET[ 'logout' ] ) ) {
    $loginHelper->logout();
}

$app->start();
