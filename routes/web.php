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


Auth::routes();

//JADI INI GROUPING ROUTE, SEHINGGA SEMUA ROUTE YANG ADA DIDALAMNYA
//SECARA OTOMATIS AKAN DIAWALI DENGAN administrator
//CONTOH: /administrator/category ATAU /administrator/product, DAN SEBAGAINYA
Route::group(['prefix' => 'administrator', 'middleware' => 'auth'], function () {
    Route::get('/home', 'HomeController@index')->name('home'); //JADI ROUTING INI SUDAH ADA DARI ARTIKEL SEBELUMNYA TAPI KITA PINDAHKAN KEDALAM GROUPING

    //INI ADALAH ROUTE BARU
    Route::resource('category', 'CategoryController')->except(['create', 'show']);
});

Route::resource('product', 'ProductController')->except(['show']);
Route::get('/product/bulk', 'ProductController@massUploadForm')->name('product.bulk');
Route::post('/product/bulk', 'ProductController@massUpload')->name('product.saveBulk');

Route::get('/', 'Ecommerce\FrontController@index')->name('front.index');
Route::get('/products', 'Ecommerce\FrontController@product')->name('front.product');
