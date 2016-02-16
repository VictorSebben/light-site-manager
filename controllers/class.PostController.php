<?php

class PostController extends BaseController {
    /**
     * The Model object.
     *
     * @var PostModel
     */
    protected $_model;

    /**
     * The Mapper object, used to deal with database operations.
     *
     * @var PostMapper
     */
    protected $_mapper;

    public function __construct( $model_base_name ) {
        parent::__construct( $model_base_name );

        $mapper_name = $model_base_name . 'Mapper';
        $this->_mapper = new $mapper_name();
    }

    public function index() {
        // Load result of edit_contents permission test
        $this->_view->editContents = $this->_user->hasPrivilege( 'edit_contents' );
        $this->_view->cat = ( Request::getInstance()->category ) ?: '';

        // instantiate Pagination object and
        // pass it to the Mapper
        $pagination = new Pagination();
        $this->_mapper->pagination = $pagination;

        // load post-objects array for use in the view
        $this->_view->pagination = $pagination;
        $this->_view->objectList = $this->_mapper->index( Request::getInstance()->category );

        $this->_view->addExtraLink( $this->_url->make( 'css/colorbox.css' ) );
        $this->_view->addExtraLink( $this->_url->make( 'font-awesome/css/font-awesome.min.css' ) );

        $this->_view->render( 'posts/index', 'pagination' );
    }

    public function create() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        // Add category that came from the URL, if the user in inserting/updating a post
        // by category. If there was a a category in the URL, the field will be readonly in the view
        $this->_view->cat = Request::getInstance()->category;

        // Populate categories for the select field
        $this->_view->objectList = $this->_mapper->getAllCat();

        // Check if there is input data (we are redirecting the user back to the form
        // with an error message after he tried to submit it), in which case we will
        // give back the input data to the form
        $inputData = H::flashInput();
        if ( $inputData ) {
            $post = new PostModel();
            $post->title = $inputData[ 'title' ];
            $post->intro = $inputData[ 'intro' ];
            $post->post_text = $inputData[ 'post_text' ];
            $post->category_id = $inputData[ 'category_id' ];
            $post->status = $inputData[ 'status' ];

            $this->_view->object = $post;
        }

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

            header( 'Location: ' . $this->_url->make( 'posts/create' ) );
        } else {
            $this->_model->title = Request::getInstance()->getInput( 'title' );
            $this->_model->intro = Request::getInstance()->getInput( 'intro' );
            $this->_model->post_text = Request::getInstance()->getInput( 'post-text' );
            $this->_model->category_id = Request::getInstance()->getInput( 'category' );
            $this->_model->status = Request::getInstance()->getInput( 'status' );
            $this->_model->user_id = $_SESSION[ 'user' ];

            $this->_mapper->save( $this->_model );
            H::flash( 'success-msg', 'Post criado com sucesso!' );
            header( 'Location: ' . $this->_url->make( 'posts/' ) );
        }
    }

    public function edit() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $id = Request::getInstance()->pk;

        $this->_view->object = $this->_mapper->find( $id );

        // Populate categories for the select field
        $this->_view->objectList = $this->_mapper->getAllCat();

        if ( ! ( $this->_view->object instanceof PostModel ) ) {
            throw new Exception( 'Erro: Post não encontrado!' );
        }

        // Try to get input data from session (data that the user had typed
        // in the form before). There will be input data if the validation
        // failed, and we want to redirect the user to the form with an
        // error message, putting back the data she had typed
        $inputData = H::flashInput();
        if ( $inputData ) {
            $post = new PostModel();
            $post->title = $inputData[ 'title' ];
            $post->intro = $inputData[ 'intro' ];
            $post->post_text = $inputData[ 'post_text' ];
            $post->category_id = $inputData[ 'category_id' ];
            $post->status = $inputData[ 'status' ];

            $this->_view->object = $post;
        }

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

            header( 'Location: ' . $this->_url->make( "posts/{$id}/edit/" ) );
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
            header( 'Location: ' . $this->_url->make( 'posts/' ) );
        }
    }

    public function show( $id ) {
        $this->_view->user = $this->_mapper->show( $id );
        $this->_view->render( 'posts/show' );
    }

    public function delete() {
        if ( ! $this->_user->hasPrivilege( 'edit_contents' ) ) {
            throw new PermissionDeniedException();
        }

        $id = Request::getInstance()->pk;

        // give the view the PostModel object
        $this->_view->object = $this->_mapper->find( $id );
        if ( ! ( $this->_view->object instanceof PostModel ) ) {
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
            header( 'Location: ' . $this->_url->make( "posts/" ) );
        }

        $id = Request::getInstance()->getInput( 'id' );

        $post = new PostModel();
        $post->id = $id;

        try {
            $this->_mapper->destroy( $post );

            H::flash( 'success-msg', 'Post removido com sucesso!' );
            header( 'Location: ' . $this->_url->make( "posts/" ) );
        } catch ( PDOException $e ) {
            H::flash( 'err-msg', "Não foi possível excluir o Post!" );
            header( 'Location: ' . $this->_url->make( "posts/" ) );
        }
    }

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
            } // Validate permission to edit all users
            else if ( !$this->_user->hasPrivilege( 'edit_other_users' ) ) {
                $errorMsg = 'Permissão negada.';
            } // No problems occurred: we can carry through with the request
            else {
                if ( $this->_mapper->toggleStatus( $id ) ) {
                    // At the end of the process, give back a new token
                    // to the page
                    $isOk = true;
                } else {
                    $errorMsg = 'Não foi possível atualizar o status do post. Contate o suporte.';
                }
            }

            echo json_encode( array( 'isOk' => $isOk, 'token' => H::generateToken(), 'error' => $errorMsg ) );
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

        $category = ( new CategoryMapper() )->find( $this->_view->object->category_id );

        $this->_view->w = H::ifnull( $category->img_w, CategoryModel::IMG_WIDTH );
        $this->_view->h = H::ifnull( $category->img_h, CategoryModel::IMG_HEIGHT );

        $this->_view->addExtraLink( $this->_url->make( 'font-awesome/css/font-awesome.min.css' ) );
        $this->_view->addExtraLink( $this->_url->make( 'imgup/css/imgareaselect-default.css' ) );

        $this->_view->render( 'posts/form-crop' );
    }

    public function saveImg() {
        $imgH = new ImgH();
        $imgH->saveImg();
    }
}
