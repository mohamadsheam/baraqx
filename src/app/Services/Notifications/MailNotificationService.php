<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailNotificationService implements NotificationInterface
{
    public function sendOtp(User $user, string $otp, string $type): bool
    {
        $subject = $this->getSubject($type);
        $expiryMinutes = config('auth.otp.expiry_minutes', 10);

        try {
            Mail::raw(
                "Your {$subject} OTP is: {$otp}. This OTP will expire in {$expiryMinutes} minutes.",
                function ($message) use ($user, $subject) {
                    $message->to($user->email)
                        ->subject("{$subject} OTP");
                }
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function supports(): string
    {
        return 'mail';
    }

    private function getSubject(string $type): string
    {
        return match ($type) {
            'login' => 'Login',
            'password_reset' => 'Password Reset',
            default => 'Verification',
        };
    }
}
