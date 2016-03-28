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

        // Add category that came from the URL, if the user is inserting/updating a post
        // by category. If there was a a category in the URL, the field will have a default
        // value in the view.
        if ( isset( Request::getInstance()->uriParts[ 'args' ] ) ) {
            $this->_view->cat = array_pop( Request::getInstance()->uriParts[ 'args' ] );
        } else {
            $this->_view->cat = null;
        }

        // Populate categories for the select field
        $this->_view->objectList = $this->_mapper->getAllCat();

        // Check if there is input data (we are redirecting the user back to the form
        // with an error message after he tried to submit it), in which case we will
        // give back the input data to the form
        $inputData = H::flashInput();
        if ( $inputData ) {
            $post->title = $inputData[ 'title' ];
            $post->intro = $inputData[ 'intro' ];
            $post->post_text = $inputData[ 'post_text' ];
            $post->category_id = $inputData[ 'category_id' ];
            $post->status = $inputData[ 'status' ];
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

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error messages
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (data the user had typed in) so that we can
            // put it back in the form fields
            H::flashInput( Request::getInstance()->getInput() );

            header( 'Location: ' . $this->_url->create() );
        } else {
            $this->_model->title = Request::getInstance()->getInput( 'title' );
            $this->_model->intro = Request::getInstance()->getInput( 'intro' );
            $this->_model->post_text = Request::getInstance()->getInput( 'post-text' );
            $this->_model->category_id = Request::getInstance()->getInput( 'category' );
            $this->_model->status = Request::getInstance()->getInput( 'status' );
            $this->_model->user_id = $_SESSION[ 'user' ];

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Post criado com sucesso!' );
            header( 'Location: ' . $this->_url->make( 'posts/index' ) );
        }
    }

    public function edit( $id ) {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $this->_view->object = $this->_mapper->find( $id );

        $this->_view->cat = $this->_view->object->category_id;

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
            $post->post_text = $inputData[ 'post_text' ];
            $post->category_id = $inputData[ 'category_id' ];
            $post->status = $inputData[ 'status' ];

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

        // Get id from $_POST
        $id = Request::getInstance()->getInput( 'id' );

        $validator = new Validator();
        if ( ! $validator->check( $_POST, $this->_model->rules ) ) {
            // Flash error message
            H::flash( 'err-msg', $validator->getErrorsJson() );

            // Flash input data (the data the user had typed int he form)
            H::flashInput( Request::getInstance()->getInput() );

            header( 'Location: ' . $this->_url->edit( $id ) );
        } else {
            $this->_model->id = $id;
            $this->_model->title = Request::getInstance()->getInput( 'title' );
            $this->_model->intro = Request::getInstance()->getInput( 'intro' );
            $this->_model->post_text = Request::getInstance()->getInput( 'post-text' );
            $this->_model->category_id = Request::getInstance()->getInput( 'category' );
            $this->_model->status = Request::getInstance()->getInput( 'status' );
            $this->_model->user_id = $_SESSION[ 'user' ];

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

    public function uploadOneImg() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        // Store URL in session so we can redirect the user
        // after she is done with the upload
        $this->flashRedirectTo( $_SERVER[ 'HTTP_REFERER' ] );

        $this->_view->object = $this->_mapper->find( Request::getInstance()->pk );

        $category = ( new CategoriesMapper() )->find( $this->_view->object->category_id );

        $this->_view->w = H::ifnull( $category->img_w, CategoriesModel::IMG_WIDTH );
        $this->_view->h = H::ifnull( $category->img_h, CategoriesModel::IMG_HEIGHT );

        $this->_view->addExtraLink( 'font-awesome/css/font-awesome.min.css' );
        $this->_view->addExtraLink( 'imgup/css/imgareaselect-default.css' );

        $this->_view->addExtraScript( 'imgup/js/jquery.imgareaselect.min.js' );
        $this->_view->addExtraScript( 'js/up-script.js' );

        $this->_view->render( 'posts/form-crop' );
    }

    public function saveImg() {
        $imgH = new ImgH();
        $imgH->saveImg();
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
