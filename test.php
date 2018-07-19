<?php

require __DIR__ . '/vendor/autoload.php';

use Genius\Foundation\Route;


Route::any('/', function() {
    return 'index';
});

Route::any('/posts/<tag:\w+>.html', function($tag) {
    return $tag;
});

Route::prefix('/api')->group(function(Route $route) {
    $route->any('/', '\\V1\\TestController::indexAction');
});

Route::dispatch();



