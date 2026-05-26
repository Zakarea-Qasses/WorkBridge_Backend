<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\UserNotificationController;
use App\Http\Controllers\Api\UserProjectController;


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

//Notification Routes

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/notifications', [UserNotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [UserNotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [UserNotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [UserNotificationController::class, 'markAllAsRead']);

});

//Location Routes
Route::get('/governorates',[LocationController::class,'governorates']);
Route::get('/governorates/{id}/cities',[LocationController::class,'cities']);

Route::get('/categories', [CategoryController::class, 'index']);

//Services Routes
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
});

//Project Routes
Route::get('/projects', [UserProjectController::class, 'index']);
Route::get('/projects/{id}', [UserProjectController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/projects', [UserProjectController::class, 'store']);
    Route::put('/projects/{id}', [UserProjectController::class, 'update']);
    Route::delete('/projects/{id}', [UserProjectController::class, 'destroy']);
});

//Chat Routes
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/conversations/start', [ConversationController::class, 'start']);

    Route::get('/conversations', [ConversationController::class, 'myConversations']);

    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);

    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'sendMessage']);

});

//Application Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/projects/{projectId}/applications', [ApplicationController::class, 'store']);

    Route::get('/applications/received', [ApplicationController::class, 'received']);
    Route::get('/applications/my', [ApplicationController::class, 'myApplications']);

    Route::post('/applications/{id}/accept', [ApplicationController::class, 'accept']);
    Route::post('/applications/{id}/reject', [ApplicationController::class, 'reject']);
});

//Service Requests Routes

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/services/{service}/requests', [ServiceRequestController::class, 'store']);

    Route::get('/service-requests/received', [ServiceRequestController::class, 'received']);
    Route::get('/service-requests/my', [ServiceRequestController::class, 'myRequests']);

    Route::post('/service-requests/{id}/accept', [ServiceRequestController::class, 'accept']);
    Route::post('/service-requests/{id}/reject', [ServiceRequestController::class, 'reject']);
});

//Report Routes

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/reports', [ReportController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
    Route::put('/reports/{id}/decision', [ReportController::class, 'adminDecision']);
});