<?php

use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/', function () {
    return view('welcome');
});

Route::resource('/dashboard', ImageController::class);
Route::post('/compress-image', [ImageController::class, 'compressImage'])->name('compress-image');
Route::get('/show-compressed-image', [ImageController::class, 'showCompressedImage']);
