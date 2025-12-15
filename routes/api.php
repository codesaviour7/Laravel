<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DrugSearchController;
use App\Http\Controllers\MedicationController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:public-search')->get('/search', [DrugSearchController::class, 'search']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/medications', [MedicationController::class, 'index']);
    Route::post('/medications', [MedicationController::class, 'store']);
    Route::delete('/medications/{rxcui}', [MedicationController::class, 'destroy']);
});

