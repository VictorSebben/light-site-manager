<!DOCTYPE html>
<html>
<head lang='pt-br'>
    <meta charset='UTF-8'>
    <title>EcomMaster - Admin</title>
    <link rel='stylesheet' type='text/css' href='<?= $this->Url->make( 'css/main.css' ); ?>'>
</head>
<body>

<div class='container'>

    <header class='header'>
        <h1><a href='<?= $this->Url->make(); ?>'>EcomMaster</a></h1>
    </header>

    <nav class='menu'>
        <ul>
            <li><a href='<?= $this->Url->make( 'users/' ); ?>'>Usuários</a></li>
            <li><a href='<?= $this->Url->make( 'banners/') ; ?>'>Banners</a></li>
            <li><a href='<?= $this->Url->make( 'produtos/'); ?>'>Produtos</a></li>
        </ul>
    </nav>

    <div class='content-wrapper'>

    <?php
    if ( isset( $this->file ) ) {
        require $this->file;
    } else {
        throw new Exception( 'View file not provided.' );
    }
    ?>

    </div>

    <footer class='footer'>
        <p>EcomMaster <?= date( 'Y', time() ); ?> &mdash; <code>(y)</code></p>
    </footer>
</div>

</body>
</html>
