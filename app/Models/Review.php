<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    public const LOW_RATING_REASONS = [
        'Rasa tidak sesuai ekspektasi',
        'Langkah memasak kurang jelas',
        'Bahan sulit ditemukan',
        'Takaran bahan kurang tepat',
        'Waktu memasak tidak sesuai',
        'Hasil masakan berbeda dari deskripsi',
        'Lainnya',
    ];

    protected $fillable = [
        'user_id',
        'food_id',
        'rating',
        'comment',
        'feedback_reasons',
        'feedback_other',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'feedback_reasons' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
