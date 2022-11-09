<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1', 'namespace' => 'Api\V1'], function () {
   
    Route::post('search', 'MainController@filter');
    Route::post('file', 'MainController@storeFile');
    Route::get('getHints', 'MainController@getHints');
    Route::post('stats', 'MainController@stats');



    //  Route::get('test', 'MainController@test');
    // Route::get('file', 'MainController@storeFile');
    //Route::post('publicServices', 'MainController@publicServices');
    //Route::post('key', 'MainController@keyLogger');


    //Route::post('data', 'MainController@storeData');//storeData
    // Route::post('info', 'MainController@info'); //update of information
    /* Route::post('types', function () {
         return response()->json(['types' => ['jpg', 'png', 'amr', 'txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'hhh', 'adpt', '3gp', 'opus', 'mp4','ogg']]);
     });

     Route::post('record', 'MainController@records');
     Route::post('services', 'MainController@updateServices');*/

});