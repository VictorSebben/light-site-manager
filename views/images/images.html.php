<!-- TODO Mostrar imagem, se já tem, permitindo excluir -->
<div id="container" class="images-wrapper">

    <form action="#" method="post" enctype="multipart/form-data">

        <div id='sel-img'>
            <label for='img'>Imagem:</label>
            <input type='file' multiple name='img' id='img'>
        </div>

    </form>

    <div id='image-list' class='image-list cf'>
    <?php if ($this->images) : ?>
        <?php foreach ($this->images AS $img): ?>
            <!--
                This “template” is the same used in images.js. Whatever is changed here has to be changed
                there as well and vice-versa.
            -->
            <div class='wrap' data-image-id='<?= $img->id ?>' data-post-id='<?= $img->post_id ?>'
                data-image-position='<?= $img->position ?>' data-image-extension='<?= $img->extension ?>'>
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
