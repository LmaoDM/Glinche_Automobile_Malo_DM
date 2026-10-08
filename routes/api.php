<?php

use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/vehicles', VehicleController::class)->name('vehicles.index');
Route::get('/brands', BrandController::class)->name('brands.index');
