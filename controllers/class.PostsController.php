<?php

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

        $mapper_name = 'PostsMapper';
        $this->_mapper = new $mapper_name();
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
            $this->_model->post_text = $request->getInput( 'post-text' );
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
            $this->_model->post_text = $request->getInput( 'post-text' );
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

        $this->_view->object = $this->_mapper->find( Request::getInstance()->uriParts['pk'] );

        $category = ( new CategoriesMapper() )->find( $this->_view->object->category_id );

        $this->_view->w = H::ifnull( $category->img_w, CategoriesModel::IMG_WIDTH );
        $this->_view->h = H::ifnull( $category->img_h, CategoriesModel::IMG_HEIGHT );

        $this->_view->addExtraLink( 'font-awesome/css/font-awesome.min.css' );
        $this->_view->addExtraLink( 'imgup/css/imgareaselect-default.css' );
        $this->_view->addExtraLink( 'imgup/css/images.css');

        $this->_view->addExtraScript( 'imgup/js/jquery.imgareaselect.min.js' );
        $this->_view->addExtraScript( 'js/images.js?v2' );

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
        $image = new GalleriesModel;
        $image->post_id = $pk;

        $galleriesMapper = new GalleriesMapper;

        // $res is an assoc array with id and position keys.
        $res = $galleriesMapper->save( $image );

        // Now we have inserted id and position. We'll use post_id + id to name the image on
        // disk. We'll also send id and position back to the ajax client so they update the
        // underlying dom elements with id and position for each image sent through ajax.

        $imgH = new ImgH;
        $imgH->save($file, $post_id, $res['id']);

        echo json_encode($res);

    }

    public function createVideoGal() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        // Store URL in session so we can redirect the user
        // after she is done with the upload
        $this->flashRedirectTo( $_SERVER[ 'HTTP_REFERER' ] );

        $pagination = new Pagination();

        $videoGalleryMapper = new VideoGalleryMapper();
        $videoGalleryMapper->pagination = $pagination;

        $this->_view->pagination = $pagination;
        $this->_view->objectList = $videoGalleryMapper->index();
        $this->_view->object = $this->_mapper->find( Request::getInstance()->pk );

        $category = ( new CategoriesMapper() )->find( $this->_view->object->category_id );

        $this->_view->addExtraLink( 'css/video-gallery.css' );
        $this->_view->addExtraScript( 'js/video-gallery.js' );

        $this->_view->render( 'posts/video-gallery', 'pagination' );
    }

    public function insertVideo( $postId ) {
        // Initialize error message (to be used if update fails) and the $isOk flag
        $errorMsg = '';
        $isOk = false;

        try {
            // Validate permission to edit contents
            if ( !$this->_user->hasPrivilege( 'edit_contents' ) ) {
                $errorMsg = 'Permissão Negada';
            } else {
                $mapper = new VideoGalleryMapper();

                $videoGallery = new VideoGalleryModel();
                $videoGallery->post_id = $postId;
                $videoGallery->title = Request::getInstance()->getInput( 'title' );
                $videoGallery->video_iframe = Request::getInstance()->getInput( 'iframe' );
                $videoGallery->position = Request::getInstance()->getInput( 'position' );

                $mapper->save( $videoGallery );
                H::flash( 'success-msg', 'Vídeo inserido com sucesso!' );
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
}
