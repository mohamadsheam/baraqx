<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\OtpLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    private int $length;

    private int $expiryMinutes;

    private int $maxAttempts;

    public function __construct()
    {
        $this->length = config('auth.otp.length', 6);
        $this->expiryMinutes = config('auth.otp.expiry_minutes', 10);
        $this->maxAttempts = config('auth.otp.max_attempts', 3);
    }

    public function generate(): string
    {
        return str_pad(
            (string) random_int(0, pow(10, $this->length) - 1),
            $this->length,
            '0',
            STR_PAD_LEFT
        );
    }

    public function createForUser(User $user, string $type, string $otp): OtpLog
    {
        return OtpLog::create([
            'user_id' => $user->id,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes($this->expiryMinutes),
            'type' => $type,
            'attempts' => 0,
        ]);
    }

    public function getOtpValue(OtpLog $otpLog): string
    {
        return $this->generate();
    }

    public function verify(string $otp, OtpLog $otpLog): bool
    {
        return Hash::check($otp, $otpLog->otp);
    }

    public function isValid(OtpLog $otpLog): bool
    {
        return ! $otpLog->is_used
            && $otpLog->expires_at
            && $otpLog->expires_at->isFuture();
    }

    public function isMaxAttemptsReached(OtpLog $otpLog): bool
    {
        return $otpLog->attempts >= $this->maxAttempts;
    }

    public function markAsUsed(OtpLog $otpLog): void
    {
        $otpLog->markAsUsed();
    }

    public function incrementAttempts(OtpLog $otpLog): void
    {
        $otpLog->incrementAttempts();
    }

    public function getAttemptsLeft(OtpLog $otpLog): int
    {
        return $this->maxAttempts - $otpLog->attempts;
    }

    public function getExpirySeconds(): int
    {
        return $this->expiryMinutes * 60;
    }

    public function findActiveOtp(int $userId, string $type): ?OtpLog
    {
        return OtpLog::where('user_id', $userId)
            ->where('type', $type)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function getExpiryMinutes(): int
    {
        return $this->expiryMinutes;
    }
}
