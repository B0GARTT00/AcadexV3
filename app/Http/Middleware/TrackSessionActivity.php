<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class TrackSessionActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track for authenticated users
        if (Auth::check() && $request->session()->getId()) {
            $this->updateSessionMetadata($request);
        }

        return $response;
    }

    /**
     * Update session metadata with device information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function updateSessionMetadata(Request $request): void
    {
        try {
            $agent = new Agent();
            $agent->setUserAgent($request->userAgent());

            DB::table('sessions')
                ->where('id', $request->session()->getId())
                ->update([
                    'last_activity_at' => now(),
                    'device_type' => $this->getDeviceType($agent),
                    'browser' => $agent->browser() ?: 'Unknown',
                    'platform' => $agent->platform() ?: 'Unknown',
                ]);
        } catch (\Exception $e) {
            // Silently fail to avoid disrupting the request
            \Log::error('Failed to update session metadata: ' . $e->getMessage());
        }
    }

    /**
     * Determine device type from agent.
     *
     * @param  \Jenssegers\Agent\Agent  $agent
     * @return string
     */
    protected function getDeviceType(Agent $agent): string
    {
        if ($agent->isDesktop()) {
            return 'Desktop';
        }

        if ($agent->isTablet()) {
            return 'Tablet';
        }

        if ($agent->isMobile()) {
            return 'Mobile';
        }

        return 'Unknown';
    }
}
