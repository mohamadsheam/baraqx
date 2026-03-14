<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\NotificationInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationManager
{
    private array $channels = [];

    public function register(NotificationInterface $channel): self
    {
        $this->channels[$channel->supports()] = $channel;

        return $this;
    }

    public function sendOtp(string $channel, User $user, string $otp, string $type): bool
    {
        if (! isset($this->channels[$channel])) {
            Log::warning("Notification channel [{$channel}] not found");

            return false;
        }

        return $this->channels[$channel]->sendOtp($user, $otp, $type);
    }

    public function sendOtpWithFallback(User $user, string $otp, string $type): array
    {
        $results = [];

        foreach ($this->channels as $channel => $service) {
            $results[$channel] = $service->sendOtp($user, $otp, $type);
        }

        return $results;
    }

    public function hasChannel(string $channel): bool
    {
        return isset($this->channels[$channel]);
    }

    public function getChannel(string $channel): ?NotificationInterface
    {
        return $this->channels[$channel] ?? null;
    }
}
