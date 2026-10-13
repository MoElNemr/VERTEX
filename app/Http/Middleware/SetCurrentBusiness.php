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
        $user = $request->user('web') ?: $request->user('team');
        $isOwner = (bool) $request->user('web');

        if ($user) {
            $businessId = session('current_business_id');

            // إذا لم يتم تحديد بيزنس بعد أو تم تمرير رأس x-business-id
            if ($request->hasHeader('X-Business-Id')) {
                $businessId = $request->header('X-Business-Id');
            }

            // التحقق من أن البيزنس يملكه المستخدم أو مسموح له كعضو فريق (أو سوبر أدمن في وضع المعاينة)
            if ($businessId) {
                $isSuperAdmin = $isOwner && (bool) $user->is_super_admin;

                $hasAccess = $isSuperAdmin
                    ? Business::where('id', $businessId)->exists()
                    : Business::where('id', $businessId)
                        ->where(function ($q) use ($user, $isOwner) {
                            if ($isOwner) {
                                $q->where('user_id', $user->id);
                            } else {
                                $q->whereHas('teamMembers', function ($tmQ) use ($user) {
                                    $tmQ->where('team_members.id', $user->id)
                                        ->where('is_active', true);
                                });
                            }
                        })->exists();

                if ($hasAccess) {
                    session(['current_business_id' => (int) $businessId]);
                } else {
                    session()->forget('current_business_id');
                }
            }

            // إذا لا يوجد بيزنس في الجلسة، اختر أول بيزنس متاح تلقائياً
            if (!session('current_business_id')) {
                $firstBusiness = $isOwner
                    ? Business::where('user_id', $user->id)->first()
                    : $user->businesses()->where('is_active', true)->first();

                if ($firstBusiness) {
                    session(['current_business_id' => $firstBusiness->id]);
                }
            }
        }

        return $next($request);
    }
}
