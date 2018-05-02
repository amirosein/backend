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

Route::get('/delete-all-things', 'TestController@delete')->middleware('auth.jwt');
Route::get('/itest', 'TestController@index')->middleware('auth.jwt');
Route::group(['namespace' => 'v1', 'prefix' => 'v1'], function () use ($router) {

    Route::post('/register', 'AuthController@register');
    Route::post('/login', 'AuthController@login');
    Route::post('/logout', 'AuthController@logout');
    Route::put('/refresh', 'AuthController@refresh');
    Route::get('/verify/{user}/{token}', 'AuthController@verifyEmail')->name('verify-email');


    Route::group(['prefix' => 'authorization', 'middleware' => ['auth.jwt']], function () {
        Route::get('/permissions', 'PermissionController@permissionsList');
        Route::get('/roles', 'PermissionController@rolesList');
    });

    Route::group(['prefix' => 'user', 'middleware' => ['auth.jwt']], function () {
        Route::patch('/update', 'UserController@update');
        Route::post('/password', 'UserController@changePassword');
        Route::get('/', 'UserController@profile');
        Route::post('/upload', 'UserController@upload');
        Route::post('/widget/charts', 'UserController@setWidgetChart');
        Route::delete('/widget/charts', 'UserController@deleteWidgetChart');
        Route::get('/dashboard', 'UserController@dashboard');
    });

    Route::group(['prefix' => 'project', 'middleware' => ['auth.jwt']], function () {
        Route::get('/', 'ProjectController@all');
        Route::post('/', 'ProjectController@create');
        Route::patch('/{project}', 'ProjectController@update');
        Route::delete('/{project}', 'ProjectController@stop');
        Route::get('/{project}', 'ProjectController@get');
        Route::get('/{project}/things', 'ProjectController@things');
        Route::get('/{project}/things/export', 'ProjectController@exportThings');
        Route::post('/{project}/aliases', 'ProjectController@aliases');

        Route::post('/{project}/lint', 'ProjectController@lint');
        Route::get('/{project}/log', 'ProjectController@log');


        Route::group(['prefix' => '/{project}/scenario'], function () {
            Route::post('/', 'ScenarioController@create');
            Route::get('/', 'ScenarioController@list');
            Route::get('/{scenario}', 'ScenarioController@get');
            Route::patch('/{scenario}', 'ScenarioController@update');
            Route::delete('/{scenario}', 'ScenarioController@delete');
            Route::get('/{scenario}/activate', 'ScenarioController@activate');
        });

        Route::group(['prefix' => '/{project}/codec'], function () {
            Route::post('/', 'CodecController@create');
            Route::get('/', 'CodecController@list');
            Route::delete('/{codec}', 'CodecController@delete');
            Route::patch('/{codec}', 'CodecController@update');
            Route::get('/{codec}', 'CodecController@get');
        });


        Route::group(['prefix' => '/{project}/things'], function () {

        });
    });

    Route::group(['prefix' => 'things', 'middleware' => ['auth.jwt']], function (){
        Route::get('/', 'ThingController@all');
        Route::post('/', 'ThingController@create');
        Route::post('/from-excel', 'ThingController@fromExcel');
        Route::get('/{thing}', 'ThingController@get');

        Route::get('/{thing}/data', 'ThingController@data');
        Route::post('data', 'ThingController@multiThingData');

        Route::patch('/{thing}', 'ThingController@update');
        Route::delete('/{thing}', 'ThingController@delete');
        Route::post('/{thing}/activate', 'ThingController@activate');
        Route::post('/{thing}/send', 'DownLinkController@sendThing');

        Route::post('/{thing}/codec', 'CodecController@send');
        Route::get('/{thing}/codec', 'CodecController@getThing');
    });

    Route::group(['prefix' => 'thing-profile', 'middleware' => ['auth.jwt']], function () {
        Route::get('/', 'ThingProfileController@all');
        Route::post('/', 'ThingProfileController@create');
        Route::delete('/{thing_profile}', 'ThingProfileController@delete');
        Route::get('/{thing_profile}', 'ThingProfileController@get');

    });

    Route::group(['prefix' => 'gateway', 'middleware' => ['auth.jwt']], function () {
        Route::post('/', 'GatewayController@create');
        Route::get('/', 'GatewayController@list');
        Route::delete('/{gateway}', 'GatewayController@delete');
        Route::get('/{gateway}', 'GatewayController@info');
    });

    Route::group(['prefix' => 'packages', 'middleware' => ['auth.jwt']], function () {
        Route::get('/', 'PackageController@list');
        Route::get('/{package}', 'PackageController@get');
    });


});


Route::group(['namespace' => 'admin', 'prefix' => 'admin'], function () use ($router) {
    Route::group(['prefix' => 'users', 'middleware' => ['auth.jwt', 'admin']], function () {
        Route::get('/', 'UserController@list');
    });
});


