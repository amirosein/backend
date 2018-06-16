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
Route::post('/itest', 'TestController@index');
Route::group(['namespace' => 'v1', 'prefix' => 'v1'], function () {

    Route::post('/register', 'AuthController@register');
    Route::post('/login', 'AuthController@login');
    Route::post('/logout', 'AuthController@logout');
    Route::put('/refresh', 'AuthController@refresh');
    Route::get('/verify/{user}/{token}', 'AuthController@verifyEmail')->name('verify-email');

    Route::post('/password/email', 'PasswordController@sendLink')->name('send-password-email');


    Route::post('/decrypt-phy-payload', 'OtherController@decryptPhyPayload');


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
        Route::get('/{project}/activate', 'ProjectController@activate');

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

    Route::group(['prefix' => 'things', 'middleware' => ['auth.jwt']], function () {
        Route::get('/', 'ThingController@all');
        Route::post('/', 'ThingController@create');
        Route::post('/from-excel', 'ThingController@fromExcel');
        Route::get('/{thing}', 'ThingController@get');

        Route::post('data', 'ThingController@mainData');
        Route::post('data/sample', 'ThingController@sampleData');

        Route::patch('/{thing}', 'ThingController@update');
        Route::delete('/{thing}', 'ThingController@delete');
        Route::post('/{thing}/keys', 'ThingController@keys');
        Route::post('/{thing}/send', 'DownLinkController@sendThing');
        Route::get('/{thing}/activate', 'ThingController@activate');

        Route::post('/{thing}/codec', 'CodecController@send');
        Route::get('/{thing}/codec', 'CodecController@getThing');
        Route::post('/{thing}/test', 'CodecController@test');
    });

    Route::group(['prefix' => 'thing-profile', 'middleware' => ['auth.jwt']], function () {
        Route::get('/', 'ThingProfileController@all');
        Route::post('/', 'ThingProfileController@create');
        Route::delete('/{thing_profile}', 'ThingProfileController@delete');
        Route::get('/{thing_profile}', 'ThingProfileController@get');
        Route::get('/{thing_profile}/things-excel', 'ThingProfileController@thingsExcel');

    });

    Route::group(['prefix' => 'gateway', 'middleware' => ['auth.jwt']], function () {
        Route::post('/', 'GatewayController@create');
        Route::get('/', 'GatewayController@list');
        Route::delete('/{gateway}', 'GatewayController@delete');
        Route::get('/{gateway}', 'GatewayController@info');
        Route::put('/{gateway}', 'GatewayController@update');
        Route::get('/{gateway}/frames', 'GatewayController@frames');
    });

    Route::group(['prefix' => 'packages', 'middleware' => ['auth.jwt']], function () {
        Route::post('/', 'PackageController@create');
        Route::get('/all', 'PackageController@all');
        Route::delete('/{package}', 'PackageController@delete');
        Route::patch('/{package}', 'PackageController@update');
        Route::get('/{package}/activate', 'PackageController@activate');

        Route::get('/', 'PackageController@list');
        Route::get('/{package}', 'PackageController@get');
        Route::get('/{package}/invoice', 'PaymentController@createInvoice');
    });
    Route::group(['prefix' => 'discount', 'middleware' => ['auth.jwt']], function () {
        Route::post('/', 'DiscountController@create');
        Route::get('/', 'DiscountController@all');
        Route::delete('/{discount}', 'DiscountController@delete');
    });

    Route::group(['prefix' => 'payment', 'middleware' => ['auth.jwt']], function () {
        Route::get('', 'PaymentController@list');
    });
    Route::group(['prefix' => 'payment'], function () {
        Route::get('/{invoice}/pay', 'PaymentController@pay');
        Route::get('/{invoice}/callback', 'PaymentController@callback')->name('payment.verify');
    });


});


Route::group(['namespace' => 'admin', 'prefix' => 'admin'], function () {


    Route::group(['prefix' => 'users', 'middleware' => ['auth.jwt', 'admin']], function () {
        Route::get('/', 'UserController@list');
        Route::get('/{user}', 'UserController@get');
        Route::get('/{user}/ban', 'UserController@ban');
        Route::post('/{user}/password', 'UserController@setPassword');
        Route::get('/{user}/transactions', 'UserController@transactions');
        Route::get('/{user}/impersonate', 'UserController@impersonate');
    });
    Route::group(['prefix' => 'codec', 'middleware' => ['auth.jwt', 'admin']], function () {
        Route::post('/', 'CodecController@create');
        Route::get('/', 'CodecController@list');
        Route::delete('/{codec}', 'CodecController@delete');
        Route::patch('/{codec}', 'CodecController@update');
        Route::get('/{codec}', 'CodecController@get');
    });

    Route::group(['prefix' => 'payment', 'middleware' => ['auth.jwt', 'admin']], function () {
        Route::get('/', 'PaymentController@list');
    });
});


