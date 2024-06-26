<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DateController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

//Authenticatie groep
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::get('users', [UserController::class, 'users'])->name('users');
    Route::post('login', [UserController::class, 'login'])->name('login');
    Route::post('register', [UserController::class, 'register'])->name('register');
    Route::post('logout', [UserController::class, 'logout'])->name('logout');
});

//Overige acties groep
Route::group(['middleware' => 'api'], function ($router) {
    Route::middleware('api')->get('user', [UserController::class, 'user'])->name('user');

    //User data groep
    Route::group(['prefix' => 'user'], function ($router) {
        Route::middleware('api')->post('update', [UserController::class, 'update_user_data'])->name('update');
        Route::middleware('api')->post('update_password', [UserController::class, 'update_user_password'])->name('update_password');
    });
    Route::middleware('api')->post('date', [DateController::class, 'date'])->name('date');

    //Maaltijd gerelateerd groep
    Route::group(['prefix' => 'meals'], function ($router) {
        Route::middleware('api')->post('search', [MealController::class, 'search'])->name('search');
        Route::middleware('api')->post('search_nutriments', [MealController::class, 'search_nutriments'])->name('search_nutriments');
        Route::middleware('api')->post('add', [MealController::class, 'add'])->name('add');
        Route::middleware('api')->delete('remove', [MealController::class, 'remove'])->name('remove');
    });

    //Activiteit gerelateerde groep
    Route::group(['prefix' => 'activities'], function ($router) {
        Route::middleware('api')->post('add', [ActivityController::class, 'add'])->name('add');
        Route::middleware('api')->delete('remove', [ActivityController::class, 'remove'])->name('remove');
    });

});
