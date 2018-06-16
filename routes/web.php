<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'logs'], function () {
    Route::get('/containers', 'LogController@containers');
    Route::get('/containers/{id}', 'LogController@containerLog');
});

Route::group(['namespace' => 'v1'], function () {
    Route::get('/password/reset/{token}', 'PasswordController@showResetForm')->name('reset-password');
    Route::post('/password/reset/{token}', 'PasswordController@reset')->name('reset-password');
});


//Auth::routes();

