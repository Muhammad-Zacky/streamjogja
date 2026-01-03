<?php

use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StreamController::class, 'index']);
Route::post('/check', [StreamController::class, 'check']);
// Route Jantung: Ini yang dimasukkan ke dalam game/radio
Route::get('/stream/{videoId}', [StreamController::class, 'streamYoutube']);
