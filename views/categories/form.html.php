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
<h2><?= ( $isUpdate ) ? "Editar" : "Criar" ?> Categoria</h2>

<?php if ( isset( $msg ) ): ?>
    <div class="flash <?= $msgClass ?>">
        <?= $msg ?>
    </div>
<?php endif; ?>

<div class="form-h">
    <form action="<?= ( $isUpdate ) ? $this->Url->make( 'categories/update' ) : $this->Url->make( 'categories/insert' ) ?>" method="post">
        <div class="form-field">
            <label for="name">Nome</label>
            <div class="input-field">
                <input id="name" type="text" name="name" maxlength="64" required
                       value="<?= ( isset( $this->object->name ) ) ? $this->object->name : ''; ?>">
            </div>
        </div>
        <div class="form-field">
            <label for="description">Descrição</label>
            <div class="input-field">
                <input id="description" type="text" name="description" maxlength="64"
                       value="<?= ( isset ( $this->object->description ) ) ? $this->object->description : ''; ?>">
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
    <a id="go-back" class="go-back" onclick="H.goBack()" href="#">Voltar</a>
</div>

<script src="<?= $this->Url->make( 'js/lsmhelper.js' ); ?>" type="text/javascript"></script>
