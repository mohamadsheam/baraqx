<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpLog extends Model
{
    protected $table = 'otp_logs';

    protected $fillable = [
        'user_id',
        'otp',
        'expires_at',
        'is_used',
        'type',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return ! $this->is_used && $this->expires_at && $this->expires_at->isFuture();
    }

    public function markAsUsed(): void
    {
        $this->is_used = true;
        $this->save();
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;
        $this->save();
    }
}
