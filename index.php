<?php

require_once 'init.php';

$app = Router::getInstance();

require_once 'routes.php';

$app->start();
H::ppr($app);
?>

<p><a href=''>Home</a></p>
<p><a href='users'>Users</a></p>
<p><a href='users/9/delete'>Delte User</a></p>
