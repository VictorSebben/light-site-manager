<!-- TODO Mostrar imagem, se já tem, permitindo excluir -->
<div id="container">
    <form action="" method="post" enctype="multipart/form-data">

        <!-- $w e $h são as dimensões da imagem para a categoria. Vem do DB.
             O tamanho do db é multiplicado por 3 antes de chegar aqui. -->
        <input type="hidden" id="w" value="<?php echo $this->w; ?>">
        <input type="hidden" id="h" value="<?php echo $this->h; ?>">
        <input type="hidden" id="tmpname"> <!-- To be set via ajax -->
        <input type="hidden" id="randname"> <!-- To be set via ajax -->
        <input type="hidden" id="extension"> <!-- To be set via ajax -->
        <input type="hidden" id="id" value="<?php echo $this->object->id; ?>">

        <div id='new-img'>
            <a href='<?= $this->Url->make( trim( Request::getInstance()->uri, '/' ) ); ?>'>Escolher outra imagem...</a>
        </div>
        <div id='sel-img'>
            <label for='img'>Imagem:</label>
            <input type='file' name='img' id='img'>
            <!-- <input type='submit' name='submit' value='Upload'> -->
        </div>
        <div class='image' id='img-div'>
            <!-- <img id='crop'> -->
        </div>

        <div class='ok-cancel'>
            <input type='button' id='btn-crop' value='Recortar'>
            <input type='button' id='btn-cancel' value='Cancelar'>
            <input type='button' id='btn-finalizar' value='Finalizar'>
            <span id='loading'>&nbsp;</span>
        </div>
    </form>
</div>

<script src="<?= $this->Url->make( 'js/jquery-2.1.4.min.js' ); ?>"></script>
<script src="<?= $this->Url->make( 'imgup/js/jquery.imgareaselect.min.js' ); ?>"></script>
<script type="text/javascript" src="<?= $this->Url->make( 'js/up-script.js' ); ?>"></script>
