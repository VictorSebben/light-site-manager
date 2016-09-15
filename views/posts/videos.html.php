<input type="hidden" value="<?= $this->object->id; ?>" name="post-id" id="post-id">

<div id="container">
    <!-- Form to insert a new video entry -->
    <form id="video-insert" class="video-insert">
        <div class="video-info form-field">
            <input placeholder="Título" type="text" id="insert-title" name="title">
            <input placeholder="Posição" type="text" id="insert-position" name="position">
            <input placeholder="Url" type="text" id="insert-url" name="url">
        </div>
        <div id="video-preview" class="video-preview">
            <span id="insert-preview" class="preview">Preview</span>
        </div>
        <div class="insert-btn-msg">
            <button class="input-submit btn-green" id="btn-insert">Inserir</button>
            <span id="msg" class="msg err-msg"></span>
        </div>
    </form>

    <h3>Galeria de Vídeos</h3>

    <!-- List of videos already inserted. Update is done in-place -->
    <?php if ( $this->objectList != null ): ?>
    <table>
        <tbody>
        <?php foreach ( $this->objectList as $video ) : ?>
            <tr>
                <td>
                    <div id="video-item-<?= $video->id ?>" class="video-item">
                        <div class="video-info">
                            <div class="info-title-pos">
                                <div class="info">Título: <span id="title-<?= $video->id; ?>" class="title"><?= $video->title; ?></span></div>
                                <div class="info">Posição: <span id="position-<?= $video->id; ?>" class="position"><?= $video->position; ?></span></div>
                            </div>
                            <div id="video-preview-<?= $video->id; ?>" class="video-preview">
                                <?= $video->getVideoIframe(200, 100); ?>
                            </div>
                            <span class="remove"><button title="Remover" data-id="<?= $video->id; ?>" class="btn-remove btn-red input-submit fa fa-times"></button></span>
                        </div>
                        <div>
                            <span class="info">Url:</span>
                            <div id="url-<?= $video->id; ?>" class="url"><?= $video->url; ?></div>
                        </div>

                        <input type="hidden" class="video-id" value="<?= $video->id ?>" name="id-<?= $video->id ?>">
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
    <p class="msg-notice">Não há vídeos cadastrados.</p>
    <?php endif; ?>
</div>
