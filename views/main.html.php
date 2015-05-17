<!DOCTYPE html>
<html>
<head lang='pt-br'>
    <meta charset='UTF-8'>
    <title>EcomMaster - Admin</title>
    <link rel='stylesheet' type='text/css' href='<?= $this->Url->make( 'css/main.css' ); ?>'>
</head>
<body>

<div class='main-header'>
    <header class='fit'>
        <h1><a href='<?= $this->Url->make(); ?>'>EcomMaster</a></h1>
    </header>
</div>

<div class='main-container'>
    <div class='fit cf'>
        <nav class='menu'>
            <h2>Categorias</h2>
            <ul>
                <li>
                    <a href='<?= $this->Url->make( 'users/' ); ?>'>Usuários</a>
                </li>
                <li>
                    <a href='<?= $this->Url->make( 'banners/') ; ?>'>Banners</a>
                </li>
                <li>
                    <a href='<?= $this->Url->make( 'produtos/'); ?>'>Produtos</a>
                </li>
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

</body>
</html>
