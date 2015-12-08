<?php

/**
 * User: Howay
 * Date: 2015/12/5 0005
 * Time: 12:29
 */
 

namespace controllers;

use Genius;
use Genius\Controller\General as Controller;

class index extends Controller {

    public function __initialize()
    {}

    public function index()
    {
        Genius::trace('abc');
        echo 11111;
    }

}