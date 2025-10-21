<?php

namespace App\Listeners;

use App\Services\UserLogRecorder;
use Illuminate\Auth\Events\Failed;
use Jenssegers\Agent\Agent;

class LogUserFailedLogin
{
    public function __construct(private readonly UserLogRecorder $recorder)
    {
    }

    public function handle(Failed $event)
    {
        $user = $event->user;
        $userId = $user?->getAuthIdentifier();

        if (is_null($userId)) {
            return;
        }

        $agent = new Agent();
        $browser = $agent->browser();
        $platform = $agent->platform();
        $device = $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop');

        $this->recorder->record($userId, 'failed_login', [
            'browser' => $browser,
            'device' => $device,
            'platform' => $platform,
        ]);
    }
}


