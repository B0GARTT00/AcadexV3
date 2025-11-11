<?php

namespace App\Services;

use App\Models\UserLog;
use Illuminate\Support\Carbon;
use App\Models\User;
use Illuminate\Database\QueryException;

class UserLogRecorder
{
    private const DUPLICATE_WINDOW_SECONDS = 1;

    public function record(int $userId, string $eventType, array $payload): void
    {
        // Defensive: ensure the referenced user still exists before inserting a log.
        if (!User::where('id', $userId)->exists()) {
            // User no longer exists; skip recording to avoid FK violations.
            return;
        }

        $now = Carbon::now();

        $attributes = array_merge($payload, [
            'user_id' => $userId,
            'event_type' => $eventType,
        ]);

        $recent = UserLog::query()
            ->where('user_id', $userId)
            ->where('event_type', $eventType)
            ->orderByDesc('id')
            ->first();

        if ($recent && $this->isDuplicate($recent, $attributes, $now)) {
            $recent->fill($attributes);
            $recent->updated_at = $now;
            $recent->save();
            return;
        }

        try {
            UserLog::create($attributes);
        } catch (QueryException $e) {
            // If a race condition caused the FK to fail (user deleted between the exists check
            // and insert), skip recording rather than bubbling up a 500. This keeps logs
            // best-effort and preserves DB integrity.
            return;
        }
    }

    private function isDuplicate(UserLog $log, array $attributes, Carbon $now): bool
    {
        if (!$log->created_at) {
            return false;
        }

        return $log->created_at->gte($now->copy()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))
            && $log->browser === ($attributes['browser'] ?? null)
            && $log->device === ($attributes['device'] ?? null)
            && $log->platform === ($attributes['platform'] ?? null);
    }
}
