<?php

// LOGIN
$app->map(
    '/login/run/?',
    array( 'controller' => 'Login', 'method' => 'run' )
);
$app->map(
    '/login/?',
    array( 'controller' => 'Login', 'method' => 'index' )
);

// CONFIG
$app->map(
    '/config/',
    array( 'controller' => 'Config' , 'method' => 'index' )
);

// ROLES
$app->map(
    '/roles/?',
    array( 'controller' => 'Role', 'method' => 'index' )
);
$app->map(
    '/roles/insert/?',
    array( 'controller' => 'Role', 'method' => 'insert' )
);
$app->map(
    '/roles/\d+/edit/?',
    array( 'controller' => 'Role', 'method' => 'edit' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/roles/update/?',
    array( 'controller' => 'Role', 'method' => 'update' )
);
$app->map(
    '/roles/\d+/delete/?',
    array( 'controller' => 'Role', 'method' => 'delete' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/roles/destroy/?',
    array( 'controller' => 'Role', 'method' => 'destroy' )
);

// HOME
$app->map(
    '/',
    array( 'controller' => 'Home', 'method' => 'index' )
);
$app->map(
    '/home',
    array( 'controller' => 'Home', 'method' => 'index' )
);

// USERS
$app->map(
    '/users/[[:alpha:]]+/\d+/show',
    array( 'controller' => 'User', 'method' => 'show' ),
    array( 'args' => array( 'cat', 'id' ) )
);

$app->map(
    '/users/\d+/edit',
    array( 'controller' => 'User', 'method' => 'edit' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/users/\d+/update',
    array( 'controller' => 'User', 'method' => 'update' )
);
$app->map(
    '/users/\d+/delete',
    array( 'controller' => 'User', 'method' => 'delete' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/users/\d+/destroy',
    array( 'controller' => 'User', 'method' => 'destroy' )
);
$app->map(
    '/users/create',
    array( 'controller' => 'User', 'method' => 'create' )
);
$app->map(
    '/users/store',
    array( 'controller' => 'User', 'method' => 'store' )
);
$app->map(
    '/users/\d+/?(view)?/?',
    array( 'controller' => 'User', 'method' => 'show' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
// ?.* means that there may be more things in the url. Those will be
// search and pagination stuff.
    '/users(/?|/.*)',
    array( 'controller' => 'User', 'method' => 'index' )
);
$app->map(
    '.*',
    array( 'controller' => 'Home', 'method' => 'notFound' )
);
