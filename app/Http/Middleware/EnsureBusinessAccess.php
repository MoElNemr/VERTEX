<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify the {business} route parameter belongs to the current user/team member.
 * Prevents cross-tenant business ID substitution in URLs.
 */
class EnsureBusinessAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = $request->route('business');

        if (! $business instanceof Business) {
            return $next($request);
        }

        $webUser  = $request->user('web');
        $teamUser = $request->user('team');

        if ($webUser) {
            // Owner: must own the business
            if ($business->user_id !== $webUser->id) {
                abort(403, 'Access denied: this business does not belong to your account.');
            }
        } elseif ($teamUser) {
            // Team member: must be assigned to the business
            $isMember = $teamUser->businesses()
                ->where('businesses.id', $business->id)
                ->exists();

            if (! $isMember) {
                abort(403, 'Access denied: you are not a member of this business.');
            }
        } else {
            abort(401);
        }

        return $next($request);
    }
}
