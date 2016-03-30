<?php

/**
 * @link http://genius.haowei.me
 * @copyright Copyright (c) 2015 Genius Software LLC
 * @license http://genius.haowei.me/license
 */

namespace Genius\Utils;

abstract class Directory
{
    private function __construct()
    {}

    /**
     * @param string $path
     * @return string
     */
    public static function create($path)
    {
        if(!$path) return null;
        if(!is_dir($path)) {
            if(!mkdir($path, 755)) {
                static::create(dirname($path));
            }
        }
        return $path;
    }

    public static function delete()
    {}
}