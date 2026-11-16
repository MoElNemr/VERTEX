<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    /**
     * Display the System User Guide & Knowledge Base.
     */
    public function index(Request $request): Response
    {
        $currentBusinessId = session('current_business_id');
        $user = $request->user();
        $business = null;

        if ($currentBusinessId) {
            $business = Business::find($currentBusinessId);
        }

        if (! $business && $user) {
            $business = $user->businesses()->first();
        }

        return Inertia::render('Guide/Index', [
            'business' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }
}
