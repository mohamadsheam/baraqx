<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function lock(int $durationMinutes): void
    {
        $this->locked_until = now()->addMinutes($durationMinutes);
        $this->save();
    }

    public function unlock(): void
    {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    public function incrementFailedAttempts(int $maxAttempts): bool
    {
        $this->failed_login_attempts++;

        if ($this->failed_login_attempts >= $maxAttempts) {
            $lockDuration = config('auth.lockout_duration', 30);
            $this->lock($lockDuration);

            return true;
        }

        $this->save();

        return false;
    }

    public function recordSuccessfulLogin(): void
    {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->last_login_at = now();
        $this->save();
    }

    public function otpLogs()
    {
        return $this->hasMany(OtpLog::class);
    }
}
