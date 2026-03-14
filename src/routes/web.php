<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::get('/password/forgot', [AuthController::class, 'showForgotPasswordForm'])->name('password.forgot');
    Route::post('/password/forgot', [AuthController::class, 'sendResetOtp'])->middleware('throttle:forgot_password');

    Route::get('/password/reset/form', [AuthController::class, 'showNewPasswordForm'])->name('password.reset.form')->withoutMiddleware('guest');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset')->withoutMiddleware('guest');
});

Route::get('/otp/verify', [AuthController::class, 'showVerifyOtpForm'])->name('otp.verify')->middleware('guest');
Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])->middleware('guest');
Route::post('/otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend')->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/', function () {
    return redirect()->route('login');
});
