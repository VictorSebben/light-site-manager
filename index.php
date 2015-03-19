<?php

require_once 'init.php';

$app = Router::getInstance();

require_once 'routes.php';

$app->start();
