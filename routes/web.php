<?php

use Illuminate\Support\Facades\Route;

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

/*
Route::get('/', function () {
    return view('welcome');
});
*/

Route::post(  'file',       'ExtraxcelController@postFile');
Route::delete('file/remove','ExtraxcelController@removeFile');

Route::get( 'schema/export','ExtraxcelController@exportSchema');
Route::post('schema/sort',  'ExtraxcelController@sortSchema');
Route::post('schema/readby','ExtraxcelController@changeReadBy');
Route::post('schema/import','ExtraxcelController@importSchema');


Route::get( 'download', 'ExtraxcelController@download');
Route::get( 'clear',    'ExtraxcelController@clear');
Route::get( '/',        'ExtraxcelController@main');

Route::get( 'dump',     'ExtraxcelController@dump');

