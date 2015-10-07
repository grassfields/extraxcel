<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


Route::post('file',         'ExtraxcelController@postFile');
Route::delete('file/remove','ExtraxcelController@removeFile');
Route::get( 'download', 'ExtraxcelController@download');
Route::get( 'clear',    'ExtraxcelController@clear');
Route::get( '/',        'ExtraxcelController@main');


