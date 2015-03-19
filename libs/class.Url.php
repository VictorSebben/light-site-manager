<?php

class Url {

    /**
     * The base url location for the system.
     *
     * @var string
     */
    public $base;

    public function __construct() {
        $this->base = 'http://localhost/projs/ecommaster';
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
