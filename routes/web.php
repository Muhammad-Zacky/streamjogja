<?php

use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Halaman Utama (Daftar Lagu)
Route::get('/', [StreamController::class, 'index']);

// Proses Input Link YouTube
Route::post('/check', [StreamController::class, 'check']);

// Route Jantung: Ini yang dimasukkan ke dalam game/radio
// Menggunakan regex {videoId} agar mendukung format .mp3 di belakangnya
Route::get('/stream/{videoId}', [StreamController::class, 'streamYoutube'])->where('videoId', '.*');

// --- ROUTE KHUSUS VERCEL (Hanya jalankan sekali setelah deploy) ---
Route::get('/install-db', function() {
    try {
        Artisan::call('migrate --force');
        return "Database berhasil di-install! Silakan hapus route ini demi keamanan.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});
