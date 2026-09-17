<?php

use App\Http\Controllers\Api\GeocodeController;
use Illuminate\Support\Facades\Route;

Route::get('/api/geocode-suggest', [GeocodeController::class, 'suggest'])->name('api.geocode-suggest');
Route::get('/api/geocode-reverse', [GeocodeController::class, 'reverse'])->name('api.geocode-reverse');
