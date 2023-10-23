<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('OCR');
});

Route::get('ocr','App\Http\Controllers\OcrController@home')->name('home');

Route::post('ocr/parsedText', 'App\Http\Controllers\OcrController@readImage')->name('readImage');


