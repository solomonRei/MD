<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\FeedActionsController;
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
    Route::get('/getById/{id}', [AuthController::class, 'getById']);
    Route::get('/getByType/{type}', [AuthController::class, 'getByType']);
    Route::post('/change-city', [AuthController::class, 'changeCity']);
    Route::post('/update-rating', [AuthController::class, 'updateRating']);
    Route::get('/ai', [\App\Http\Controllers\AI::class, 'index']);

    Route::prefix('feed')->group(function () {
        Route::get('/getAll', [FeedController::class, 'index']);
        Route::get('/get/{id}', [FeedController::class, 'show']);
        Route::get('/getByUser', [FeedController::class, 'getByUser']);
        Route::get('/getAllComment/{feedId}', [FeedActionsController::class, 'getComments']);
        Route::get('/getLikesAmount/{feedId}', [FeedActionsController::class, 'getLikes']);
        Route::get('/getSharesAmount/{feedId}', [FeedActionsController::class, 'getShares']);
        Route::post('/create', [FeedController::class, 'create']);
        Route::post('/update/{id}', [FeedController::class, 'update']);
        Route::post('/delete/{id}', [FeedController::class, 'destroy']);
        Route::post('/like/{feedId}', [FeedActionsController::class, 'like']);
        Route::post('/comment/{feedId}', [FeedActionsController::class, 'comment']);
        Route::post('/share/{feedId}', [FeedActionsController::class, 'share']);
    });

});
