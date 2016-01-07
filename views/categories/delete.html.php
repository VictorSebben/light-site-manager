<h2>Remover Categoria</h2>

<div class="form-field confirm-delete">
    <h3>Deseja realmente remover a Categoria "<?= $this->object->name ?>"?</h3>
    <form action="<?= $this->Url->make( "categories/destroy" ) ?>" method="post">
        <div class="form-field">
            <input class="input-submit" type="submit" name="delete" value="Confirmar">
        </div>

        <!-- Token field -->
        <input type="hidden" name="token" value="<?= H::generateToken() ?>">
        <!-- Id field -->
        <input type="hidden" name="id" value="<?= $this->object->id ?>">
    </form>
    <a id="go-back" onclick="H.goBack()" class="input-submit" href="#">Voltar</a>
</div>
<script src="<?= $this->Url->make( 'js/lsmhelper.js' ); ?>" type="text/javascript"></script>
