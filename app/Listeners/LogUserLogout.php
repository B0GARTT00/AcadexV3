<?php

namespace App\Listeners;

use App\Services\UserLogRecorder;
use Illuminate\Auth\Events\Logout;
use Jenssegers\Agent\Agent;

class LogUserLogout
{
    public function __construct(private readonly UserLogRecorder $recorder)
    {
    }

    public function handle(Logout $event)
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        $userId = $user->getAuthIdentifier();

        $agent = new Agent();
        $browser = $agent->browser();
        $platform = $agent->platform();
        $device = $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop');

        $this->recorder->record($userId, 'logout', [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'browser' => $browser,
            'platform' => $platform,
            'device' => $device,
        ]);
    }
}
