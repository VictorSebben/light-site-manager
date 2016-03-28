<?php

include '../init.php';
// TODO id (update and remove)
// TODO position
// TODO post_id

$mo = new VideoGalleryModel();

$ma = new VideoGalleryMapper();

////$mo->id = 4;
//$mo->post_id = 21;
////$mo->position = 2;
//$mo->video_iframe = 'loremipsumdolorsitamet';
//
//$ma->save( $mo );

// TODO TEST DESTROY
$mo->id = 4;
$ma->destroy( $mo );
