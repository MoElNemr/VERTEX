<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    /**
     * Display a listing of the user's businesses.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $businesses = $user->businesses()
            ->with([
                'whatsappAccount:business_id,phone_number,status',
                'facebookAccount:business_id,page_name,status',
                'instagramAccount:business_id,username,status',
                'telegramAccount:business_id,bot_username,status',
            ])
            ->withCount(['conversations', 'teamMembers'])
            ->latest()
            ->get();

        return Inertia::render('Businesses/Index', [
            'businesses' => $businesses,
            'canCreateBusiness' => $user->canCreateBusiness(),
            'currentSubscription' => $user->activeSubscription()->with('plan')->first(),
        ]);
    }

    /**
     * Store a newly created business in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->canCreateBusiness()) {
            return back()->withErrors([
                'name' => 'لقد وصلت للحد الأقصى لعدد البيزنسس المسموح بها في باقتك الحالية. يرجى الترقية للمتابعة.',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $business = $user->businesses()->create($validated);

        // تعيين البيزنس الجديد كالبيزنس النشط تلقائياً
        session(['current_business_id' => $business->id]);

        return redirect()->route('businesses.index')->with('success', 'تم إنشاء البيزنس بنجاح');
    }

    /**
     * Switch current active business.
     */
    public function switch(Request $request, Business $business): RedirectResponse
    {
        $user = $request->user('web') ?: $request->user('team');

        $hasAccess = Business::where('id', $business->id)
            ->where(function ($q) use ($user, $request) {
                if ($request->user('web')) {
                    $q->where('user_id', $user->id);
                } else {
                    $q->whereHas('teamMembers', function ($tmQ) use ($user) {
                        $tmQ->where('email', $user->email);
                    });
                }
            })->exists();

        if (!$hasAccess) {
            abort(403, 'غير مصرح لك بالوصول لهذا البيزنس.');
        }

        session(['current_business_id' => $business->id]);

        return back();
    }
}
