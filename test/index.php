<?php

define('APP_ENV', 'alpha');
define('APP_ROOT', __DIR__);
define('GENIUS_DEBUG', true);
define('GENIUS_ROOT', dirname(__DIR__) . '/genius-src');

require GENIUS_ROOT . '/initialize.php';
Genius\Application::init();