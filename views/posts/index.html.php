<div class="console">
    <!-- Adicionar -->
    <a href="<?= $this->Url->create(); ?>" class="input-submit btn-green">Adicionar</a>

    <div class="console-toggle">
        <!-- Ativar -->
        <button id="btn-activate" name="btn-activate" class="input-submit">Ativar</button>

        <!-- Desativar -->
        <button id="btn-deactivate" name="btn-deactivate" class="input-submit">Desativar</button>
    </div>

    <!-- Excluir -->
    <button id="btn-delete" name="btn-delete" class="input-submit btn-red">Excluir</button>

    <div class="search" title="Pode usar parte do nome ou email">
        <form id="users-search-form" class="search-form" action="<?= $this->Url->make( 'posts/' ) ?>">
            <div class="form-field">
                <input placeholder="Pesquisar Posts" title="Pode-se pesquisar por título, chamada ou texto"
                       id="search" type="text" name="search" value="<?= \lsm\libs\Request::getInstance()->getInput( 'search', false ); ?>">
            </div>
            <input class="input-submit" type="submit" value="Buscar">
            <a href="<?= $this->Url->act( 'index', null, false ); ?>">Limpar pesquisa</a>
        </form>
    </div>
</div>

<h2 id="area-header">Posts</h2>

<?php if ( $this->flashMsg ): ?>
    <div class="flash <?= $this->flashMsgClass; ?>">
        <?= $this->flashMsg; ?>
    </div>
<?php endif; ?>

<?php if ( $this->objectList != null ): ?>
<table>
    <thead>
    <tr>
        <th><input id="toggle-all" type="checkbox" name="toggle-all" title="Selecionar todos"></th>
        <th><?= $this->makeOrderByLink( 'Título', 'title' ); ?></th>
        <th>Categorias</th>
        <th>Imagem</th>
        <th>Vídeos</th>
        <th>Status</th>
        <?php if ( $this->editContents ) : ?>
            <th>Editar</th>
            <th>Remover</th>
        <?php endif; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ( $this->objectList as $post ) :
        // If the post has an image uploaded, get the
        // path to show preview
        if ( $post->image ) {
            $img = $this->Url->make( "img/post/{$post->image}-M.{$post->img_ext}" );
            $imgThumb = $this->Url->make( "img/post/{$post->image}-p.{$post->img_ext}" );
        }
        // If there isn't a main image uploaded for the post,
        // show default preview image
        else {
            $img = $this->Url->make( "img/not-available-transp-300x300.png" );
            $imgThumb = $this->Url->make( "img/icons/icon-not-available-transp-20x20.png" );
        }
    ?>
        <tr>
            <td><input type="checkbox" class="list-item" name="li[]" value="<?= $post->id ?>"></td>
            <td><?= $post->title; ?></td>
            <td>
                <ul>
                    <?php foreach ( $post->categories as $category ) : ?>
                    <li><?= $category->name; ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td>
                <a title="Imagens" class="imgup icon" href="<?= $this->Url->make( "posts/galeria-de-imagens/{$post->id}/images/" ); ?>">
                    <span class="fa fa-picture-o"></span>
                </a>
            </td>
            <td>
                <a title="Vídeos" class="imgup icon" href="<?= $this->Url->make( "posts/{$post->id}/videos/" ); ?>">
                    <span class="fa fa-youtube"></span>
                </a>
            </td>
            <td>
                <div class="onoffswitch" title="<?= \lsm\models\PostsModel::$statusString[ $post->status ] ?>">
                    <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox"
                           value="<?= $post->id ?>"
                           id="onoffswitch-<?= $post->id ?>" <?= ( $post->status ) ? "checked" : "" ?>>
                    <label class="onoffswitch-label" for="onoffswitch-<?= $post->id ?>">
                        <span class="onoffswitch-inner"></span>
                        <span class="onoffswitch-switch"></span>
                    </label>
                </div>
            </td>
            <?php if ( $this->editContents ) : ?>
                <td>
                    <a class="input-submit btn-edit" href="<?= $this->Url->edit( $post->id ); ?>">Editar</a>
                </td>
                <td>
                    <a class="input-submit btn-delete" href="<?= $this->Url->delete( $post->id ); ?>">Excluir</a>
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>
    <p class="msg-notice">Não há posts cadastrados.</p>
<?php endif; ?>

<!-- Token field -->
<input id="token" type="hidden" name="token" value="<?= \lsm\libs\H::generateToken() ?>">
