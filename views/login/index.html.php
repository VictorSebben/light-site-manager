<?php
use lsm\libs\H;
?>

<!DOCTYPE html>
<html>
<head lang='pt-br'>
    <meta charset='UTF-8'>
    <title>EcomMaster - Admin</title>
    <link rel='stylesheet' type='text/css' href='<?= $this->Url->make( 'css/main.css' ); ?>'>
    <link rel='stylesheet' type='text/css' href='<?= $this->Url->make( 'css/login.css' ); ?>'>
</head>
<body class="login-page">
<div class="login-form">
    <fieldset>
        <legend>Login</legend>
        <form method="post" action="<?= $this->Url->make( 'login/run' ); ?>">
            <div class="login-info-input form-field">
                <label for="email">E-mail</label>
                <input id="email" type="text" name="email">
            </div>
            <div class="login-info-input form-field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="off">
            </div>

            <input class="input-submit-login" type="submit" name="login" value="Entrar">
        </form>
    </fieldset>
    <?php
    if ( isset( $_SESSION[ 'login-error' ] ) ) :
    ?>
    <div class="error-msg">
        <span><?= H::flash( 'login-error' ) ?></span>
    </div>
    <?php
    endif;
    ?>

    <a href="<?= $this->_config[ 'base_url' ]; ?>">Voltar ao Site</a>
</div>
</body>
</html>
