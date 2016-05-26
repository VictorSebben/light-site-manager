<?php

namespace lsm\conf;

class Base {
    protected $_config;

    public function __construct() {
        $this->_config = include 'inc.appconfig.php';
    }

    public function getConfig() {
        return $this->_config;
    }
}
