<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BulkMessageTransaction;
use App\Models\BulkMessageWallet;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\FacebookAccount;
use App\Models\InstagramAccount;
use App\Models\Message;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\TelegramAccount;
use App\Models\User;
use App\Models\WhatsappAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
    /**
     * Display Super Admin Command Center.
     */
    public function index(Request $request): Response
    {
        // 1. Platform KPIs
        $totalUsers = User::count();
        $totalBusinesses = Business::count();
        $totalMessages = Message::count();
        $totalContacts = Contact::count();
        $totalCampaigns = Campaign::count();
        $totalPlans = Plan::count();

        // 2. Channel Breakdown
        $whatsappConnected = WhatsappAccount::withoutGlobalScopes()->where('status', 'connected')->count();
        $telegramConnected = TelegramAccount::withoutGlobalScopes()->where('status', 'connected')->count();
        $facebookConnected = FacebookAccount::withoutGlobalScopes()->where('status', 'connected')->count();
        $instagramConnected = InstagramAccount::withoutGlobalScopes()->where('status', 'connected')->count();

        // 3. System & Microservices Health Check
        $nodeServiceOnline = false;
        $liveNodeSessions = [];
        try {
            $serviceUrl = config('services.whatsapp.service_url', 'http://127.0.0.1:3000');
            $response = Http::timeout(2)
                ->withHeaders(\App\Services\Channels\WhatsAppTokenService::getRequestHeaders())
                ->get("{$serviceUrl}/api/sessions");
            if ($response->successful()) {
                $nodeServiceOnline = true;
                $liveNodeSessions = $response->json('sessions', []);
            }
        } catch (\Throwable $e) {
            $nodeServiceOnline = false;
        }

        // Check Reverb Socket status
        $reverbOnline = false;
        try {
            $socket = @fsockopen('127.0.0.1', 8080, $errno, $errstr, 1);
            if ($socket) {
                $reverbOnline = true;
                fclose($socket);
            }
        } catch (\Throwable) {
            $reverbOnline = false;
        }

        // Queue Health
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        // 4. Businesses List
        $businesses = Business::with(['user:id,name,email', 'whatsappAccount', 'telegramAccount', 'facebookAccount', 'instagramAccount'])
            ->withCount(['contacts', 'conversations', 'campaigns'])
            ->latest()
            ->paginate(15, ['*'], 'businesses_page');

        // 5. Users List
        $users = User::with(['activeSubscription.plan'])
            ->withCount('businesses')
            ->latest()
            ->paginate(15, ['*'], 'users_page');

        // 6. Plans List
        $plans = Plan::withCount('subscriptions')->get();

        // 7. Recent Campaigns across all businesses
        $recentCampaigns = Campaign::with('business:id,name')
            ->latest()
            ->limit(10)
            ->get();

        // 8. All Users simple list for dropdown selects
        $allUsersSimple = User::select(['id', 'name', 'email'])->orderBy('name')->get();

        // 9. System Specs
        $systemSpecs = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_os' => PHP_OS_FAMILY,
            'db_connection' => config('database.default'),
            'queue_driver' => config('queue.default'),
            'broadcast_driver' => config('broadcasting.default'),
        ];

        // 10. Fawry Payment Gateway Settings & Transactions
        $fawrySettings = [
            'merchant_code' => SystemSetting::get('fawry_merchant_code', env('FAWRY_MERCHANT_CODE', '1014316')),
            'security_key' => SystemSetting::get('fawry_security_key', env('FAWRY_SECURITY_KEY', 'default_test_key')),
            'merchant_wallet' => SystemSetting::get('fawry_merchant_wallet', env('FAWRY_MERCHANT_WALLET', '')),
            'mode' => SystemSetting::get('fawry_mode', env('FAWRY_MODE', 'sandbox')),
            'webhook_url' => url('/webhooks/fawry'),
        ];

        $paymentTransactions = PaymentTransaction::with(['user:id,name,email', 'plan:id,name'])
            ->latest()
            ->limit(15)
            ->get();

        // 11. Bulk Message Wallets & Transactions
        $userWallets = User::with('bulkWallet')
            ->latest()
            ->paginate(15, ['*'], 'wallets_page');

        $recentBulkTransactions = BulkMessageTransaction::with('user:id,name,email')
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('SuperAdmin/Index', [
            'metrics' => [
                'total_users' => $totalUsers,
                'total_businesses' => $totalBusinesses,
                'total_messages' => $totalMessages,
                'total_contacts' => $totalContacts,
                'total_campaigns' => $totalCampaigns,
                'total_plans' => $totalPlans,
                'channels' => [
                    'whatsapp' => $whatsappConnected,
                    'telegram' => $telegramConnected,
                    'facebook' => $facebookConnected,
                    'instagram' => $instagramConnected,
                    'total' => $whatsappConnected + $telegramConnected + $facebookConnected + $instagramConnected,
                ],
            ],
            'health' => [
                'node_baileys' => $nodeServiceOnline,
                'reverb_websockets' => $reverbOnline,
                'queue_worker' => true,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'live_sessions' => $liveNodeSessions,
            ],
            'businesses' => $businesses,
            'users' => $users,
            'plans' => $plans,
            'allUsersSimple' => $allUsersSimple,
            'recentCampaigns' => $recentCampaigns,
            'systemSpecs' => $systemSpecs,
            'fawrySettings' => $fawrySettings,
            'paymentTransactions' => $paymentTransactions,
            'userWallets' => $userWallets,
            'recentBulkTransactions' => $recentBulkTransactions,
        ]);
    }

    /**
     * Adjust User Bulk Message Wallet balance from Super Admin.
     */
    public function adjustUserWallet(Request $request, User $user, \App\Services\WalletService $walletService): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'credits' => ['required', 'integer', 'min:0'],
            'type' => ['required', 'in:add,deduct'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $admin = $request->user('web');
        $multiplier = $validated['type'] === 'deduct' ? -1 : 1;

        $walletService->adjustBalance(
            $user,
            (float) ($validated['amount'] * $multiplier),
            (int) ($validated['credits'] * $multiplier),
            $validated['reason'],
            $admin ? $admin->name : 'Super Admin'
        );

        return back()->with('success', "تم تعديل رصيد محفظة المستخدم {$user->name} بنجاح.");
    }

    /**
     * Update Fawry Payment Gateway settings.
     */
    public function updateFawrySettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'merchant_code' => ['required', 'string', 'max:255'],
            'security_key' => ['required', 'string', 'max:255'],
            'merchant_wallet' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', 'in:sandbox,live'],
        ]);

        SystemSetting::set('fawry_merchant_code', $validated['merchant_code'], 'fawry');
        SystemSetting::set('fawry_security_key', $validated['security_key'], 'fawry');
        SystemSetting::set('fawry_merchant_wallet', $validated['merchant_wallet'] ?? '', 'fawry');
        SystemSetting::set('fawry_mode', $validated['mode'], 'fawry');

        return back()->with('success', 'تم حفظ وتحديث إعدادات بوابة فوري بنجاح.');
    }

    /**
     * Store new User from Super Admin.
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_super_admin' => ['nullable', 'boolean'],
            'plan_id' => ['nullable', 'exists:plans,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_super_admin' => (bool) ($validated['is_super_admin'] ?? false),
            'email_verified_at' => now(),
        ]);

        // Assign plan if provided
        if (!empty($validated['plan_id'])) {
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $validated['plan_id'],
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
            ]);
        }

        return back()->with('success', "تم إنشاء المستخدم {$user->name} بنجاح.");
    }

    /**
     * Update User from Super Admin.
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'is_super_admin' => ['nullable', 'boolean'],
            'plan_id' => ['nullable', 'exists:plans,id'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_super_admin' => (bool) ($validated['is_super_admin'] ?? false),
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        if (!empty($validated['plan_id'])) {
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id' => $validated['plan_id'],
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addYear(),
                ]
            );
        }

        return back()->with('success', "تم تحديث بيانات المستخدم {$user->name} بنجاح.");
    }

    /**
     * Delete User.
     */
    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        $currentAdmin = $request->user('web');

        if ($currentAdmin->id === $user->id) {
            return back()->with('error', 'لا يمكنك حذف حسابك الشخصي الحالي.');
        }

        if ($user->is_super_admin) {
            $otherAdminsCount = User::where('is_super_admin', true)->where('id', '!=', $user->id)->count();
            if ($otherAdminsCount === 0) {
                return back()->with('error', 'لا يمكنك حذف الأدمن الوحيد للنظام.');
            }
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "تم حذف المستخدم {$userName} بنجاح.");
    }

    /**
     * Assign / Change User Subscription Plan.
     */
    public function assignUserPlan(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $months = $validated['duration_months'] ?? 12;

        Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan_id' => $validated['plan_id'],
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addMonths($months),
            ]
        );

        return back()->with('success', "تم تعيين الباقة للمستخدم {$user->name} بنجاح.");
    }

    /**
     * Store new Business from Super Admin.
     */
    public function storeBusiness(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $business = Business::create([
            'name' => $validated['name'],
            'user_id' => $validated['user_id'],
        ]);

        return back()->with('success', "تم إنشاء المتجر {$business->name} بنجاح.");
    }

    /**
     * Update Business from Super Admin.
     */
    public function updateBusiness(Request $request, Business $business): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $business->update([
            'name' => $validated['name'],
            'user_id' => $validated['user_id'],
        ]);

        return back()->with('success', "تم تحديث بيانات المتجر {$business->name} بنجاح.");
    }

    /**
     * Delete Business from Super Admin.
     */
    public function destroyBusiness(Business $business): RedirectResponse
    {
        $name = $business->name;
        $business->delete();

        return back()->with('success', "تم حذف المتجر {$name} وجميع بياناته المرتبطة.");
    }

    /**
     * Store Pricing Plan.
     */
    public function storePlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_businesses' => ['nullable', 'integer', 'min:1'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'features' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plan = Plan::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'max_businesses' => $validated['max_businesses'] ?: null,
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'features' => $validated['features'] ?? [],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', "تمت إضافة باقة التسعير {$plan->name} بنجاح.");
    }

    /**
     * Update Pricing Plan.
     */
    public function updatePlan(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_businesses' => ['nullable', 'integer', 'min:1'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'features' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plan->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'max_businesses' => $validated['max_businesses'] ?: null,
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'features' => $validated['features'] ?? [],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', "تم تحديث باقة التسعير {$plan->name} بنجاح.");
    }

    /**
     * Delete Pricing Plan.
     */
    public function destroyPlan(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'لا يمكن حذف باقة مرتبطة بمشتركين فعليين. يمكنك تعطيلها بدلاً من ذلك.');
        }

        $name = $plan->name;
        $plan->delete();

        return back()->with('success', "تم حذف الباقة {$name} بنجاح.");
    }

    /**
     * Toggle Plan active status.
     */
    public function togglePlanActive(Plan $plan): RedirectResponse
    {
        $plan->is_active = !$plan->is_active;
        $plan->save();

        $status = $plan->is_active ? 'تفعيل' : 'تعطيل';
        return back()->with('success', "تم {$status} الباقة {$plan->name} بنجاح.");
    }

    /**
     * Clear application cache & optimize.
     */
    public function clearSystemCache(): RedirectResponse
    {
        Artisan::call('optimize:clear');

        return back()->with('success', 'تم تنظيف ذاكرة التخزين المؤقت (Cache) وإعادة تهيئة النظام بنجاح.');
    }

    /**
     * Impersonate / switch into a specific business as Super Admin.
     */
    public function impersonateBusiness(Business $business): RedirectResponse
    {
        session(['current_business_id' => $business->id]);

        return redirect()->route('dashboard')->with('success', "تم الدخول بنجاح لإدارة متجر: {$business->name}");
    }

    /**
     * Toggle Super Admin status for a user.
     */
    public function toggleSuperAdmin(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $currentAdmin = $request->user('web');

        // Safety: prevent removing own super admin if only admin
        if ($currentAdmin->id === $user->id && $user->is_super_admin) {
            $otherAdminsCount = User::where('is_super_admin', true)->where('id', '!=', $user->id)->count();
            if ($otherAdminsCount === 0) {
                return back()->with('error', 'لا يمكنك إلغاء صلاحية الإدارة العليا عن نفسك كأدمن وحيد.');
            }
        }

        $user->is_super_admin = !$user->is_super_admin;
        $user->save();

        return back()->with('success', "تم تحديث صلاحيات المستخدم {$user->name} بنجاح.");
    }

    /**
     * Restart a WhatsApp instance on Node microservice.
     */
    public function restartWhatsAppSession(string $sessionId): JsonResponse
    {
        try {
            $serviceUrl = config('services.whatsapp.service_url', 'http://127.0.0.1:3000');
            $response = Http::timeout(8)
                ->withHeaders(\App\Services\Channels\WhatsAppTokenService::getRequestHeaders())
                ->post("{$serviceUrl}/api/sessions/start", [
                'session_id' => $sessionId,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "تمت إعادة تشغيل الجلسة {$sessionId} بنجاح.",
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'failed',
                'message' => "تعذر إعادة تشغيل الجلسة {$sessionId}.",
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
