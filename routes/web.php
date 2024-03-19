<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});
#kankerzooi

require __DIR__.'/auth.php';
