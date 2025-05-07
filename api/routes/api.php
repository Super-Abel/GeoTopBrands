<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BrandController;

Route::apiResource('brands', BrandController::class);
