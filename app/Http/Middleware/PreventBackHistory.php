<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistory
{
    /**
     * Prevent browser bfcache/HTTP caching on authenticated or protected pages
     * so pressing Back after logout cannot display stale authenticated views.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->user() !== null
            || $request->is('account*', 'admin*', 'favorites*', 'my-submissions*', 'contributor*', 'recipes*', 'foods*', 'recipe/create', 'stories/create', 'logout')
        ) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }
}
