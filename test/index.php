<?php

/**
 * @link http://genius.haowei.me
 * @copyright Copyright (c) 2015 Genius Software LLC
 * @license http://genius.haowei.me/license
 */

define('APP_ENV', 'alpha');
define('APP_ROOT', __DIR__);
define('GENIUS_DEBUG', false);
define('GENIUS_ROOT', dirname(__DIR__) . '/vendor');

require GENIUS_ROOT . '/initialize.php';
Genius\Application::init();