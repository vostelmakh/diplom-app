<?php

use App\Http\Controllers\DetailsController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MainController::class, 'index'])->name('main.index');
Route::get('order', [OrderController::class, 'index'])->name('order.index');
Route::get('details', [DetailsController::class, 'index'])->name('details.index');
