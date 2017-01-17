<!DOCTYPE html>
<html>
<head lang='pt-br'>
    <meta charset='UTF-8'>
    <base href="<?= $this->Url->make(); ?>">
    <title>LSM - Light Site Manager</title>
    <link rel='stylesheet' type='text/css' href='<?= $this->Url->make( 'css/main.css' ); ?>'>
    <link rel='stylesheet' type='text/css' href='<?= $this->Url->make( 'font-awesome/css/font-awesome.min.css') ?>'>

    <?= $this->getExtraLinkTags(); ?>

    <script>
        window.lsmConf = {
            baseUrl: '<?= "{$this->_config[ 'base_url' ]}"; ?>',
            ctrl: '<?= \lsm\libs\Request::getInstance()->uriParts[ 'ctrl' ] ?>',
            pk: '<?= \lsm\libs\Request::getInstance()->uriParts[ 'pk' ] ?>'
        };
    </script>
</head>
<body>

<div class='main-header'>
    <header class='fit cf'>
        <h1><a href='<?= $this->Url->make(); ?>'>EcomMaster</a></h1>
        <div class="btn-config cf">
            <a id="btn-open-config" href="#"><?= $_SESSION[ 'username' ] ?></a>
            <div class="nav-config" id="nav-config">
                <ul>
                    <li><a href="<?= $this->Url->make( "config/index" ) ?>">Configuração</a></li>
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
                <li><a href='<?= $this->Url->make( 'users/index/' ); ?>'>Usuários</a>
                <li><a href='<?= $this->Url->make( 'categories/index/' ); ?>'>Categorias</a></li>
                <li><a href='<?= $this->Url->make( 'posts/index/') ; ?>'>Posts</a></li>
                <li><a href='<?= $this->Url->make( 'series/index/' ); ?>'>Séries</a></li>
                <li><a href='<?= $this->Url->make( 'contact/index/' ); ?>'>Contatos</a></li>
                <?php foreach ( $this->modules as $label => $module ) : ?>
                    <li><a href="<?= $this->Url->make( "{$module}/index" ) ?>"><?= $label ?></a></li>
                <?php endforeach; ?>
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
<?= $this->getExtraScriptTags(); ?>
</body>
</html>
