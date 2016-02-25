<input type="hidden" value="<?= $this->object->id; ?>" name="post-id" id="post-id">

<div id="container">
    <!-- Form to insert a new video entry -->
    <form id="video-insert" class="video-insert">
        <div class="video-info form-field">
            <input placeholder="Título" type="text" id="insert-title" name="title">
            <input placeholder="Posição" type="text" id="insert-position" name="position">
            <input placeholder="iframe" type="text" id="insert-iframe" name="iframe">
        </div>
        <div id="video-preview" class="video-preview">
            <span id="insert-preview" class="preview">Preview</span>
        </div>
        <div class="insert-btn-msg">
            <button class="input-submit btn-green" id="btn-insert">Inserir</button>
            <span id="msg" class="msg"></span>
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
                                <span class="info">Título: <span id="title"><?= $video->title; ?></span></span>
                                <span class="info">Posição: <span id="position"><?= $video->position; ?></span></span>
                            </div>
                            <div class="video-preview">
                                <span id="preview" class="preview">Preview</span>
                            </div>
                            <span class="remove"><button title="Remover" class="btn-red input-submit fa fa-times"></button></span>
                        </div>
                        <div class="iframe">
                            <span class="info">iframe:</span>
                            <div></div>
                        </div>

                        <input type="hidden" id="<?= $video->id ?>" value="<?= $video->id ?>" name="id-<?= $video->id ?>">
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
