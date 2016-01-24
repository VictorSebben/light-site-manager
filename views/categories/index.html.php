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
    <?php
    if ( $this->editCat ):
    ?>
    <!-- Adicionar -->
    <a href="<?= $this->Url->make( "categories/create" ) ?>" class="input-submit btn-green">Adicionar</a>

    <!-- Excluir -->
    <button id="btn-delete" name="btn-delete" class="input-submit btn-red">Excluir</button>

    <?php
    endif;
    ?>

    <div class="search" title="Pode usar parte do nome ou email">
        <form id="users-search-form" class="search-form" action="<?= $this->Url->make( 'categories/' ) ?>">
            <div class="form-field">
                <input placeholder="Pesquisar Categorias" title="Pode-se pesquisar por nome ou descrição"
                       id="search" type="text" name="search" value="<?= Request::getInstance()->getInput( 'search', false ); ?>">
            </div>
            <input class="input-submit" type="submit" value="Buscar">
            <a href="<?= $this->Url->make( 'categories/' ) ?>">Limpar pesquisa</a>
        </form>
    </div>
</div>

<h2 id="area-header">Categorias</h2>

<?php if ( isset( $msg ) ): ?>
    <div class="flash <?= $msgClass ?>">
        <?= $msg ?>
    </div>
<?php endif; ?>

<?php if ( $this->objectList != null ): ?>
    <table>
        <thead>
        <tr>
            <th><input id="toggle-all" type="checkbox" name="toggle-all" title="Selecionar todas"></th>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Núm. de Posts</th>
            <?php if ( $this->editCat ) : ?>
                <th>Editar</th>
                <th>Remover</th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ( $this->objectList as $category ) : ?>
            <tr>
                <td><input type="checkbox" class="list-item" name="li[]" value="<?= $category->id ?>"></td>
                <td><?= $category->name; ?></td>
                <td><?= $category->description; ?></td>
                <td>
                    <a href="<?= $this->Url->make( "posts/{$category->id}/list" ) ?>">
                        <?= $category->posts_count; ?> - Visualizar
                    </a>
                </td>
                <?php if ( $this->editCat ) : ?>
                    <td>
                        <a class="input-submit btn-edit" href="<?= $this->Url->make( "categories/{$category->id}/edit" ) ?>">Editar</a>
                    </td>
                    <td>
                        <a class="input-submit btn-delete" href="<?= $this->Url->make( "categories/{$category->id}/delete" ) ?>">Excluir</a>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <script src="<?= $this->Url->make( 'js/form.js' ); ?>" type="text/javascript"></script>

<?php else: ?>
    <p class="msg-notice">Não há categorias cadastradas.</p>
<?php endif; ?>

<!-- Token field -->
<input id="token" type="hidden" name="token" value="<?= H::generateToken() ?>">
