<?php

/**
 * User: howay
 * Date: 2015/12/7 0007
 * Time: 10:32
 */

namespace controllers;
use Genius\Controller\General as Controller;

class index extends Controller
{
    public function index()
    {
        var_dump($this->controllerID);
    }
}