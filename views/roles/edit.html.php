<?php
$msgClass = false;

if ( isset( $_SESSION[ 'success-msg' ] ) ) {
    $msg = H::flash( 'success-msg' );
    $msgClass = 'success-msg';
} else if ( isset( $_SESSION[ 'err-msg' ] ) ) {
    $msg = '<ul>';

    foreach ( json_decode( H::flash( 'err-msg' ) ) as $errMsg ) {
        $msg .= "<li>{$errMsg}</li>";
    }

    $msg .= '</ul>';

    $msgClass = 'err-msg';
}
?>

<h2>Editar Role</h2>

<?php if ( isset( $msg ) ): ?>
<div class="flash <?= $msgClass ?>">
    <?= $msg ?>
</div>
<?php endif; ?>

<div class="form-h">
    <form action="<?= $this->Url->make( 'roles/update' ) ?>" method="post">
        <div class="form-field">
            <label for="name">Nome:</label>
            <div class="input-field"><input id="name" type="text" name="name" maxlength="50"  value="<?= $this->object->name ?: ''; ?>"></div>
        </div>
        <div class="form-h-check">
            <h3>Selecionar Permissões</h3>
            <ul>
                <?php foreach ( $this->permArray as $perm ): ?>
                <li>
                    <label for="perms-<?= $this->object->id  . '-' . $perm ?>"><?= $perm ?></label>
                    <input type="checkbox" id="perms-<?= $this->object->id . '-' . $perm ?>" name="perms[]" value="<?= $perm ?>"
                        <?= ( $this->object->hasPerm( $perm ) ) ? 'checked' : '' ?>>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Token field -->
        <input type="hidden" name="token" value="<?= H::generateToken() ?>">
        <!-- Id field -->
        <input type="hidden" name="id" value="<?= $this->object->id ?>">

        <div class="form-field"><input type="submit" class="input-submit" name="submit" value="Enviar"></div>
    </form>
</div>
<div class="go-back">
    <a id="go-back" class="go-back" href="#">Voltar</a>
</div>

<script src="<?= $this->Url->make( 'js/lsmhelper.js' ); ?>" type="text/javascript"></script>
