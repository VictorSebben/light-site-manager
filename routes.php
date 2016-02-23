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
// ?.* means that there may be more things in the url. Those will be
// search and pagination stuff.
$app->map(
    '/roles(/?|/.*)',
    array( 'controller' => 'Role', 'method' => 'index' )
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
    '/users/[[:alpha:]]+/\d+/show/?',
    array( 'controller' => 'User', 'method' => 'show' ),
    array( 'args' => array( 'cat', 'id' ) )
);

$app->map(
    '/users/\d+/edit/?',
    array( 'controller' => 'User', 'method' => 'edit' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/users/update/?',
    array( 'controller' => 'User', 'method' => 'update' )
);
$app->map(
    '/users/\d+/delete/?',
    array( 'controller' => 'User', 'method' => 'delete' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/users/delete/?',
    array( 'controller' => 'User', 'method' => 'deleteAjax' )
);
$app->map(
    '/users/destroy/?',
    array( 'controller' => 'User', 'method' => 'destroy' )
);
$app->map(
    '/users/create/?',
    array( 'controller' => 'User', 'method' => 'create' )
);
$app->map(
    '/users/insert/?',
    array( 'controller' => 'User', 'method' => 'insert' )
);
$app->map(
    '/users/\d+/toggle-status/?',
    array( 'controller' => 'User', 'method' => 'toggleStatus' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/users/activate/?',
    array( 'controller' => 'User', 'method' => 'activate' )
);
$app->map(
    '/users/deactivate/?',
    array( 'controller' => 'User', 'method' => 'deactivate' )
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

// CATEGORIES
$app->map(
    '/categories/\d+/edit/?',
    array( 'controller' => 'Category', 'method' => 'edit' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/categories/update/?',
    array( 'controller' => 'Category', 'method' => 'update' )
);
$app->map(
    '/categories/\d+/delete/?',
    array( 'controller' => 'Category', 'method' => 'delete' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/categories/delete/?',
    array( 'controller' => 'Category', 'method' => 'deleteAjax' )
);
$app->map(
    '/categories/destroy/?',
    array( 'controller' => 'Category', 'method' => 'destroy' )
);
$app->map(
    '/categories/create/?',
    array( 'controller' => 'Category', 'method' => 'create' )
);
$app->map(
    '/categories/insert/?',
    array( 'controller' => 'Category', 'method' => 'insert' )
);
$app->map(
    '/categories/\d+/?(view)?/?',
    array( 'controller' => 'Category', 'method' => 'show' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
// ?.* means that there may be more things in the url. Those will be
// search and pagination stuff.
    '/categories(/?|/.*)',
    array( 'controller' => 'Category', 'method' => 'index' )
);

// POSTS
$app->map(
    '/posts/\d+/edit/?',
    array( 'controller' => 'Post', 'method' => 'edit' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/posts/update/?',
    array( 'controller' => 'Post', 'method' => 'update' )
);
$app->map(
    '/posts/\d+/delete/?',
    array( 'controller' => 'Post', 'method' => 'delete' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/posts/delete/?',
    array( 'controller' => 'Post', 'method' => 'deleteAjax' )
);
$app->map(
    '/posts/destroy/?',
    array( 'controller' => 'Post', 'method' => 'destroy' )
);
$app->map(
    '/posts/create/?',
    array( 'controller' => 'Post', 'method' => 'create' )
);
$app->map(
    '/posts/\d+/create/?',
    array( 'controller' => 'Post', 'method' => 'create' ),
    array( 'args' => array( 'cat' ) )
);
$app->map(
    '/posts/insert/?',
    array( 'controller' => 'Post', 'method' => 'insert' )
);
$app->map(
    '/posts/\d+/?(view)?/?',
    array( 'controller' => 'Post', 'method' => 'show' ),
    array( 'args' => array( 'id' ) )
);
// list posts by category
$app->map(
    '/posts/\d+/list/?',
    array( 'controller' => 'Post', 'method' => 'index' ),
    array( 'args' => array( 'cat' ) )
);
$app->map(
    '/posts/\d+/toggle-status/?',
    array( 'controller' => 'Post', 'method' => 'toggleStatus' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/posts/activate/?',
    array( 'controller' => 'Post', 'method' => 'activate' )
);
$app->map(
    '/posts/deactivate/?',
    array( 'controller' => 'Post', 'method' => 'deactivate' )
);
$app->map(
    '/posts/\d+/upload/?',
    array( 'controller' => 'Post', 'method' => 'uploadOneImg' ),
    array( 'args' => array( 'id' ) )
);
$app->map(
    '/posts/saveImg/?',
    array( 'controller' => 'Post', 'method' => 'saveImg' )
);
$app->map(
// ?.* means that there may be more things in the url. Those will be
// search and pagination stuff.
    '/posts(/?|/.*)',
    array( 'controller' => 'Post', 'method' => 'index' )
);

// GENERIC
$app->map(
    '.*',
    array( 'controller' => 'Home', 'method' => 'notFound' )
);
