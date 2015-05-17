<?php

class Base {
    protected $_config;

    public function __construct() {
        $this->_config = include 'inc.appconfig.php';
    }
}
