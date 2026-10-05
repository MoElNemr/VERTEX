<?php

namespace App\Http\Middleware;

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
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'is_owner' => (bool) $request->user('web'),
                'is_team' => (bool) $request->user('team'),
            ],
            'currentBusiness' => $currentBusiness,
            'businesses' => $businesses,
        ];
    }
}
