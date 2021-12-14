<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Controllers\PhotoController;

//user
Route::get("/login",[UserController::class,'authenticate']);
Route::post("/sign_up",[UserController::class,'register']);
Route::get('/EmailConfirmation/{email}', [UserController::class, 'confirmEmail']);
Route::post('/SendResetLinkResponse', [UserController::class, 'sendResetLinkResponse']);
Route::post('/sendResetResponse/{email}/{token}', [UserController::class, 'sendResetResponse']);


Route::group(['middleware' => ['verification']], function() {

        Route::get('/logout', [UserController::class, 'logout']);
        Route::get('/get_user', [UserController::class, 'get_user']);
        Route::post('/profile', [UserController::class, 'profile']);



    });
    