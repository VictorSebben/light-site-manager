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

    <div class="search" title="Pesquisar por título">
        <form id="users-search-form" class="search-form" action="<?= $this->Url->index( false ) ?>">
            <div class="form-field">
                <input placeholder="Pesquisar Séries" title="Pesquisar por título"
                       id="search" type="text" name="search" value="<?= \lsm\libs\Request::getInstance()->getInput( 'search', false ); ?>">
            </div>
            <input class="input-submit" type="submit" value="Buscar">
            <a href="<?= $this->Url->act( 'index', null, false ); ?>">Limpar pesquisa</a>
        </form>
    </div>
</div>

<h2 id="area-header">Séries</h2>

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
            <th>Status</th>
            <?php if ( $this->editContents ) : ?>
                <th>Editar</th>
                <th>Remover</th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ( $this->objectList as $series ) : ?>
            <tr>
                <td><input type="checkbox" class="list-item" name="li[]" value="<?= $series->id ?>"></td>
                <td><?= $series->title; ?></td>
                <td>
                    <div class="onoffswitch" title="<?= \lsm\models\SeriesModel::$statusString[ $series->status ] ?>">
                        <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox"
                               value="<?= $series->id ?>"
                               id="onoffswitch-<?= $series->id ?>" <?= ( $series->status ) ? "checked" : "" ?>>
                        <label class="onoffswitch-label" for="onoffswitch-<?= $series->id ?>">
                            <span class="onoffswitch-inner"></span>
                            <span class="onoffswitch-switch"></span>
                        </label>
                    </div>
                </td>
                <?php if ( $this->editContents ) : ?>
                    <td>
                        <a class="input-submit btn-edit" href="<?= $this->Url->edit( $series->id ); ?>">Editar</a>
                    </td>
                    <td>
                        <a class="input-submit btn-delete" href="<?= $this->Url->delete( $series->id ); ?>">Excluir</a>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php else: ?>
    <p class="msg-notice">Não há séries cadastradas.</p>
<?php endif; ?>

<!-- Token field -->
<input id="token" type="hidden" name="token" value="<?= \lsm\libs\H::generateToken() ?>">
