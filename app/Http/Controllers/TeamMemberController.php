<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamMemberController extends Controller
{
    /**
     * Get authenticated owner ID with role check.
     */
    protected function getAuthorizedOwnerId(Request $request): int
    {
        $user = $request->user('web') ?: $request->user('team');

        if (!$user) {
            abort(401);
        }

        if ($request->user('web')) {
            return $user->id;
        }

        // Team guard: only admin can manage team
        if ($user->role !== 'admin') {
            abort(403, 'غير مصرح لك بإدارة فريق العمل. الصلاحية متاحة للمسؤولين فقط.');
        }

        return $user->user_id;
    }

    /**
     * Display a listing of team members.
     */
    public function index(Request $request): Response
    {
        $ownerId = $this->getAuthorizedOwnerId($request);

        $businesses = Business::where('user_id', $ownerId)
            ->select(['id', 'name'])
            ->get();

        $query = TeamMember::where('user_id', $ownerId)
            ->with(['businesses:id,name']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('business_id')) {
            $businessId = $request->business_id;
            $query->whereHas('businesses', function ($bQ) use ($businessId) {
                $bQ->where('businesses.id', $businessId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $members = $query->latest()->get();

        // إحصائيات الفريق
        $allMembers = TeamMember::where('user_id', $ownerId)->get();
        $stats = [
            'total' => $allMembers->count(),
            'admins' => $allMembers->where('role', 'admin')->count(),
            'supervisors' => $allMembers->where('role', 'supervisor')->count(),
            'agents' => $allMembers->where('role', 'agent')->count(),
            'active' => $allMembers->where('is_active', true)->count(),
        ];

        return Inertia::render('Team/Index', [
            'members' => $members,
            'businesses' => $businesses,
            'stats' => $stats,
            'filters' => $request->only(['search', 'role', 'business_id']),
        ]);
    }

    /**
     * Store a newly created team member.
     */
    public function store(Request $request): RedirectResponse
    {
        $ownerId = $this->getAuthorizedOwnerId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:team_members,email',
                'unique:users,email',
            ],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,supervisor,agent'],
            'business_ids' => ['required', 'array', 'min:1'],
            'business_ids.*' => ['exists:businesses,id'],
        ], [
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً في النظام.',
            'business_ids.required' => 'يجب ربط العضو بمتجر واحد على الأقل.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 خانات.',
        ]);

        // التحقق الدقيق من عزل المستأجرين (Tenant Separation)
        $validBusinessCount = Business::where('user_id', $ownerId)
            ->whereIn('id', $validated['business_ids'])
            ->count();

        if ($validBusinessCount !== count($validated['business_ids'])) {
            abort(403, 'غير مصرح بربط متاجر لا تنتمي لحسابك.');
        }

        $member = TeamMember::create([
            'user_id' => $ownerId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        $member->businesses()->sync($validated['business_ids']);

        return redirect()->route('team.index')->with('success', 'تم إنشاء حساب عضو الفريق بنجاح.');
    }

    /**
     * Update the specified team member.
     */
    public function update(Request $request, TeamMember $teamMember): RedirectResponse
    {
        $ownerId = $this->getAuthorizedOwnerId($request);

        // التحقق من عزل المستأجرين
        if ($teamMember->user_id !== $ownerId) {
            abort(403, 'غير مصرح بتعديل هذا العضو.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:team_members,email,'.$teamMember->id,
                'unique:users,email',
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,supervisor,agent'],
            'is_active' => ['boolean'],
            'business_ids' => ['required', 'array', 'min:1'],
            'business_ids.*' => ['exists:businesses,id'],
        ], [
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً في النظام.',
            'business_ids.required' => 'يجب ربط العضو بمتجر واحد على الأقل.',
        ]);

        // التحقق من عزل المتاجر
        $validBusinessCount = Business::where('user_id', $ownerId)
            ->whereIn('id', $validated['business_ids'])
            ->count();

        if ($validBusinessCount !== count($validated['business_ids'])) {
            abort(403, 'غير مصرح بربط متاجر لا تنتمي لحسابك.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $teamMember->update($updateData);
        $teamMember->businesses()->sync($validated['business_ids']);

        return redirect()->route('team.index')->with('success', 'تم تحديث بيانات العضو بنجاح.');
    }

    /**
     * Toggle active/inactive status of team member.
     */
    public function toggleStatus(Request $request, TeamMember $teamMember): RedirectResponse
    {
        $ownerId = $this->getAuthorizedOwnerId($request);

        if ($teamMember->user_id !== $ownerId) {
            abort(403, 'غير مصرح بتعديل هذا العضو.');
        }

        if ($request->user('team') && $request->user('team')->id === $teamMember->id) {
            return back()->with('error', 'لا يمكنك تغيير حالة حسابك الحالي.');
        }

        $teamMember->update([
            'is_active' => !$teamMember->is_active,
        ]);

        $statusText = $teamMember->is_active ? 'تفعيل' : 'تعطيل';
        return redirect()->route('team.index')->with('success', "تم {$statusText} حساب العضو بنجاح.");
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Request $request, TeamMember $teamMember): RedirectResponse
    {
        $ownerId = $this->getAuthorizedOwnerId($request);

        if ($teamMember->user_id !== $ownerId) {
            abort(403, 'غير مصرح بحذف هذا العضو.');
        }

        if ($request->user('team') && $request->user('team')->id === $teamMember->id) {
            return back()->with('error', 'لا يمكنك حذف حسابك الحالي.');
        }

        $teamMember->businesses()->detach();
        $teamMember->delete();

        return redirect()->route('team.index')->with('success', 'تم حذف عضو الفريق بنجاح.');
    }
}
