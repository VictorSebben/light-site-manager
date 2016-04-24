<!-- TODO Mostrar imagem, se já tem, permitindo excluir -->
<div id="container" class="images-wrapper">

    <div class='row1 cf'>
        <div class='col1'>
            <form action="#" method="post" enctype="multipart/form-data">
                <div id='sel-img' class='sel-img'>
                    <label for='img'>Selecione uma ou mais imagens</label>
                    <input type='file' multiple name='img' id='img'>
                </div>
            </form>
        </div>
        <div class='col2 messages'>
            <div id='images-messages'></div>
        </div>
    </div>

    <div id='image-list-wrap' class='image-list-wrap row2 cf'>
    <!--
        This “template” is the same used in images.js. Whatever is changed here has to be changed
        there as well and vice-versa.
    -->
    <?php if ($this->images) : ?>
        <?php foreach ($this->images AS $img): ?>
            <div class='preview-wrap' data-id='<?= $img->id ?>' data-position='<?= $img->position ?>'
                 data-extension='<?= $img->extension ?>'>
                <div class='btn-action position'>posicionar</div>
                <div class='tbl'>
                    <div class='tblcell'>
                        <img class='preview' src='<?= $img->path() ?>'>
                    </div>
                </div>
                <div class='actions cf'>
                    <div class='btn-action remove'>remover</div>
                    <div class='btn-action crop'>recortar</div>
                </div>
            </div>
        <?php endforeach ?>
    <?php endif ?>
    </div>


</div>
