<?php

namespace App\Services;

use App\Models\ContributorLevelLog;
use App\Models\ReadingSession;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContributorLevelService
{
    /**
     * Maximum seconds credited per single content + session token to prevent abuse.
     */
    public const MAX_SECONDS_PER_SESSION = 900;

    /**
     * Maximum seconds accepted per single heartbeat request.
     */
    public const MAX_DELTA_PER_HEARTBEAT = 30;

    /**
     * Bonus levels awarded once when a contributor's recipe is approved.
     */
    public const RECIPE_APPROVAL_BONUS_LEVELS = 5;

    /**
     * Seconds required to advance from reading level L to L + 1.
     * L=1 -> 600s (10 min), L=2 -> 900s (15 min), L=3 -> 1200s (20 min), ...
     */
    public function requiredSecondsForReadingStep(int $readingLevel): int
    {
        return (5 + (5 * max(1, $readingLevel))) * 60;
    }

    /**
     * Compute reading-derived level and progress within current step from total seconds.
     *
     * @return array{reading_level: int, current_step_seconds: int, next_step_required_seconds: int, remaining_seconds: int, progress_percent: int}
     */
    public function calculateReadingProgress(int $totalSeconds): array
    {
        $remaining = max(0, $totalSeconds);
        $readingLevel = 1;

        while (true) {
            $needed = $this->requiredSecondsForReadingStep($readingLevel);
            if ($remaining < $needed) {
                $progressPercent = $needed > 0 ? (int) min(100, round(($remaining / $needed) * 100)) : 0;

                return [
                    'reading_level' => $readingLevel,
                    'current_step_seconds' => $remaining,
                    'next_step_required_seconds' => $needed,
                    'remaining_seconds' => max(0, $needed - $remaining),
                    'progress_percent' => $progressPercent,
                ];
            }

            $remaining -= $needed;
            $readingLevel++;
        }
    }

    /**
     * Record a validated active reading heartbeat and update contributor level if thresholds are crossed.
     *
     * @return array{credited_seconds: int, total_reading_seconds: int, contributor_level: int, progress: array<string, int>}
     */
    public function recordReadingHeartbeat(
        User $user,
        string $contentType,
        int $contentId,
        string $sessionToken,
        int $deltaSeconds
    ): array {
        $requestedDelta = max(1, min(self::MAX_DELTA_PER_HEARTBEAT, $deltaSeconds));
        $credited = 0;

        DB::transaction(function () use ($user, $contentType, $contentId, $sessionToken, $requestedDelta, &$credited): void {
            /** @var User $lockedUser */
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $session = ReadingSession::firstOrNew([
                'user_id' => $lockedUser->id,
                'content_type' => $contentType,
                'content_id' => $contentId,
                'session_token' => $sessionToken,
            ]);

            $now = now();
            $alreadyRead = (int) ($session->seconds_read ?? 0);

            if ($alreadyRead >= self::MAX_SECONDS_PER_SESSION) {
                $credited = 0;

                return;
            }

            $allowedDelta = $requestedDelta;
            if ($session->exists && $session->last_heartbeat_at !== null) {
                $elapsedSinceLast = max(0, (int) $session->last_heartbeat_at->diffInSeconds($now));
                // Allow a small 3-second clock jitter cushion, block rapid fake bursts.
                $allowedDelta = min($requestedDelta, $elapsedSinceLast + 3);
            }

            $remainingCap = max(0, self::MAX_SECONDS_PER_SESSION - $alreadyRead);
            $credited = max(0, min($allowedDelta, $remainingCap));

            if ($credited <= 0) {
                return;
            }

            $session->seconds_read = $alreadyRead + $credited;
            $session->last_heartbeat_at = $now;
            $session->save();

            $oldLevel = max(1, (int) $lockedUser->contributor_level);
            $newReadingSeconds = (int) $lockedUser->reading_seconds + $credited;
            $lockedUser->reading_seconds = $newReadingSeconds;

            $newLevel = $this->computeTotalLevel($lockedUser);
            if ($newLevel > $oldLevel) {
                $lockedUser->contributor_level = $newLevel;
                ContributorLevelLog::create([
                    'user_id' => $lockedUser->id,
                    'source' => 'reading',
                    'recipe_id' => null,
                    'levels_gained' => $newLevel - $oldLevel,
                    'level_before' => $oldLevel,
                    'level_after' => $newLevel,
                    'description' => sprintf(
                        'Naik ke Level %d dari aktivitas membaca (%d menit total).',
                        $newLevel,
                        (int) floor($newReadingSeconds / 60)
                    ),
                ]);
            }

            $lockedUser->save();
        });

        $user->refresh();
        $progress = $this->calculateReadingProgress((int) $user->reading_seconds);

        return [
            'credited_seconds' => $credited,
            'total_reading_seconds' => (int) $user->reading_seconds,
            'contributor_level' => (int) $user->contributor_level,
            'progress' => $progress,
        ];
    }

    /**
     * Grant +5 contributor levels once when a recipe is approved by an Admin.
     */
    public function grantRecipeApprovalBonus(Recipe $recipe): bool
    {
        if (! $recipe->user_id || $recipe->approval_status !== 'approved') {
            return false;
        }

        if (! Schema::hasTable('contributor_level_logs')) {
            return false;
        }

        $granted = false;

        DB::transaction(function () use ($recipe, &$granted): void {
            $alreadyGranted = ContributorLevelLog::where('source', 'recipe_bonus')
                ->where('recipe_id', $recipe->id)
                ->exists();

            if ($alreadyGranted) {
                return;
            }

            /** @var User|null $author */
            $author = User::whereKey($recipe->user_id)->lockForUpdate()->first();
            if (! $author) {
                return;
            }

            $oldLevel = max(1, (int) $author->contributor_level);
            $newLevel = $oldLevel + self::RECIPE_APPROVAL_BONUS_LEVELS;

            $author->contributor_level = $newLevel;
            $author->approved_recipes_count = (int) $author->approved_recipes_count + 1;
            $author->save();

            ContributorLevelLog::create([
                'user_id' => $author->id,
                'source' => 'recipe_bonus',
                'recipe_id' => $recipe->id,
                'levels_gained' => self::RECIPE_APPROVAL_BONUS_LEVELS,
                'level_before' => $oldLevel,
                'level_after' => $newLevel,
                'description' => sprintf(
                    'Bonus +%d level karena resep "%s" disetujui Admin.',
                    self::RECIPE_APPROVAL_BONUS_LEVELS,
                    $recipe->title
                ),
            ]);

            $granted = true;
        });

        return $granted;
    }

    /**
     * Compute total level from reading seconds + all recorded recipe approval bonuses.
     */
    public function computeTotalLevel(User $user): int
    {
        $readingProgress = $this->calculateReadingProgress((int) $user->reading_seconds);
        $recipeBonusLevels = Schema::hasTable('contributor_level_logs')
            ? (int) ContributorLevelLog::where('user_id', $user->id)
                ->where('source', 'recipe_bonus')
                ->sum('levels_gained')
            : 0;

        return max(1, $readingProgress['reading_level'] + $recipeBonusLevels);
    }

    /**
     * Build complete progress summary for the Contributor dashboard UI.
     *
     * @return array<string, mixed>
     */
    public function getProgressSummary(User $user): array
    {
        $reading = $this->calculateReadingProgress((int) $user->reading_seconds);
        $logs = Schema::hasTable('contributor_level_logs')
            ? ContributorLevelLog::where('user_id', $user->id)->latest()->take(15)->get()
            : collect();

        $recipeBonusLevels = $logs->where('source', 'recipe_bonus')->sum('levels_gained');
        $currentLevel = max(1, (int) $user->contributor_level);

        return [
            'current_level' => $currentLevel,
            'reading_level' => $reading['reading_level'],
            'total_reading_seconds' => (int) $user->reading_seconds,
            'current_step_seconds' => $reading['current_step_seconds'],
            'next_step_required_seconds' => $reading['next_step_required_seconds'],
            'remaining_seconds' => $reading['remaining_seconds'],
            'progress_percent' => $reading['progress_percent'],
            'approved_recipes_count' => (int) $user->approved_recipes_count,
            'recipe_bonus_levels' => (int) $recipeBonusLevels,
            'can_submit_stories' => $user->isAdmin() || ($user->isContributor() && $currentLevel >= 3),
            'logs' => $logs,
        ];
    }
}
