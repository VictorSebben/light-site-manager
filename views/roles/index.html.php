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

<h2>Gerenciar Roles</h2>

<?php if ( isset( $msg ) ): ?>
    <div class="flash <?= $msgClass ?>">
        <?= $msg ?>
    </div>
<?php endif; ?>

<div class="form-h">
    <fieldset>
        <legend>Criar Role</legend>
        <form action="<?= $this->Url->make( 'roles/insert' ) ?>" method="post">
            <div class="form-field">
                <label for="name">Nome:</label>
                <input id="name" type="text" name="name" maxlength="50" required>
            </div>

            <!-- Token field -->
            <input type="hidden" name="token" value="<?= H::generateToken() ?>">

            <div class="form-field"><input type="submit" class="input-submit" name="submit" value="Criar Role"></div>
        </form>
    </fieldset>
</div>

<h3>Roles</h3>
<table>
    <thead>
    <tr>
        <th>Id</th>
        <th>Nome</th>
        <th>Editar</th>
        <th>Excluir</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ( $this->objectList as $role ): ?>
        <tr>
            <td><?= $role->id ?></td>
            <td><?= $role->name ?></td>
            <td>
                <a class="input-submit btn-edit" href="<?= $this->Url->make( "roles/{$role->id}/edit" ) ?>">Editar</a>
            </td>
            <td>
                <a class="input-submit btn-delete" href="<?= $this->Url->make( "roles/{$role->id}/delete" ) ?>">Excluir</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
