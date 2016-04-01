<?php

/**
 * @link http://genius.haowei.me
 * @copyright Copyright (c) 2015 Genius Software LLC
 * @license http://genius.haowei.me/license
 */

namespace Genius\Utils;

abstract class File
{
    private function __construct()
    {}

    public static function exists($file)
    {
        $exists = false;
        if(!file_exists($file)) return false;
        if(PHP_OS == 'WINNT') {
            $handle = opendir(dirname($file));
            while(($next = readdir($handle)) != false) {
                if($file === $next) {
                    $exists = true;
                    break;
                }
            }
            closedir($handle);
            return $exists;
        }
        return !$exists;
    }
}