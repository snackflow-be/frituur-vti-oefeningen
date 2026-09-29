<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Personeel-middleware voor /keuken en /admin. Elke ingelogde gebruiker is personeel (geen rollen,
 * 01-analist B-12); deze laag bestaat zodat rollen later op één plek kunnen komen.
 */
class EnsureStaff
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
            abort(403, 'Dit mag je niet.');
        }

        return $next($request);
    }
}
