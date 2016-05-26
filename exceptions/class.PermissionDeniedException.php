<?php

namespace lsm\exceptions;

class PermissionDeniedException extends \Exception {
    protected $message = "Permission denied!";
}
