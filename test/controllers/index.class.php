<?php

/**
 * User: Howay
 * Date: 2015/12/5 0005
 * Time: 12:29
 */
 

namespace Controllers;

use Genius;
use Genius\Controller\General as Controller;

class Index extends Controller {

    public function __initialize()
    {
    }

    public function index()
    {
        Genius::userConfig(APP_ENV)->parameters;
    }

}