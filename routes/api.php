<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['namespace' => 'v1', 'prefix' => 'v1'], function () use ($router) {
    Route::post('/register', 'AuthController@register');
    Route::post('/login', 'AuthController@login');
    Route::post('/logout', 'AuthController@logout');
    Route::put('/refresh', 'AuthController@refresh');

    Route::group(['prefix' => 'user', 'middleware' => ['auth.jwt']], function () {
        Route::patch('/update', 'UserController@update');
        Route::get('/', 'UserController@profile');
        Route::post('/upload', 'UserController@upload');
    });
});


