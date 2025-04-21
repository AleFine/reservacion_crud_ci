<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ComensalController;
use App\Http\Controllers\Api\MesaController;
use App\Http\Controllers\Api\ReservaController;

Route::apiResource('comensales', ComensalController::class);
Route::apiResource('mesas', MesaController::class);
Route::apiResource('reservas', ReservaController::class);