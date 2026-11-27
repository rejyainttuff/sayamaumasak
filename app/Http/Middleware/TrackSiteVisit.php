<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackSiteVisit
{
    /**
     * Record a single page visit per request for public HTML GET routes.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $request->attributes->set('_site_visit_tracked', true);

            try {
                if (Schema::hasTable('site_visits')) {
                    SiteVisit::create([
                        'visit_date' => now()->toDateString(),
                        'path' => '/'.ltrim($request->path(), '/'),
                        'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                        'ip_hash' => $request->ip() ? hash('sha256', (string) $request->ip()) : null,
                        'user_id' => $request->user()?->id,
                    ]);
                }
            } catch (Throwable) {
                // Never break page responses if visitor logging fails.
            }
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if ($request->attributes->get('_site_visit_tracked', false)) {
            return false;
        }

        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if ($request->is('admin*', 'up', 'Assets/*', 'storage/*', 'favicon.ico')) {
            return false;
        }

        return true;
    }
}
