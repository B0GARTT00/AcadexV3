<?php

namespace App\Services;

use App\Models\UserLog;
use Illuminate\Support\Carbon;

class UserLogRecorder
{
    private const DUPLICATE_WINDOW_SECONDS = 1;

    public function record(int $userId, string $eventType, array $payload): void
    {
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

        UserLog::create($attributes);
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
