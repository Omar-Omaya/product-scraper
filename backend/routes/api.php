<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ScrapeController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::post('/products/scrape', [ScrapeController::class, 'store']);
