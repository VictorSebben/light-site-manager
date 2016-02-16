<?php
// Check if it is an update operation (this will affect the page's heading)
$isUpdate = ( isset( $this->object ) && is_numeric( $this->object->id ) );

if ( isset( $_SESSION[ 'err-msg' ] ) ) {
    $msg = '<ul>';

    foreach ( json_decode( H::flash( 'err-msg' ) ) as $errMsg ) {
        $msg .= "<li>{$errMsg}</li>";
    }

    $msg .= '</ul>';

    $msgClass = 'err-msg';
}
?>
<h2><?= ( $isUpdate ) ? "Editar" : "Criar" ?> Usuário</h2>

<?php if ( isset( $msg ) ): ?>
    <div class="flash <?= $msgClass ?>">
        <?= $msg ?>
    </div>
<?php endif; ?>

<div class="form-h">
    <form action="<?= ( $isUpdate ) ? $this->Url->make( 'users/update' ) : $this->Url->make( 'users/insert' ) ?>" method="post">
        <div class="form-field">
            <label for="name">Nome</label>
            <div class="input-field">
                <input id="name" type="text" name="name" maxlength="64" required
                       value="<?= ( isset( $this->object->name ) ) ? $this->object->name : ''; ?>">
            </div>
        </div>
        <div class="form-field">
            <label for="email">E-mail</label>
            <div class="input-field">
                <input id="email" type="text" name="email" maxlength="64" required
                       value="<?= ( isset ( $this->object->email ) ) ? $this->object->email : ''; ?>">
            </div>
        </div>
        <div class="form-field-radio">
            <h3>Status</h3>
            <div class="form-field">
                <label for="status-active">Ativo</label>
                <div class="input-field">
                    <input id="status-active" type="radio" name="status" value="<?= UserModel::STATUS_ACTIVE ?>"
                        <?= ( isset ( $this->object->status ) && $this->object->status ) ? 'checked' : '' ?>>
                </div>
            </div>
            <div class="form-field">
                <label for="status-inactive">Inativo</label>
                <div class="input-field">
                    <input id="status-inactive" type="radio" name="status" value="<?= UserModel::STATUS_INACTIVE ?>"
                        <?= ( isset ( $this->object->status ) && ( ! $this->object->status ) ) ? 'checked' : '' ?>>
                </div>
            </div>
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
                <label for="password">Senha</label>
                <div class="input-field"><input id="password" type="password" name="password" maxlength="128" <?= ( $isUpdate ) ? '' : 'required'; ?>></div>
            </div>
            <div class="form-field">
                <label for="password-confirm">Confirmar senha</label>
                <div class="input-field"><input id="password-confirm" type="password" name="password-confirm" maxlength="128" <?= ( $isUpdate ) ? '' : 'required'; ?>></div>
            </div>
        </div>

        <!-- Token field -->
        <input type="hidden" name="token" value="<?= H::generateToken() ?>">
        <!-- Id field -->
        <input type="hidden" name="id" value="<?= $this->object->id ?>">

        <div class="form-field"><input type="submit" class="input-submit" name="submit" value="Enviar"></div>
    </form>
</div>
<div class="go-back">
    <a id="go-back" class="go-back" href="">Voltar</a>
</div>
