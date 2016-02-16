<h2>Remover Post</h2>

<div class="form-field confirm-delete">
    <h3>Deseja realmente remover o Post "<?= $this->object->title ?>"?</h3>
    <form action="<?= $this->Url->make( "posts/destroy" ) ?>" method="post">
        <div class="form-field">
            <input class="input-submit" type="submit" name="delete" value="Confirmar">
        </div>

        <!-- Token field -->
        <input type="hidden" name="token" value="<?= H::generateToken() ?>">
        <!-- Id field -->
        <input type="hidden" name="id" value="<?= $this->object->id ?>">
    </form>
    <a id="go-back" class="go-back input-submit" href="#">Voltar</a>
</div>
<script src="<?= $this->Url->make( 'js/lsmhelper.js' ); ?>" type="text/javascript"></script>
