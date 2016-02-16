<!DOCTYPE html>
<html>
<head lang='pt-br'>
    <meta charset='UTF-8'>
    <base href="<?= $this->Url->make(); ?>">
    <title>LSM - Light Site Manager</title>
    <link rel='stylesheet' type='text/css' href='<?= $this->Url->make( 'css/main.css' ); ?>'>

    <?php foreach ( $this->extraLink as $extraLink ) : ?>
    <link rel="stylesheet" type="text/css" href="<?= $extraLink ?>">
    <?php endforeach; ?>
</head>
<body>

<div class='main-header'>
    <header class='fit cf'>
        <h1><a href='<?= $this->Url->make(); ?>'>EcomMaster</a></h1>
        <div class="btn-config cf">
            <a id="btn-open-config" href="#"><?= $_SESSION[ 'username' ] ?></a>
            <div class="nav-config" id="nav-config">
                <ul>
                    <li><a href="<?= $this->Url->make( "config/" ) ?>">Configuração</a></li>
                    <li><a href="<?= $this->Url->make( '?logout' ) ?>">Sair</a></li>
                </ul>
            </div>
        </div>
    </header>
</div>

<div class='main-container'>
    <div class='fit cf'>
        <nav class='menu'>
            <ul>
                <li><a href='<?= $this->Url->make( 'users/' ); ?>'>Usuários</a>
                <li><a href='<?= $this->Url->make( 'categories/' ); ?>'>Categorias</a></li>
                <li><a href='<?= $this->Url->make( 'posts/') ; ?>'>Posts</a></li>
            </ul>
        </nav>

        <div class='content-wrapper'>

        <?php
        if ( isset( $this->_file ) ) {
            require $this->_file;
        } else {
            throw new Exception( 'View file not provided.' );
        }

        if ( isset( $this->_pagFile ) ) {
            require $this->_pagFile;
        }
        ?>
        </div>
    </div>
</div>

<div class='main-footer'>
    <footer class='fit'>
        <p>EcomMaster <?= date( 'Y', time() ); ?> &mdash; <code>(y)</code></p>
    </footer>
</div>

<script src="<?= $this->Url->make( 'js/menu.js' ); ?>"></script>
<?php foreach ( $this->extraScript as $src )  : ?>
<script src="<?= $src ?>"></script>
<?php endforeach; ?>
</body>
</html>
