<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (! $user) {
            return redirect()->route('admin.login');
        }

        if (! $user->is_super_admin) {
            abort(403, 'غير مصرح لك بالوصول إلى لوحة التحكم الرئيسية (Super Admin).');
        }

        return $next($request);
    }
}
