<?php

namespace lsm\models;

class HomeModel extends BaseModel {
    public function notFound() {
        echo 'I could not find the page you are requesting :(. ' .
            'Maybe we will have something for you later!';
    }
}
