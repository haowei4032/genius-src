<?php

require __DIR__ . '/vendor/autoload.php';

use Genius\Foundation\Route;

Route::any('/', function() {
    return 11111;
});


