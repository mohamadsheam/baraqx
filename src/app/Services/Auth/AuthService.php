<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\OtpLog;
use App\Models\User;
use App\Services\Notifications\NotificationManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{
    private OtpService $otpService;

    private NotificationManager $notificationManager;

    public function __construct(
        OtpService $otpService,
        NotificationManager $notificationManager
    ) {
        $this->otpService = $otpService;
        $this->notificationManager = $notificationManager;
    }

    public function checkRateLimit(string $key, string $type): array
    {
        $maxAttempts = config("auth.rate_limit.{$type}.max_attempts", 5);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return [
                'success' => false,
                'message' => 'Too many attempts. Please try again in '.ceil($seconds / 60).' minutes.',
            ];
        }

        return ['success' => true];
    }

    public function attemptLogin(Request $request): array
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            $this->hitRateLimit('login:'.$request->ip());

            return [
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
                'redirect' => null,
            ];
        }

        if ($user->isLocked()) {
            $remainingMinutes = now()->diffInMinutes($user->locked_until);

            return [
                'success' => false,
                'message' => "Your account is locked. Please try again in {$remainingMinutes} minutes.",
                'redirect' => null,
            ];
        }

        if (! Hash::check($request->password, $user->password)) {
            $this->handleFailedLogin($user, 'login:'.$request->ip());

            return [
                'success' => false,
                'message' => $this->getFailedLoginMessage($user),
                'redirect' => null,
            ];
        }

        RateLimiter::clear('login:'.$request->ip());
        $user->recordSuccessfulLogin();

        return $this->initiateOtpVerification($user, 'login');
    }

    public function initiateOtpVerification(User $user, string $type): array
    {
        $otp = $this->otpService->generate();
        $otpLog = $this->otpService->createForUser($user, $type, $otp);

        $this->notificationManager
            ->getChannel('mail')
            ->sendOtp($user, $otp, $type);

        session([
            'otp_verification_user_id' => $user->id,
            'otp_log_id' => $otpLog->id,
            'otp_type' => $type,
        ]);

        return [
            'success' => true,
            'message' => 'OTP sent successfully!',
            'redirect' => route('otp.verify'),
        ];
    }

    public function validateOtp(string $otp): array
    {
        $otpLog = OtpLog::where('id', session('otp_log_id'))
            ->where('user_id', session('otp_verification_user_id'))
            ->first();

        if (! $otpLog) {
            return [
                'success' => false,
                'message' => 'Invalid OTP request.',
            ];
        }

        if ($otpLog->is_used) {
            $this->clearOtpSession();

            return [
                'success' => false,
                'message' => 'OTP already used. Please login again.',
                'redirect' => route('login'),
            ];
        }

        if (! $this->otpService->isValid($otpLog)) {
            return [
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ];
        }

        if (! $this->otpService->verify($otp, $otpLog)) {
            $this->otpService->incrementAttempts($otpLog);

            if ($this->otpService->isMaxAttemptsReached($otpLog)) {
                $this->otpService->markAsUsed($otpLog);
                $this->clearOtpSession();

                return [
                    'success' => false,
                    'message' => 'Too many invalid OTP attempts. Please login again.',
                    'redirect' => route('login'),
                ];
            }

            $attemptsLeft = $this->otpService->getAttemptsLeft($otpLog);

            return [
                'success' => false,
                'message' => "Invalid OTP. {$attemptsLeft} attempts remaining.",
            ];
        }

        $this->otpService->markAsUsed($otpLog);
        $user = User::find(session('otp_verification_user_id'));
        $otpType = session('otp_type');

        $this->clearOtpSession();

        if ($otpType === 'login') {
            Auth::login($user);

            return [
                'success' => true,
                'message' => 'Login successful!',
                'redirect' => route('dashboard'),
            ];
        }

        if ($otpType === 'password_reset') {
            session([
                'password_reset_user_id' => $user->id,
                'password_reset_verified' => true,
            ]);

            return [
                'success' => true,
                'message' => 'OTP verified!',
                'redirect' => route('password.reset.form'),
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid verification type.',
            'redirect' => route('login'),
        ];
    }

    public function resendOtp(): array
    {
        $userId = session('otp_verification_user_id');

        if (! $userId) {
            return [
                'success' => false,
                'message' => 'Session expired. Please try again.',
            ];
        }

        $user = User::find($userId);

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User not found.',
            ];
        }

        $key = 'otp_resend:'.$userId;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return [
                'success' => false,
                'message' => 'Please wait '.ceil($seconds / 60).' minutes before requesting another OTP.',
            ];
        }

        RateLimiter::hit($key, 300);

        return $this->initiateOtpVerification($user, session('otp_type', 'login'));
    }

    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function sendPasswordResetOtp(User $user): array
    {
        return $this->initiateOtpVerification($user, 'password_reset');
    }

    public function resetPassword(string $password): array
    {
        if (! session()->has('password_reset_user_id')) {
            return [
                'success' => false,
                'message' => 'Session expired.',
                'redirect' => route('password.forgot'),
            ];
        }

        $user = User::find(session('password_reset_user_id'));

        if (! $user) {
            $this->clearPasswordResetSession();

            return [
                'success' => false,
                'message' => 'User not found.',
                'redirect' => route('password.forgot'),
            ];
        }

        $user->update(['password' => $password]);

        $this->clearPasswordResetSession();
        $this->clearOtpSession();

        return [
            'success' => true,
            'message' => 'Password reset successfully! Please login with your new password.',
            'redirect' => route('login'),
        ];
    }

    private function handleFailedLogin(User $user, string $rateLimitKey): void
    {
        RateLimiter::hit($rateLimitKey, config('auth.rate_limit.login.duration'));

        $maxAttempts = config('auth.lockout.max_attempts', 5);
        $user->incrementFailedAttempts($maxAttempts);
    }

    private function getFailedLoginMessage(User $user): string
    {
        $maxAttempts = config('auth.lockout.max_attempts', 5);
        $attemptsLeft = $maxAttempts - $user->failed_login_attempts;

        if ($user->failed_login_attempts >= $maxAttempts - 1) {
            return 'Too many failed attempts. Your account has been locked for '.config('auth.lockout.duration').' minutes.';
        }

        return "Invalid credentials. {$attemptsLeft} attempts remaining.";
    }

    private function hitRateLimit(string $key): void
    {
        RateLimiter::hit($key, config('auth.rate_limit.login.duration', 60));
    }

    private function clearOtpSession(): void
    {
        session()->forget(['otp_verification_user_id', 'otp_log_id', 'otp_type']);
    }

    private function clearPasswordResetSession(): void
    {
        session()->forget(['password_reset_user_id', 'password_reset_verified']);
    }
}
