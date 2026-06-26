<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedUsername = env('ADMIN_USERNAME');
        $expectedPassword = env('ADMIN_PASSWORD');

        if (blank($expectedUsername) || blank($expectedPassword)) {
            abort_if(!app()->environment(['local', 'testing']), 503, 'Admin credentials are not configured.');

            return $next($request);
        }

        $providedUsername = (string) $request->getUser();
        $providedPassword = (string) $request->getPassword();

        $usernameMatches = hash_equals((string) $expectedUsername, $providedUsername);
        $passwordMatches = hash_equals((string) $expectedPassword, $providedPassword);

        if (!$usernameMatches || !$passwordMatches) {
            return response('Authentication required.', 401, [
                'WWW-Authenticate' => 'Basic realm="Admin Area"',
                'Cache-Control' => 'no-store, private',
            ]);
        }

        return $next($request);
    }
}
