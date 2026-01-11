<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\DidController as AdminDidController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\CompanyDidController as AdminCompanyDidController;
use App\Http\Controllers\Company\AuthController as CompanyAuthController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Company\CompanyUserController;
use App\Http\Controllers\Company\CompanyDidController;
use App\Http\Controllers\Company\CompanyInvoiceController;
use App\Http\Controllers\Company\CompanyCallController;
use App\Http\Controllers\Company\CompanyActivityLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Admin Portal Routes
Route::prefix('admin')->group(function () {
    // Auth Routes
    Route::post('login', [AdminAuthController::class, 'login']);

    // Protected Routes
    Route::middleware(['auth:admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);

        // Admin Resources
        Route::apiResource('companies', AdminCompanyController::class);
        Route::apiResource('dids', AdminDidController::class);
        Route::apiResource('invoices', AdminInvoiceController::class)->only(['index', 'show', 'update']);

        // Company DID Assignment
        Route::get('company-dids', [AdminCompanyDidController::class, 'index']);
        Route::post('company-dids', [AdminCompanyDidController::class, 'store']);
        Route::put('company-dids/{id}', [AdminCompanyDidController::class, 'update']);
        Route::delete('company-dids/{id}', [AdminCompanyDidController::class, 'destroy']);

        // Reports
        Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index']);

        // Team (Admins)
        Route::get('admins/roles', [\App\Http\Controllers\Admin\AdminController::class, 'roles']);
        Route::apiResource('admins', \App\Http\Controllers\Admin\AdminController::class);

        // Activity Logs
        Route::get('activity-logs', [\App\Http\Controllers\Admin\AdminActivityLogController::class, 'index']);

        // Calls
        Route::get('calls', [\App\Http\Controllers\Admin\CallController::class, 'index']);
        Route::get('calls/{id}', [\App\Http\Controllers\Admin\CallController::class, 'show']);
    });
});

// Company Portal Routes
Route::prefix('company')->group(function () {
    // Auth Routes
    Route::post('register', [CompanyAuthController::class, 'register']);
    Route::post('verify-email', [CompanyAuthController::class, 'verifyEmail']);
    Route::post('login', [CompanyAuthController::class, 'login']);

    // Protected Routes
    Route::middleware(['auth:company'])->group(function () {
        Route::post('logout', [CompanyAuthController::class, 'logout']);
        Route::get('me', [CompanyAuthController::class, 'me']);

        // Company User Management
        Route::apiResource('users', CompanyUserController::class);

        // Company DIDs
        Route::get('dids', [CompanyDidController::class, 'index']);
        Route::get('dids/{id}', [CompanyDidController::class, 'show']);

        // Company Invoices
        Route::get('invoices', [CompanyInvoiceController::class, 'index']);
        Route::get('invoices/{id}', [CompanyInvoiceController::class, 'show']);
        Route::get('invoices/{id}/download', [CompanyInvoiceController::class, 'download']);

        // Company Calls
        Route::get('calls', [CompanyCallController::class, 'index']);
        Route::get('calls/{id}', [CompanyCallController::class, 'show']);
        Route::post('calls/{id}/feedback', [CompanyCallController::class, 'addFeedback']);
        
        // Company Activity Logs
        Route::get('activity-logs', [CompanyActivityLogController::class, 'index']);
    });
});

// User Routes (if any separate user portal or standard api)
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
