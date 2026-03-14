<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\OtpLog;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private OtpService $otpService
    ) {}

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $rateLimitCheck = $this->authService->checkRateLimit(
            'login:'.$request->ip(),
            'login'
        );

        if (! $rateLimitCheck['success']) {
            return back()->withErrors(['email' => $rateLimitCheck['message']])->withInput();
        }

        $result = $this->authService->attemptLogin($request);

        if (! $result['success']) {
            return back()->withErrors(['email' => $result['message']])->withInput();
        }

        return redirect($result['redirect'])->with('success', $result['message']);
    }

    public function showVerifyOtpForm(): View|RedirectResponse
    {
        if (! session()->has('otp_verification_user_id')) {
            return redirect()->route('login');
        }

        $user = User::find(session('otp_verification_user_id'));

        if (! $user) {
            return redirect()->route('login');
        }

        $otpLog = $this->otpService->findActiveOtp(
            session('otp_verification_user_id'),
            session('otp_type')
        );

        if ($otpLog && $otpLog->is_used) {
            session()->forget(['otp_verification_user_id', 'otp_log_id', 'otp_type']);

            return redirect()->route('login')->withErrors(['email' => 'OTP already used. Please login again.']);
        }

        return view('auth.verify-otp', [
            'email' => $user->email,
            'otpType' => session('otp_type'),
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): RedirectResponse
    {
        $result = $this->authService->validateOtp($request->validated('otp'));

        if (! $result['success']) {
            if (isset($result['redirect'])) {
                return redirect($result['redirect'])->withErrors(['email' => $result['message']]);
            }

            return back()->withErrors(['otp' => $result['message']])->withInput();
        }

        if (isset($result['redirect'])) {
            return redirect($result['redirect'])->with('success', $result['message']);
        }

        return redirect()->route('dashboard')->with('success', $result['message']);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $result = $this->authService->resendOtp();

        if (! $result['success']) {
            return response()->json(['error' => $result['message']], 422);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function showForgotPasswordForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetOtp(ForgotPasswordRequest $request): RedirectResponse
    {
        $user = $this->authService->findUserByEmail($request->email);

        if (! $user) {
            return back()->withErrors(['email' => 'No user found with this email address.'])->withInput();
        }

        $rateLimitCheck = $this->authService->checkRateLimit(
            'forgot_password:'.$request->ip(),
            'forgot_password'
        );

        if (! $rateLimitCheck['success']) {
            return back()->withErrors(['email' => $rateLimitCheck['message']])->withInput();
        }

        $result = $this->authService->sendPasswordResetOtp($user);

        if (! $result['success']) {
            return back()->withErrors(['email' => $result['message']])->withInput();
        }

        return redirect()->route('otp.verify')->with('success', 'OTP sent to your email!');
    }

    public function showNewPasswordForm(): View|RedirectResponse
    {
        if (! session()->has('password_reset_user_id') || ! session()->has('password_reset_verified')) {
            return redirect()->route('password.forgot');
        }

        $user = User::find(session('password_reset_user_id'));

        if (! $user) {
            session()->forget(['password_reset_user_id', 'password_reset_verified']);

            return redirect()->route('password.forgot');
        }

        return view('auth.new-password');
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $result = $this->authService->resetPassword($request->validated('password'));

        if (! $result['success']) {
            if (isset($result['redirect'])) {
                return redirect($result['redirect'])->withErrors(['email' => $result['message']]);
            }

            return back()->withErrors(['password' => $result['message']])->withInput();
        }

        return redirect($result['redirect'])->with('success', $result['message']);
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    public function dashboard(): View
    {
        return view('dashboard');
    }
}
