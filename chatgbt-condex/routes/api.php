<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profiles', [ProfileController::class, 'index']);
    Route::post('/profiles', [ProfileController::class, 'store']);
});
