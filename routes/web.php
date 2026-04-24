<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('files.index'));
Route::get('/files', [FileController::class, 'index'])->name('files.index');
Route::post('/files/upload', [FileController::class, 'upload'])->name('files.upload');
Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
