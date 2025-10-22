<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatabaseManagementController;

Route::get('/', function () {
    return view('welcome');
});

// Database Management Page (Temporary - for development only)
Route::get('/temp-db-management', [DatabaseManagementController::class, 'index']);

// Orders management page
Route::get('/orders-management', function () {
    return view('orders-management');
});
