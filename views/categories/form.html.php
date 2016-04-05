<h2><?= ( $this->object->id ) ? "Editar" : "Criar" ?> Categoria</h2>

<?php if ( isset( $this->flashMsg[ 'success' ] ) ) : ?>
    <div class="flash success-msg">
        <?= $this->flashMsg[ 'success' ]; ?>
    </div>
<?php endif; ?>
<?php if ( isset( $this->flashMsg[ 'err' ] ) ) : ?>
    <div class="flash err-msg">
        <?= $this->flashMsg[ 'err' ]; ?>
    </div>
<?php endif; ?>

<div class="form-h">
    <form action="<?= ( $this->object->id ) ? $this->Url->update() : $this->Url->insert() ?>" method="post">
        <div class="form-field">
            <label for="name">Nome</label>
            <div class="input-field">
                <input id="name" type="text" name="name" maxlength="64" required
                       value="<?= $this->object->name; ?>">
            </div>
        </div>
        <div class="form-field">
            <label for="description">Descrição</label>
            <div class="input-field">
                <input id="description" type="text" name="description" maxlength="64"
                       value="<?= $this->object->description; ?>">
            </div>
        </div>

        <!-- Token field -->
        <input type="hidden" name="token" value="<?= H::generateToken() ?>">
        <!-- Id field -->
        <input type="hidden" name="id" value="<?= $this->object->id; ?>">

        <div class="form-field"><input type="submit" class="input-submit" name="submit" value="Enviar"></div>
    </form>
</div>
<div class="go-back">
    <a id="go-back" class="go-back" href="#">Voltar</a>
</div>
