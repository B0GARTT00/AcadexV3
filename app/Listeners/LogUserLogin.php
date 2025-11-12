<?php

namespace App\Listeners;

use App\Services\UserLogRecorder;
use Illuminate\Auth\Events\Login;
use Jenssegers\Agent\Agent;

class LogUserLogin
{
    public function __construct(private readonly UserLogRecorder $recorder)
    {
    }

    public function handle(Login $event)
    {
        $userId = $event->user->getAuthIdentifier();

        $agent = new Agent();
        $browser = $agent->browser();
        $platform = $agent->platform();
        $device = $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop');

        $this->recorder->record($userId, 'login', [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'browser' => $browser,
            'platform' => $platform,
            'device' => $device,
        ]);
    }
}
