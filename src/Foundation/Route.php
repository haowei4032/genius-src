<?php

namespace Genius\Foundation;

use Genius\Adapter\Request;

class Route
{
    public static function any($name, $callback)
    {
        echo $callback();
    }

    public static function post($name, $callback)
    {}

    public static function get($name, $callback)
    {}

    public static function put($name, $callback)
    {
    }
}