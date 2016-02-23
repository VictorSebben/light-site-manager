<?php
$msgClass = false;

if ( isset( $_SESSION[ 'success-msg' ] ) ) {
    $msg = H::flash( 'success-msg' );
    $msgClass = 'success-msg';
} else if ( isset( $_SESSION[ 'err-msg' ] ) ) {
    $msg = H::flash( 'err-msg' );
    $msgClass = 'err-msg';
}
?>

<div class="console">
    <!-- Adicionar -->
    <a href="<?= $this->Url->make( "users/create" ) ?>" class="input-submit btn-green">Adicionar</a>

    <div class="console-toggle">
        <!-- Ativar -->
        <button id="btn-activate" name="btn-activate" class="input-submit">Ativar</button>

        <!-- Desativar -->
        <button id="btn-deactivate" name="btn-deactivate" class="input-submit">Desativar</button>
    </div>

    <!-- Excluir -->
    <button id="btn-delete" name="btn-delete" class="input-submit btn-red">Excluir</button>

    <div class="search" title="Pode usar parte do nome ou email">
        <form id="users-search-form" class="search-form" action="<?= $this->Url->make( 'users/' ) ?>">
            <div class="form-field">
                <input placeholder="Pesquisar Usuários" title="Pode-se pesquisar por nome ou e-mail"
                       id="search" type="text" name="search" value="<?= Request::getInstance()->getInput( 'search', false ); ?>">
            </div>
            <input class="input-submit" type="submit" value="Buscar">
            <a href="<?= $this->Url->make( 'users/' ) ?>">Limpar pesquisa</a>
        </form>
    </div>
</div>

<h2 id="area-header">Users</h2>

<?php if ( isset( $msg ) ): ?>
    <div class="flash <?= $msgClass ?>">
        <?= $msg ?>
    </div>
<?php endif; ?>

<?php if ( $this->objectList != null ): ?>
<table>
    <thead>
    <tr>
        <th><input id="toggle-all" type="checkbox" name="toggle-all" title="Selecionar todos"></th>
        <th><?= $this->makeOrderByLink( 'Nome', 'name' ); ?></th>
        <th><?= $this->makeOrderByLink( 'E-mail', 'email' ); ?></th>
        <th>Status</th>
        <?php if ( $this->editOtherUsers ) : ?>
        <th>Editar</th>
        <th>Remover</th>
        <?php endif; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ( $this->objectList as $user ) : ?>
        <tr>
            <td>
                <?php if ( $this->disableOwnUser || ( $user->id != $_SESSION[ 'user' ] ) ) : ?>
                <input type="checkbox" class="list-item" name="li[]" value="<?= $user->id ?>">
                <?php endif; ?>
            </td>
            <td><?= $user->name; ?></td>
            <td><?= $user->email; ?></td>
            <td>
                <?php if ( $this->disableOwnUser || ( $user->id != $_SESSION[ 'user' ] ) ) : ?>
                <div class="onoffswitch" title="<?= $user->getStatus( true ); ?>">
                    <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox"
                           value="<?= $user->id ?>"
                           id="onoffswitch-<?= $user->id ?>" <?= ( $user->status ) ? "checked" : "" ?>>
                    <label class="onoffswitch-label" for="onoffswitch-<?= $user->id ?>">
                        <span class="onoffswitch-inner"></span>
                        <span class="onoffswitch-switch"></span>
                    </label>
                </div>
                <?php
                else :
                    echo "<span class='status'>{$user->getStatus( true )}</span>";
                endif;
                ?>
            </td>
            <?php if ( $this->editOtherUsers ) : ?>
            <td>
                <a class="input-submit btn-edit" href="<?= $this->Url->make( "users/{$user->id}/edit" ) ?>">Editar</a>
            </td>
            <td>
                <?php if ( $this->disableOwnUser || ( $user->id != $_SESSION[ 'user' ] ) ) : ?>
                <a class="input-submit btn-delete" href="<?= $this->Url->make( "users/{$user->id}/delete" ) ?>">Excluir</a>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>
<p class="msg-notice">Não há usuários cadastrados.</p>
<?php endif; ?>

<!-- Token field -->
<input id="token" type="hidden" name="token" value="<?= H::generateToken() ?>">
<input id="me-myself-and-i" type="hidden" name="me-myself-and-i" value="<?= $_SESSION[ 'user' ] ?>">
