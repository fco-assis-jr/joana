<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvImportController;

Route::get('/', function () {
    return redirect('/joana');
});

// Joana Import Routes
Route::prefix('joana')->group(function () {
    Route::get('/', [CsvImportController::class, 'index'])->name('joana.index');
    Route::post('/upload', [CsvImportController::class, 'upload'])->name('joana.upload');
    Route::get('/status/{id}', [CsvImportController::class, 'status'])->name('joana.status');
    Route::get('/recent', [CsvImportController::class, 'recent'])->name('joana.recent');
});
