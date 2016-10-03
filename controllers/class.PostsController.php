<?php

namespace lsm\controllers;

use lsm\models\PostsModel;
use lsm\mappers\PostsMapper;
use lsm\models\ImagesModel;
use lsm\mappers\ImagesMapper;
use lsm\mappers\CategoriesMapper;
use lsm\models\VideosModel;
use lsm\mappers\VideosMapper;
use lsm\libs\H;
use lsm\libs\ImgH;
use lsm\libs\Pagination;
use lsm\libs\Validator;
use lsm\libs\Request;
use Exception;
use PDOException;
use lsm\exceptions\PermissionDeniedException;

class PostsController extends BaseController {
    /**
     * The Model object.
     *
     * @var PostsModel
     */
    protected $_model;

    /**
     * The Mapper object, used to deal with database operations.
     *
     * @var PostsMapper
     */
    protected $_mapper;

    public function __construct() {
        parent::__construct( 'Posts' );

        $this->_mapper = new PostsMapper();
    }

    public function index( $args = null ) {
        // Load result of edit_contents permission test
        $this->_view->editContents = $this->_user->hasPrivilege( 'edit_contents' );

        $args = (array) $args;

        // Instantiate Pagination object and
        // pass it to the Mapper
        $pagination = new Pagination();
        $this->_mapper->pagination = $pagination;

        // load post-objects array for use in the view
        $this->_view->pagination = $pagination;
        $this->_view->objectList = $this->_mapper->index( array_pop( $args ) );

        $this->_view->categories = $this->_mapper->getAllCat();

        $this->_view->addExtraLink( 'css/colorbox.css' );
        $this->_view->addExtraLink( 'css/posts.css' );

        $this->_view->addExtraScript( 'js/list.js' );
        $this->_view->addExtraScript( 'js/post.js' );
        $this->_view->addExtraScript( 'js/jquery.colorbox-min.js' );

        $this->prepareFlashMsg( $this->_view );

        $this->_view->render( 'posts/index', 'pagination' );
    }

    public function create() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $post = new PostsModel();

        // Populate categories for the select field
        $this->_view->objectList = $this->_mapper->getAllCat();

        // Check if there is input data (we are redirecting the user back to the form
        // with an error message after he tried to submit it), in which case we will
        // give back the input data to the form
        $inputData = H::flashInput();
        if ( $inputData ) {
            $post->title = $inputData[ 'title' ];
            $post->intro = $inputData[ 'intro' ];
            $post->post_text = $inputData[ 'post-text' ];
            $post->status = $inputData[ 'status' ];

            if ( isset( $inputData[ 'cat' ] ) ) {
                array_map( function( $catId ) use ( $post ) {
                    $category = ( new CategoriesMapper() )->find( $catId );
                    $post->categories[] = $category;
                }, $inputData[ 'cat' ] );
            }
        }

        $this->_view->object = $post;

        $this->prepareFlashMsg( $this->_view );

        $this->_view->addExtraScript( 'js/lsmhelper.js' );

