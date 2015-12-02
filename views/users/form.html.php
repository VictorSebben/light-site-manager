<?php
$isUpdate = isset( $this->user );
?>
<h2><?= ( $isUpdate ) ? "Editar" : "Criar" ?> Usuário</h2>

<div class="form-h">
    <form action="<?= $this->Url->make( 'users/insert' ) ?>" method="post">
        <div class="form-field">
            <label for="name">Nome:</label>
            <div class="input-field"><input id="name" type="text" name="name" maxlength="64" required></div>
        </div>
        <div class="form-field">
            <label for="email">E-mail:</label>
            <div class="input-field"><input id="email" type="text" name="email" maxlength="64" required></div>
        </div>

        <div class="alt-password-form">
            <?php
            if ( $isUpdate ) :
            ?>
            <h3>Alterar senha</h3>
            <p>preencha os campos abaixo se deseja alterar sua senha</p>
            <?php
            endif;
            ?>
            <div class="form-field">
                <label for="password">Senha:</label>
                <div class="input-field"><input id="password" type="password" name="password" maxlength="128" <?= ( $isUpdate ) ? '' : 'required'; ?>></div>
            </div>
            <div class="form-field">
                <label for="password-confirm">Confirmar senha:</label>
                <div class="input-field"><input id="password-confirm" type="password" name="password-confirm" maxlength="128" <?= ( $isUpdate ) ? '' : 'required'; ?>></div>
            </div>
        </div>

        <!-- Token field -->
        <input type="hidden" name="token" value="<?= H::generateToken() ?>">

        <div class="form-field"><input type="submit" class="input-submit" name="submit" value="Enviar"></div>
    </form>
</div>
