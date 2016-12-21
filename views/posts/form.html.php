<h2><?= ( $this->object->id ) ? "Editar" : "Criar" ?> Post</h2>

<?php if ( $this->flashMsg ): ?>
    <div class="flash <?= $this->flashMsgClass; ?>">
        <?= $this->flashMsg; ?>
    </div>
<?php endif; ?>

<div class="form-h">
    <form action="<?= ( $this->object->id ) ? $this->Url->update() : $this->Url->insert() ?>" method="post">
        <div class="form-field">
            <label for="title">Título</label>
            <div class="input-field">
                <input id="title" type="text" name="title" maxlength="64" required
                       value="<?= $this->object->title; ?>">
            </div>
        </div>
        <div class="form-field">
            <label for="intro">Chamada</label>
            <div class="input-field">
                <input id="intro" type="text" name="intro"
                       value="<?= $this->object->intro; ?>">
            </div>
        </div>
        <div class="form-h-check">
            <h3>Categorias</h3>
            <ul>
                <?php foreach ( $this->objectList as $cat ): ?>
                <li>
                    <label for="cat-<?= $cat->id; ?>"><?= $cat->name; ?></label>
                    <input type="checkbox" id="cat-<?= $cat->id; ?>" name="cat[]" value="<?= $cat->id; ?>"
                        <?= ( $this->object->hasCat( $cat->id ) ) ? 'checked' : ''; ?>>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="form-field-radio">
            <h3>Status</h3>
            <div class="form-field">
                <label for="status-active">Ativo</label>
                <div class="input-field">
                    <input id="status-active" type="radio" name="status" value="<?= \lsm\models\UsersModel::STATUS_ACTIVE ?>"
                        <?= ( $this->object->status == 1 ) ? 'checked' : '' ?>>
                </div>
            </div>
            <div class="form-field">
                <label for="status-inactive">Inativo</label>
                <div class="input-field">
                    <input id="status-inactive" type="radio" name="status" value="<?= \lsm\models\UsersModel::STATUS_INACTIVE ?>"
                        <?= ( ( $this->object->status == 0 ) ) ? 'checked' : '' ?>>
                </div>
            </div>
        </div>
        <div class="editor-field">
            <textarea name="post-text" id="post-editor"><?= $this->object->post_text; ?></textarea>
        </div>

        <!-- Token field -->
        <input type="hidden" name="token" value="<?= \lsm\libs\H::generateToken() ?>">
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
    CKEDITOR.replace(
        'post-editor',
        {
            basicEntities: false,
            htmlEncodeOutput: false,
            entities: false
        }
    );
</script>
