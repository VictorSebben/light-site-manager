<div class="console">
    <?php
    if ( $this->editCat ):
    ?>
    <!-- Adicionar -->
    <a href="<?= $this->Url->create(); ?>" class="input-submit btn-green">Adicionar</a>

    <!-- Excluir -->
    <button id="btn-delete" name="btn-delete" class="input-submit btn-red">Excluir</button>

    <?php
    endif;
    ?>

    <div class="search" title="Pode usar parte do nome ou email">
        <form id="users-search-form" class="search-form" action="<?= $this->Url->index(); ?>">
            <div class="form-field">
                <input placeholder="Pesquisar Categorias" title="Pode-se pesquisar por nome ou descrição"
                       id="search" type="text" name="search" value="<?= \lsm\libs\Request::getInstance()->getInput( 'search', false ); ?>">
            </div>
            <input class="input-submit" type="submit" value="Buscar">
            <a href="<?= $this->Url->make( 'categories/index' ) ?>">Limpar pesquisa</a>
        </form>
    </div>
</div>

<h2 id="area-header">Categorias</h2>

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

<?php if ( $this->objectList != null ): ?>
<table>
    <thead>
    <tr>
        <th><input id="toggle-all" type="checkbox" name="toggle-all" title="Selecionar todas"></th>
        <th><?= $this->makeOrderByLink( 'Nome', 'name' ); ?></th>
        <th><?= $this->makeOrderByLink( 'Descrição', 'description' ); ?></th>
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
                <a href="<?= $this->Url->make( "posts/{$category->id}/index" ) ?>">
                    <?= $category->posts_count; ?> - Visualizar
                </a>
            </td>
            <?php if ( $this->editCat ) : ?>
                <td>
                    <a class="input-submit btn-edit" href="<?= $this->Url->edit( $category->id ); ?>">Editar</a>
                </td>
                <td>
                    <a class="input-submit btn-delete" href="<?= $this->Url->delete( $category->id ); ?>">Excluir</a>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>
<p class="msg-notice">Não há categorias cadastradas.</p>
<?php endif; ?>

<!-- Token field -->
<input id="token" type="hidden" name="token" value="<?= \lsm\libs\H::generateToken() ?>">
