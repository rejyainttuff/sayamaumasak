<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingSession extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'user_id',
        'content_type',
        'content_id',
        'session_token',
        'seconds_read',
        'last_heartbeat_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
