<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBusiness
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $businessId = session('current_business_id');

            // إذا لم يتم تحديد بيزنس بعد أو تم تمرير رأس x-business-id
            if ($request->hasHeader('X-Business-Id')) {
                $businessId = $request->header('X-Business-Id');
            }

            // التحقق من أن البيزنس يملكه المستخدم أو مسموح له كعضو فريق
            if ($businessId) {
                $hasAccess = Business::where('id', $businessId)
                    ->where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->orWhereHas('teamMembers', function ($tmQ) use ($user) {
                              $tmQ->where('email', $user->email);
                          });
                    })->exists();

                if ($hasAccess) {
                    session(['current_business_id' => (int) $businessId]);
                } else {
                    session()->forget('current_business_id');
                }
            }

            // إذا لا يوجد بيزنس في الجلسة، اختر أول بيزنس متاح تلقائياً
            if (!session('current_business_id')) {
                $firstBusiness = Business::where('user_id', $user->id)
                    ->orWhereHas('teamMembers', function ($tmQ) use ($user) {
                        $tmQ->where('email', $user->email);
                    })->first();

                if ($firstBusiness) {
                    session(['current_business_id' => $firstBusiness->id]);
                }
            }
        }

        return $next($request);
    }
}
