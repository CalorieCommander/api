<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\UserController;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [UserController::class, 'login'])->name('login');
    Route::post('register', [UserController::class, 'register'])->name('register');
    Route::post('logout', [UserController::class, 'logout'])->name('logout');
});

Route::group([

    'middleware' => 'api'
], function ($router) {
    Route::get('user', [UserController::class, 'user'])->name('user');
    Route::group([
        'prefix' => 'user'
    ], function ($router) {
        Route::get('update', [UserController::class, 'user'])->name('user');
    });
});

