<h2>Remover Série</h2>

<?php if ( $this->flashMsg ): ?>
    <div class="flash <?= $this->flashMsgClass; ?>">
        <?= $this->flashMsg; ?>
    </div>
<?php endif; ?>

<div class="form-field confirm-delete">
    <h3>Deseja realmente remover a Série?</h3>
    <form action="<?= $this->Url->destroy() ?>" method="post">
        <?php if ( count( $this->posts ) ) : ?>
        <div class="form-field-radio">
            <h4>Há posts relacionados a essa série. Escolha a ação a ser feita:</h4>
            <div class="form-field">
                <div class="input-field">
                    <input id="delete-posts" type="radio" name="action-posts" value="<?= \lsm\models\SeriesModel::DELETE_POSTS ?>">
                    <label for="delete-posts">Remover</label>
                </div>
            </div>
            <div class="form-field cf">
                <div class="input-field">
                    <input id="dissociate-posts" type="radio" name="action-posts" value="<?= \lsm\models\SeriesModel::DISSOCIATE_POSTS ?>">
                    <label for="dissociate-posts">
                        Desassociar (os posts serão apenas desassociados da série e o status será trocado para Inativo)
                    </label>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <input class="input-submit" type="submit" name="delete" value="Confirmar">
        <a id="go-back" class="go-back input-submit" href="#">Voltar</a>

        <!-- Token field -->
        <input type="hidden" name="token" value="<?= \lsm\libs\H::generateToken() ?>">
        <!-- Id field -->
        <input type="hidden" name="id" value="<?= $this->object->id ?>">
    </form>
</div>
