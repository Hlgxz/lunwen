<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\StudentController;
use App\Http\Controllers\LogController;
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

Route::group(['middleware' => ['auth:api', 'log.operation']], function () {
    Route::post('logout', [LoginController::class, 'logout']);

    Route::get('user', [UserController::class, 'current']);
    Route::post('student/upload', [StudentController::class, 'upload']);
    Route::get('student/thesis', [StudentController::class, 'thesisList']);
    Route::patch('settings/profile', [ProfileController::class, 'update']);
    Route::patch('settings/password', [PasswordController::class, 'update']);
});

Route::group(['middleware' => 'guest:api'], function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::post('register', [RegisterController::class, 'register']);

    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('password/reset', [ResetPasswordController::class, 'reset']);

    Route::post('email/verify/{user}', [VerificationController::class, 'verify'])->name('verification.verify');
    Route::post('email/resend', [VerificationController::class, 'resend']);

    Route::post('oauth/{driver}', [OAuthController::class, 'redirect']);
    Route::get('oauth/{driver}/callback', [OAuthController::class, 'handleCallback'])->name('oauth.callback');
});

Route::middleware(['auth:api', 'log.operation'])->group(function () {
    Route::prefix('student')->group(function () {
        Route::get('thesis/{id}/download', [StudentController::class, 'download']);
        Route::post('thesis/{id}/ai-check', [StudentController::class, 'aiCheck']);
    });
    Route::get('/review-list', [StudentController::class, 'reviewList']);
    Route::post('/reviews/{reviewId}/appeal', [StudentController::class, 'submitAppeal']);
    Route::get('/reviews/{reviewId}/appeals', [StudentController::class, 'getAppealHistory']);
    Route::get('/appeals', [StudentController::class, 'getAppeals']);
    Route::put('/appeals/{id}', [StudentController::class, 'handleAppeal']);
    Route::get('/operation-logs', [LogController::class, 'index']);

    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{id}', [\App\Http\Controllers\UserController::class, 'update']);
    Route::get('/roles', [\App\Http\Controllers\UserController::class, 'getRoles']);
});
