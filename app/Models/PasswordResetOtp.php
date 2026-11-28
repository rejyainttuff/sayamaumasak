<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'used_at',
        'attempts',
        'resend_count',
        'last_sent_at',
        'verified_token',
        'verified_expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'verified_expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isVerifiedTokenValid(string $token): bool
    {
        return $this->verified_token !== null
            && hash_equals($this->verified_token, $token)
            && $this->verified_expires_at !== null
            && $this->verified_expires_at->isFuture();
    }
}
