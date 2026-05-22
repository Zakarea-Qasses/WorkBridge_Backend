<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/email/verify', [AuthController::class, 'verify']);

Route::post('/email/resend', [AuthController::class, 'resend']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('verified')->group(function () {
        Route::get('/me', function (Request $request) {
            return response()->json([
                'user'=> $request->user()
            ]);
        });
    });
});

//Reset Password Routes
Route::post('/forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword']);

Route::get('/reset-password/{token}',function($token){
    return response()->json([
        'token'=>$token
    ]);
})->name('password.reset');

//dashboard
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/dashboard/personal', [DashboardController::class, 'personal'])
        ->middleware('role:personal');

    Route::get('/dashboard/company', [DashboardController::class, 'company'])
        ->middleware('role:company');

    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])
        ->middleware('role:admin');

});

//Profile Routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'show'])
        ->middleware('role:personal');
    
    
    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware('role:personal');

    Route::get('/company', [CompanyController::class, 'show'])
        ->middleware('role:company');

    Route::put('/company', [CompanyController::class, 'update'])
        ->middleware('role:company');
 

});

//Posts & Applications Routes
Route::middleware([
    'auth:sanctum',
    'role:personal'
])->group(function () {

    Route::get('/posts', [PostController::class, 'index']);

    Route::get('/posts/{post}', [PostController::class, 'show']);

    Route::post('/posts', [PostController::class, 'store']);

    Route::put('/posts/{post}', [PostController::class, 'update']);

    Route::delete('/posts/{post}', [PostController::class, 'destroy']);

 
    Route::get('/services', [PostController::class, 'services']);

    Route::get('/projects', [PostController::class, 'projects']);

    Route::patch('/posts/{post}/publish', [
        PostController::class,
        'publish'
    ]);

    Route::patch('/posts/{post}/pause', [
        PostController::class,
        'pause'
    ]);

    Route::patch('/posts/{post}/archive', [
        PostController::class,
        'archive'
    ]);

    Route::patch('/posts/{post}/close', [
        PostController::class,
        'close'
    ]);
});