        $this->_view->render( 'posts/form' );
    }

    public function insert() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $request = Request::getInstance();

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error messages
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (data the user had typed in) so that we can
            // put it back in the form fields
            H::flashInput( $request->getInput() );

            header( 'Location: ' . $this->_url->create() );
        } else {
            $this->_model->title = $request->getInput( 'title' );
            $this->_model->intro = $request->getInput( 'intro' );
            $this->_model->post_text = $_POST[ 'post-text' ];
            $this->_model->status = $request->getInput( 'status' );
            $this->_model->user_id = $_SESSION[ 'user' ];

            // Let us make sure no error will occur if the user changed the hidden id's manually
            // and duplicated one
            $categories = array_unique( $request->getInput( 'cat' ) );
            array_map( function( $catId ) {
                $category = ( new CategoriesMapper() )->find( $catId );
                if ( ! $category ) {
                    // Flash error messages
                    H::flash( 'err-msg', "Categoria inválida: {$catId}" );
                    header( 'Location: ' . $this->_url->create() );
                    exit;
                }
                $this->_model->categories[] = $category;
            }, $categories );

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Post criado com sucesso!' );
            header( 'Location: ' . $this->_url->index() );
        }
    }

    public function edit( $id ) {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $this->_view->object = $this->_mapper->find( $id );
        $this->_mapper->initCategories( $this->_view->object );

        // Populate categories for the select field
        $this->_view->objectList = $this->_mapper->getAllCat();

        if ( ! ( $this->_view->object instanceof PostsModel ) ) {
            throw new Exception( 'Erro: Post não encontrado!' );
        }

        // Try to get input data from session (data that the user had typed
        // in the form before). There will be input data if the validation
        // failed, and we want to redirect the user to the form with an
        // error message, putting back the data she had typed
        $inputData = H::flashInput();
        if ( $inputData ) {
            $post = new PostsModel();
            $post->title = $inputData[ 'title' ];
            $post->intro = $inputData[ 'intro' ];
            $post->post_text = $inputData[ 'post-text' ];
            $post->status = $inputData[ 'status' ];

            if ( isset( $inputData[ 'cat' ] ) ) {
                array_map( function( $catId ) use ( $post ) {
                    $category = ( new CategoriesMapper() )->find( $catId );
                    $post->categories[] = $category;
                }, $inputData[ 'cat' ] );
            }

            $this->_view->object = $post;
        }

        $this->_view->addExtraScript( 'js/lsmhelper.js' );

        $this->prepareFlashMsg( $this->_view );

        $this->_view->render( 'posts/form' );
    }

    public function update() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $request = Request::getInstance();

        // Get id from $_POST
        $id = $request->getInput( 'id' );

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error message
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (the data the user had typed int he form)
            H::flashInput( $request->getInput() );

            header( 'Location: ' . $this->_url->edit( $id ) );
        } else {
            $this->_model->id = $id;
            $this->_model->title = $request->getInput( 'title' );
            $this->_model->intro = $request->getInput( 'intro' );
            $this->_model->post_text = $_POST[ 'post-text' ];
            $this->_model->status = $request->getInput( 'status' );
            $this->_model->user_id = $_SESSION[ 'user' ];

            // Let us make sure no error will occur if the user changed the hidden id's manually
            // and duplicated one
            $categories = array_unique( $request->getInput( 'cat' ) );
            array_map( function( $catId ) {
                $category = ( new CategoriesMapper() )->find( $catId );
                if ( ! $category ) {
                    // Flash error messages
                    H::flash( 'err-msg', "Categoria inválida: {$catId}" );
                    header( 'Location: ' . $this->_url->create() );
                    exit;
                }
                $this->_model->categories[] = $category;
            }, $categories );

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Post atualizado com sucesso!' );
            header( 'Location: ' . $this->_url->index() );
        }
    }

    public function show( $id ) {
        $this->_view->user = $this->_mapper->show( $id );
        $this->_view->render( 'posts/show' );
    }

    public function delete( $id ) {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        // give the view the PostsModel object
        $this->_view->object = $this->_mapper->find( $id );
        if ( ! ( $this->_view->object instanceof PostsModel ) ) {
            throw new Exception( 'Erro: Post não encontrado!' );
        }

        $this->_view->render( 'posts/delete' );
    }

    public function destroy() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        if ( ! H::checkToken( Request::getInstance()->getInput( 'token' ) ) ) {
            H::flash( 'err-msg', "Não foi possível processar a requisição!" );
            header( 'Location: ' . $this->_url->make( "posts/index" ) );
        }

        $id = Request::getInstance()->getInput( 'id' );

        $post = new PostsModel();
        $post->id = $id;

        try {
            $this->_mapper->destroy( $post );

            H::flash( 'success-msg', 'Post removido com sucesso!' );
            header( 'Location: ' . $this->_url->index() );
        } catch ( PDOException $e ) {
            H::flash( 'err-msg', "Não foi possível excluir o Post!" );
            header( 'Location: ' . $this->_url->index() );
        }
    }

    /**
     * @param $id
     */
    public function toggleStatus( $id ) {
        // Initialize error message (to be used if update fails) and the $isOk flag
        $errorMsg = '';
        $isOk = false;

        // Get token that came with the request
        $token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        try {
            // Validate token
            if ( !H::checkToken( $token ) ) {
                // If token fails to validate, let's send back an error message
                $errorMsg = 'Não foi possível processar a requisição.';
            } // Validate permission to edit contents
            else if ( !$this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão negada.';
            } // No problems occurred: we can carry through with the request
            else {
                if ( $this->_mapper->toggleStatus( $id ) ) {
                    $isOk = true;
                } else {
                    $errorMsg = 'Não foi possível atualizar o status do post. Contate o suporte.';
                }
            }

            // At the end of the process, give back a new token
            // to the page, as well as the isOk flag and an eventual error message
            echo json_encode( array( 'isOk' => $isOk, 'token' => H::generateToken(), 'error' => $errorMsg ) );
        } catch ( Exception $e ) {
            // If any exceptions were thrown in the process, send an error message
            if ( DEBUG )
                echo json_encode( array( 'isOk' => false, 'error' => $e->getMessage() ) );
            else
                echo json_encode( array( 'isOk' => false, 'error' => $errorMsg ) );
        }
    }

    public function activate() {
        $this->_toggleStatusArr( 'activate' );
    }

    public function deactivate() {
        $this->_toggleStatusArr( 'deactivate' );
    }

    /**
     * @param $method
     */
    private function _toggleStatusArr( $method ) {
        // Initialize messages and the $isOk flag to be sent back to the page
        $errorMsg = '';
        $successMsg = '';
        $isOk = false;

        // Get token that came with the request
        $token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Get array os Post IDs
        $items = $_POST[ 'items' ];

        try {
            // Validate token
            if ( !H::checkToken( $token ) ) {
                // If token fails to validate, let's send back an error message
                $errorMsg = 'Não foi possível processar a requisição.';
            } // Validate permission to edit contents
            else if ( !$this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão negada.';
            } // No problems occurred: we can carry through with the request
            else {
                if ( $this->_mapper->{$method}( $items ) ) {
                    $isOk = true;
                    if ( $method == 'activate' )
                        $successMsg = 'Posts ativados com sucesso!';
                    else
                        $successMsg = 'Posts desativados com sucesso!';
                } else {
                    $errorMsg = 'Não foi possível atualizar o status dos Posts. Contate o suporte.';
                }
            }

            // At the end of the process, give back a new token
            // to the page, as well as the isOk flag and an eventual message.
            // We'll also send back the ids that were changed, so that we can
            // toggle the status checkboxes in the table.
            echo json_encode(
                array(
                    'isOk' => $isOk,
                    'token' => H::generateToken(),
                    'error' => $errorMsg,
                    'success' => $successMsg,
                    'items' => $items
                )
            );
        } catch ( Exception $e ) {
            // If any exceptions were thrown in the process, send an error message
            if ( DEBUG )
                echo json_encode( array( 'isOk' => false, 'error' => $e->getMessage() ) );
            else
                echo json_encode( array( 'isOk' => false, 'error' => $errorMsg ) );
        }
    }

    public function deleteAjax() {
        // Initialize error message and the $isOk flag to be sent back to the page
        $errorMsg = '';
        $isOk = false;

        // Get token that came with the request
        $token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        // Get array os Post IDs
        $items = $_POST[ 'items' ];

        try {
            // Validate token
            if ( ! H::checkToken( $token ) ) {
                // If token fails to validate, let's send back an error message
                $errorMsg = 'Não foi possível processar a requisição.';
            } // Validate permission to edit contents
            else if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão negada.';
            } // No problems occurred: we can carry through with the request
            else {
                if ( $this->_mapper->deleteAjax( $items ) ) {
                    $isOk = true;

                    // If everything worked out, we are going to redirect the user
                    // back to the first page on the view. Therefore, we have to
                    // add a success message to the session
                    H::flash( 'success-msg', 'Posts removidos com sucesso!' );
                } else {
                    $errorMsg = 'Não foi possível excluir os Posts. Contate o suporte.';
                }
            }

            // At the end of the process, give back a new token
            // to the page, as well as the isOk flag and an eventual message.
            // We'll also send back the ids that were changed, so that we can
            // toggle the status checkboxes in the table.
            echo json_encode(
                array(
                    'isOk' => $isOk,
                    'token' => H::generateToken(),
                    'error' => $errorMsg,
                    'success' => 'Posts excluídos com sucesso',
                    'items' => $items
                )
            );
        } catch ( Exception $e ) {
            // If any exceptions were thrown in the process, send an error message
            if ( DEBUG )
                echo json_encode( array( 'isOk' => false, 'error' => $e->getMessage() ) );
            else
                echo json_encode( array( 'isOk' => false, 'error' => $errorMsg ) );
        }
    }

    public function images() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        // Store URL in session so we can redirect the user
        // after she is done with the upload
        $this->flashRedirectTo( $_SERVER[ 'HTTP_REFERER' ] );

        $this->_view->images = $this->_mapper->find( Request::getInstance()->uriParts['pk'] );

        $this->_view->addExtraLink( 'css/images.css?' . time() );
        $this->_view->addExtraLink( 'js/cropperjs/cropper.min.css' );
        $this->_view->addExtraScript( 'js/jquery-ui.min.js' );
        $this->_view->addExtraScript( 'js/cropperjs/cropper.min.js ');
        $this->_view->addExtraScript( 'js/images.js?' . time() );


        $this->_view->images = (new ImagesMapper())->index(Request::getInstance()->uriParts[ 'pk' ]);

        // It is not `images/index` because will do more than just list images there. We'll add, remove
        // reorder, crop, etc, from that single view.
        $this->_view->render( 'images/images' );
    }


    /**
     * Save image that come by ajax.
     *
     * Because we are using reflection in the router, the name of the formal parameter
     * must be either $pk or $id. Something like $post_id producess errors.
     *
     * Each ajax call that send an image to the server will cause this method
     * to be invoked once, so, even though the user selects multiple images
     * on the client side, for our method, only one image comes at a single time.
     *
     * @param integer $pk
     *
     * @ajax
     * @throws PermissionDeniedException
     */
    public function imagesSave( $pk ) {

        /**
         * TODO: Properly handle exceptions on the client side of ajax.
         */
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }
        $post_id = $pk;

        // IMPORTANT: $pk in this case id post_id. We are saving an image
        // that belongs to post with id post_id/$pk.

        // If error code is not “OK”, keep calm, abort the mission and die a flaming death.
        if ( ! $_FILES || $_FILES[ 'image' ][ 'error' ] !== UPLOAD_ERR_OK ) {
            echo json_encode( [ 'error' => 'Error uploading the file.' ] );
            return;
        }

        $file = $_FILES[ 'image' ];

        // Let's assume there is an extension (for now).
        $tmp = explode('.', $file['name']);
        $extension = array_pop($tmp);

        $image = new ImagesModel;
        $image->post_id = $pk;
        $image->extension = mb_strtolower($extension, 'UTF-8');

        $imagesMapper = new ImagesMapper;

        // $res is an assoc array with id and position keys.
        $res = $imagesMapper->save( $image );

        // Now we have inserted id and position. We'll use post_id + id to name the image on
        // disk. We'll also send id and position back to the ajax client so they update the
        // underlying dom elements with id and position for each image sent through ajax.

        $imgH = new ImgH;
        $imgH->save($file, $post_id, $res['id']);

        echo json_encode($res);

    }

    /**
     * @ajax. Reposition images on DB.
     *
     * @param integer $id - the post id (not the image id)
     */
    public function imageSetPosition( $id ) {

        // Comes from the request url by default (assigning to new identifier to avoid confusion).
        $post_id = $id;

        $image_id = filter_input( INPUT_POST, 'image_id', FILTER_SANITIZE_NUMBER_INT );
        $oldpos = filter_input( INPUT_POST, 'oldpos', FILTER_SANITIZE_NUMBER_INT );
        $newpos = filter_input( INPUT_POST, 'newpos', FILTER_SANITIZE_NUMBER_INT );

        $image = new ImagesModel;
        $image->id = $image_id;
        $image->post_id = $post_id;

        $imagesMapper = new ImagesMapper;

        // Image has the position attribute. Still, in this case, we are dealing
        // with newpos and oldpos. How to properly handle this? I'll pass these two
        // as separate params for now...
        $status = $imagesMapper->setPosition( $image, $oldpos, $newpos );

        echo json_encode( $status );
    }


    /**
     * @ajax destroys an image (DB / HD).
     *
     * @param Integer $id - the id of the post the image belongs to. The other
     * params (like image_id) come from the ajax params.
     *
     * @return Json.
     */
    public function imageDestroy( $id ) {

        $image = new ImagesModel;
        $image->id = H::param( 'image_id', 'POST' );
        $image->post_id = Request::getInstance()->uriParts[ 'pk' ];
        $image->extension = H::param( 'extension', 'POST' );
        $image->position = H::param( 'position', 'POST' );

        $imagesMapper = new ImagesMapper;
        $status = $imagesMapper->destroy( $image );

        if ( ! $status ) {
            echo json_encode( [ 'status' => 'error' ] );
            return;
        }

        $imgH = new ImgH;
        // How can we properly check whether `unlink` worked fine? Let's
        // assume it was able to delete the images for now.
        $imgH->destroy( $image );

        echo json_encode( [ 'status' => 'success' ] );
    }


    /**
     * @ajax
     */
    public function imageCrop() {

        $post_id = Request::getInstance()->uriParts[ 'pk' ];
        $image_id = Request::getInstance()->getInput( 'image_id', true );
        $extension = Request::getInstance()->getInput( 'extension', true );
        $crop_x = Request::getInstance()->getInput( 'crop_x', true );
        $crop_y = Request::getInstance()->getInput( 'crop_y', true );
        $crop_w = Request::getInstance()->getInput( 'crop_w', true );
        $crop_h = Request::getInstance()->getInput( 'crop_h', true );

        $imgH = new ImgH;
        $imgH->crop( $post_id, $image_id, $extension, $crop_x, $crop_y, $crop_w, $crop_h );
    }


    public function videos( $pk ) {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $videosMapper = new VideosMapper();

        $this->_view->objectList = $videosMapper->index( $pk );
        $this->_view->object = $this->_mapper->find( $pk );

        $this->_view->addExtraLink( 'css/video-gallery.css' );
        $this->_view->addExtraScript( 'js/videos.js' );

        $this->_view->render( 'posts/videos' );
    }

    public function insertVideo( $pk ) {
        // Initialize error message (to be used if update fails) and the $isOk flag
        $errorMsg = '';
        $isOk = false;

        try {
            // Validate permission to edit contents
            if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão Negada';
            } else {
                $mapper = new VideosMapper();

                $video = new VideosModel();

                // Extract video ID and provider from the URL
                $video->setUrlProviderFromUrl( Request::getInstance()->getInput( 'url' ) );

                $video->post_id = $pk;
                $video->url = Request::getInstance()->getInput( 'url' );
                $video->title = Request::getInstance()->getInput( 'title' );
                $video->position = Request::getInstance()->getInput( 'position' );

                $mapper->save( $video );
                $isOk = true;
            }

            echo json_encode( array( 'isOk' => $isOk, 'error' => $errorMsg ) );

        } catch ( Exception $e ) {
            if ( DEBUG ) {
                echo json_encode( array( 'isOk' => false, 'error' => $e->getMessage() ) );
            } else {
                echo json_encode( array( 'isOk' => false, 'error' => 'Não foi possível inserir o vídeo. Contate o suporte.' ) );
            }
        }
    }

    public function updateVideo( $pk ) {
        // Initialize error message (to be used if update fails) and the $isOk flag
        $errorMsg = '';
        $isOk = false;

        $request = Request::getInstance();

        try {
            // Validate permission to edit contents
            if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão Negada';
            } else {
                $mapper = new VideosMapper();

                $video = new VideosModel();
                $video->post_id = $pk;

                if ( ! $_POST[ 'id' ] ) {
                    $errorMsg = 'Ocorreu um erro ao processar a requisição (ID do vídeo não foi encontrado). Contate o suporte.';
                    $isOk = false;
                } else {
                    $video->id = $request->getInput( 'id' );

                    if ( isset( $_POST[ 'title' ] ) )
                        $video->title = $request->getInput( 'title' );
                    if ( isset( $_POST[ 'url' ] ) ) {
                        $video->setUrlProviderFromUrl( $request->getInput( 'url' ) );
                        $video->url = $request->getInput( 'url' );
                    }
                    if ( isset( $_POST[ 'position' ] ) )
                        $video->position = $request->getInput( 'position' );

                    $mapper->save( $video );

                    // Since this is an in-place edition view, we need not
                    // give the user a success message.
                    $isOk = true;
                }
            }

            echo json_encode( array( 'isOk' => $isOk, 'error' => $errorMsg ) );

        } catch ( Exception $e ) {
            if ( DEBUG ) {
                echo json_encode( array( 'isOk' => false, 'error' => $e->getMessage() ) );
            } else {
                echo json_encode( array( 'isOk' => false, 'error' => 'Não foi possível atualizar o vídeo. Contate o suporte.' ) );
            }
        }
    }

    public function destroyVideo( $pk ) {
        // Initialize error message and $isOk flag
        $errorMsg = '';
        $isOk = false;

        try {
            // Validate permission to edit contents.
            if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão Negada';
            } else {
                $mapper = new VideosMapper();

                $video = new VideosModel();
                $video->post_id = $pk;
                $video->id = Request::getInstance()->getInput( 'video_id' );
                $video->position = Request::getInstance()->getInput( 'position' );

                if ( ! $video->id || ! $video->position ) {
                    $errorMsg = 'Ocorreu um erro ao processar a requisição (ID ou Posição do vídeo não encontradas).';
                    $isOk = false;
                } else {
                    $mapper->destroy( $video );
                    $isOk = true;
                }
            }

            echo json_encode( array( 'isOk' => $isOk, 'error' => $errorMsg ) );
        } catch ( Exception $e ) {
            if ( DEBUG ) {
                echo json_encode( array( 'isOk' => false, 'error' => $e->getMessage() ) );
            } else {
                echo json_encode( array( 'isOk' => false, 'error' => 'Não foi possível atualizar o vídeo. Contate o suporte.' ) );
            }
        }
    }
}
