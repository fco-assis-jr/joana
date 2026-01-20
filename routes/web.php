<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvImportController;

Route::get('/', function () {
    return redirect('/csv-import');
});

// CSV Import Routes
Route::prefix('csv-import')->group(function () {
    Route::get('/', [CsvImportController::class, 'index'])->name('csv-import.index');
    Route::post('/upload', [CsvImportController::class, 'upload'])->name('csv-import.upload');
    Route::get('/status/{id}', [CsvImportController::class, 'status'])->name('csv-import.status');
    Route::get('/recent', [CsvImportController::class, 'recent'])->name('csv-import.recent');
});
