<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\BusinessAiSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user('web') ?: $request->user('team');
        $currentBusinessId = session('current_business_id');
        $businesses = [];
        $currentBusiness = null;

        if ($user) {
            if ($request->user('web')) {
                $businesses = $user->businesses()->get(['id', 'name', 'logo']);
            } elseif ($request->user('team')) {
                $businesses = $user->businesses()->get(['businesses.id', 'businesses.name', 'businesses.logo']);
            }

            if ($currentBusinessId) {
                $currentBusiness = $businesses->firstWhere('id', $currentBusinessId);

                // Support Super Admin impersonation of any tenant business
                if (! $currentBusiness && $request->user('web')?->is_super_admin) {
                    $impersonated = Business::select(['id', 'name', 'logo'])->find($currentBusinessId);
                    if ($impersonated) {
                        $currentBusiness = $impersonated;
                        $businesses->prepend($impersonated);
                    }
                }
            }

            // Auto-fallback: if no business is selected in session, default to the first business
            if (! $currentBusiness && $businesses->isNotEmpty()) {
                $currentBusiness = $businesses->first();
                session(['current_business_id' => $currentBusiness->id]);
            }
        }

        $isOwner = (bool) $request->user('web');
        $isTeam = (bool) $request->user('team');
        $role = $isOwner ? 'owner' : ($user?->role ?? 'agent');

        $hasAi = false;
        if ($currentBusiness) {
            $aiSetting = BusinessAiSetting::withoutGlobalScopes()
                ->where('business_id', $currentBusiness->id)
                ->first();
            $hasAi = $aiSetting?->has_api_key ?? false;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'is_owner' => $isOwner,
                'is_team' => $isTeam,
                'role' => $role,
                'is_super_admin' => (bool) ($request->user('web')?->is_super_admin),
                'can_manage_team' => $isOwner || $role === 'admin',
                'can_manage_channels' => $isOwner || $role === 'admin',
                'can_manage_businesses' => $isOwner,
                'can_view_all_conversations' => $isOwner || in_array($role, ['admin', 'supervisor']),
            ],
            'currentBusiness' => $currentBusiness,
            'businesses' => $businesses,
            'has_ai' => $hasAi,
            'bulk_wallet' => $user instanceof User ? [
                'balance' => (float) ($user->bulkWallet?->balance ?? 0),
                'credits' => (int) ($user->bulkWallet?->credits ?? 0),
                'currency' => $user->bulkWallet?->currency ?? 'EGP',
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'pendingCharge' => fn () => $request->session()->get('pendingCharge'),
            ],
        ];
    }
}
