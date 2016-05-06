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
    public function Index()
    {
        var_dump($this);
    }

    public function Output($id = 5)
    {
        var_dump($id);
    }

    public function __before()
    {
        echo 1;
    }

}