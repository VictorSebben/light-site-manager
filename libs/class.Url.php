<?php

class Url {

    /**
     * The base url location for the system.
     *
     * @var string
     */
    public $base;

    public function __construct() {
        $arrAppConf = include '../conf/inc.appconfig.php';
        $this->base = $arrAppConf[ 'base_url' ];
    }

    /**
     * Builds the url using the base url.
     *
     * @param string $path The path the link leads to.
     * @return string The path.
     */
    public function make( $path = '' ) {
        return "{$this->base}/{$path}";
    }
}
