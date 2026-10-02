<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gebruik: ->middleware('role:office') voor alle kantoorrollen (alles behalve inspecteur),
 * of ->middleware('role:beheerder,bedrijfsleider') voor specifieke rollen.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = in_array('office', $roles, true)
            ? $user->canAccessOffice()
            : in_array($user->role, $roles, true);

        if (! $allowed) {
            return $user->isInspector()
                ? redirect()->route('my-route')
                : redirect()->route('dashboard')->with('error', 'Je hebt geen toegang tot deze pagina.');
        }

        return $next($request);
    }
}
