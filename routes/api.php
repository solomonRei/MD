<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/change-city', [AuthController::class, 'changeCity']);

    Route::prefix('feed')->middleware('auth:api')->except(['getAll', 'get/{id}'])->group(function () {
        Route::post('/getAll', [FeedController::class, 'index']);
        Route::post('/get/{id}', [FeedController::class, 'show']);
        Route::post('/getByUser', [FeedController::class, 'getByUser']);
        Route::post('/create', [FeedController::class, 'create']);
        Route::post('/update/{id}', [FeedController::class, 'update']);
        Route::post('/delete/{id}', [FeedController::class, 'destroy']);
    });

});
