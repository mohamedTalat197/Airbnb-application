<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// Tags...
Route::get('/tags', App\Http\Controllers\TagController::class);

// Offices...
Route::get('/offices', [\App\Http\Controllers\OfficeController::class, 'index']);
Route::get('/offices/{office}', [\App\Http\Controllers\OfficeController::class, 'show']);
Route::post('/offices', [\App\Http\Controllers\OfficeController::class, 'create'])->middleware(['auth:sanctum' , 'verified']);
Route::put('/offices/{office}', [\App\Http\Controllers\OfficeController::class, 'update'])->middleware(['auth:sanctum' , 'verified']);
Route::delete('/offices/{office}', [\App\Http\Controllers\OfficeController::class, 'delete'])->middleware(['auth:sanctum' , 'verified']);

// Office Images...
Route::post('/offices/{office}/images', [\App\Http\Controllers\OfficeImageController::class, 'store'])->middleware(['auth:sanctum' , 'verified']);
Route::delete('/offices/{office}/images/{image:id}', [\App\Http\Controllers\OfficeImageController::class, 'delete'])->middleware(['auth:sanctum' , 'verified']);

// User Reservation
Route::get('/reservation', [\App\Http\Controllers\UserReservationController::class, 'index'])->middleware(['auth:sanctum' , 'verified']);
Route::post('/reservation', [\App\Http\Controllers\UserReservationController::class, 'create'])->middleware(['auth:sanctum' , 'verified']);
Route::put('/reservation', [\App\Http\Controllers\UserReservationController::class, 'cancel'])->middleware(['auth:sanctum' , 'verified']);

// Host Reservation
Route::get('/host/reservation', [\App\Http\Controllers\HostReservationController::class, 'index'])->middleware(['auth:sanctum' , 'verified']);
