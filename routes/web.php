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

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('/facebookLogin', 'FacebookController@connectFB');
Route::get('/callback/{var}','FacebookController@callback');
Auth::routes();

Route::get('/home', 'HomeController@index');
Route::get('/', 'HomeController@index');
Route::get('/importGroup/{ID}','FacebookController@importGroupFeeds');
Route::get('/refreshProducts','FacebookController@refreshProducts');
Route::get('/products','FacebookController@showAllMyProducts');
Route::get('/importMoreGroups','FacebookController@showMyGroups');
Route::get('/webhook','HomeController@test');
//Route::get('auth/{provider}', 'Auth\AuthController@redirectToProvider');
//Route::get('auth/{provider}/callback', 'Auth\AuthController@handleProviderCallback');