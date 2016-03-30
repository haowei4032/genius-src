<?php

/**
 * @link http://genius.haowei.me
 * @copyright Copyright (c) 2015 Genius Software LLC
 * @license http://genius.haowei.me/license
 */

namespace Controllers;

use Genius;
use Genius\Controller\General as Controller;

class Index extends Controller
{
    public function __before()
    {
        var_dump($this);
    }

    public function Index()
    {
        echo 11111;
    }

}