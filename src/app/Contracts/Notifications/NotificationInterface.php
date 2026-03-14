<?php

namespace App\Contracts\Notifications;

use App\Models\User;

interface NotificationInterface
{
    public function sendOtp(User $user, string $otp, string $type): bool;

    public function supports(): string;
}
