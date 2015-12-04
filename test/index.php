<?php

/**
 * User: Howay
 * Date: 2015/12/4 0004
 * Time: 22:14
 */

define('APP_ENV', 0x1);     // 0x1 开发  0x2 测试  0x3 产品
define('APP_ROOT', __DIR__);
define('GENIUS_DEBUG', true);
define('GENIUS_ROOT', dirname(__DIR__) . '/genius-src');

require GENIUS_ROOT . '/initialize.php';
Genius\Application::init();