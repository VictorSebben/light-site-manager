<h2><?= ( $this->object->id ) ? "Editar" : "Criar" ?> Usuário</h2>

<?php if ( $this->flashMsg ) : ?>
    <div class="flash <?= $this->flashMsgClass; ?>">
        <?= $this->flashMsg; ?>
    </div>
<?php endif; ?>

<div class="form-h">
    <form action="<?= ( $this->object->id ) ? $this->Url->update() : $this->Url->insert(); ?>" method="post">
        <div class="form-field">
            <label for="name">Nome</label>
            <div class="input-field">
                <input id="name" type="text" name="name" maxlength="64" required
                       value="<?= $this->object->name; ?>">
            </div>
        </div>
        <div class="form-field">
            <label for="email">E-mail</label>
            <div class="input-field">
                <input id="email" type="text" name="email" maxlength="64" required
                       value="<?= $this->object->email; ?>">
            </div>
        </div>
        <?php if ( ! $this->object->id || ( $this->disableOwnUser || ( $this->object->id != $_SESSION[ 'user' ] ) ) ) : ?>
        <div class="form-field-radio">
            <h3>Status</h3>
            <div class="form-field">
                <label for="status-active">Ativo</label>
                <div class="input-field">
                    <input id="status-active" type="radio" name="status" value="<?= UsersModel::STATUS_ACTIVE ?>"
                        <?= ( $this->object->status ) ? 'checked' : '' ?>>
                </div>
            </div>
            <div class="form-field">
                <label for="status-inactive">Inativo</label>
                <div class="input-field">
                    <input id="status-inactive" type="radio" name="status" value="<?= UsersModel::STATUS_INACTIVE ?>"
                        <?= ( $this->object->id && ! $this->object->status ) ? 'checked' : '' ?>>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="alt-password-form">
            <?php
            if ( $this->object->id ) :
            ?>
            <h3>Alterar senha</h3>
            <p>preencha os campos abaixo se deseja alterar sua senha</p>
            <?php
            endif;
            ?>
            <div class="form-field">
                <label for="password">Senha</label>
                <div class="input-field"><input id="password" type="password" name="password" maxlength="128" <?= ( $this->object->id ) ? '' : 'required'; ?>></div>
            </div>
            <div class="form-field">
                <label for="password-confirm">Confirmar senha</label>
                <div class="input-field"><input id="password-confirm" type="password" name="password-confirm" maxlength="128" <?= ( $this->object->id ) ? '' : 'required'; ?>></div>
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
