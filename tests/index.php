<?php

/**
 * @link http://genius.haowei.me
 * @copyright Copyright (c) 2015 Genius Software LLC
 * @license http://genius.haowei.me/license
 */

define('APP_ENV', 'alpha');
define('APP_PATH', __DIR__);
define('GENIUS_DEBUG', true);
define('GENIUS_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor');

require GENIUS_ROOT . DIRECTORY_SEPARATOR . 'autoload.php';
$arguments = require implode(DIRECTORY_SEPARATOR, [APP_PATH, 'config', 'config.php']);
Genius\Application::runArguments($arguments);