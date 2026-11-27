<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'username',
    'email',
    'role',
    'contributor_level',
    'reading_seconds',
    'approved_recipes_count',
    'bio',
    'avatar',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isHelper(): bool
    {
        return in_array($this->role, ['helper', 'contributor'], true);
    }

    public function isContributor(): bool
    {
        return in_array($this->role, ['contributor', 'helper'], true);
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function favoriteFoods(): BelongsToMany
    {
        return $this->belongsToMany(Food::class, 'food_user')->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function foods(): HasMany
    {
        return $this->hasMany(Food::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function culinaryStories(): HasMany
    {
        return $this->hasMany(CulinaryStory::class);
    }

    public function contributorRequests(): HasMany
    {
        return $this->hasMany(ContributorRequest::class);
    }

    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    public function contributorLevelLogs(): HasMany
    {
        return $this->hasMany(ContributorLevelLog::class);
    }

    public function canSubmitCulinaryStory(): bool
    {
        return $this->isAdmin() || ($this->isContributor() && (int) $this->contributor_level >= 3);
    }

    public function getDisplayUsernameAttribute(): string
    {
        if (! empty($this->username)) {
            return '@'.ltrim((string) $this->username, '@');
        }

        return '@'.strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', (string) $this->name));
    }
}
