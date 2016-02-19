<?php
// Check if it is an update operation (this will affect the page's heading)
$isUpdate = ( isset( $this->object ) && is_numeric( $this->object->id ) );

$catId = null;

if ( isset( $this->cat ) ) {
    $catId = $this->cat;
} else if ( isset( $this->object->category_id ) ) {
    $catId = $this->object->category_id;
}

if ( isset( $_SESSION[ 'err-msg' ] ) ) {
    $msg = '<ul>';

    foreach ( json_decode( H::flash( 'err-msg' ) ) as $errMsg ) {
        $msg .= "<li>{$errMsg}</li>";
    }

    $msg .= '</ul>';

    $msgClass = 'err-msg';
}
?>
<h2><?= ( $isUpdate ) ? "Editar" : "Criar" ?> Post</h2>

<?php if ( isset( $msg ) ): ?>
    <div class="flash <?= $msgClass ?>">
        <?= $msg ?>
    </div>
<?php endif; ?>

<div class="form-h">
    <form action="<?= ( $isUpdate ) ? $this->Url->make( 'posts/update' ) : $this->Url->make( 'posts/insert' ) ?>" method="post">
        <div class="form-field">
            <label for="category">Categoria</label>
            <div class="input-field">
                <select id="category" name="category">
                    <option value=""></option>
                    <?php foreach ( $this->objectList as $cat ): ?>
                    <option <?= ( $catId == $cat->id ) ? 'selected' : ''; ?> value="<?= $cat->id; ?>">
                        <?= $cat->name; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-field">
            <label for="title">Título</label>
            <div class="input-field">
                <input id="title" type="text" name="title" maxlength="64" required
                       value="<?= ( isset( $this->object->title ) ) ? $this->object->title : ''; ?>">
            </div>
        </div>
        <div class="form-field">
            <label for="intro">Chamada</label>
            <div class="input-field">
                <input id="intro" type="text" name="intro"
                       value="<?= ( isset( $this->object->intro ) ) ? $this->object->intro : ''; ?>">
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
        <div class="editor-field">
            <textarea name="post-text" id="post-editor"><?= ( isset( $this->object->post_text ) ) ? $this->object->post_text : ''; ?></textarea>
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

<script src="vendor/ckeditor/ckeditor/ckeditor.js" type="text/javascript"></script>
<script>
    CKEDITOR.replace( 'post-editor' );
</script>
