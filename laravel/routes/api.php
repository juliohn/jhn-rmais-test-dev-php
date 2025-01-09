<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupplierController;


Route::apiResources([
    'suppliers' => SupplierController::class,
]);