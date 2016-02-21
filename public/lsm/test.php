<?php
include('../init.php');

H::ppr($_FILES);
H::ppr($_POST);

if ( isset( $_POST[ "submit" ] ) ) {
    $upload = new upload( $_FILES[ 'img' ] );

    $upload->image_resize = true;
    $upload->image_ratio_no_zoom_in = true;
    $upload->image_x = 1000;
    $upload->image_y = 800;

    $upload->process( '../_tmp' );

    if ( !$upload->processed ) {
        try {
            throw new Exception( $upload->error );
        } catch ( Exception $potato ) {
            echo $potato->getMessage();
        }
    }
}

?>

<form action="test.php" method="post" enctype="multipart/form-data">
    <label for='img'>Image:</label>
    <input type='file' name='img' id='img'>
    <input type="hidden" name="w" id="w" value="100"/>
    <input type="hidden" name="h" id="h" value="100"/>
    <input type="submit" name="submit">
</form>
