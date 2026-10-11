<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * وحدة التحكم بإدارة جهات الاتصال والعملاء وتصدير البيانات
 */
class ContactController extends Controller
{
    /**
     * التحقق من صلاحية الوصول لجهة الاتصال وتطابق المتجر
     */
    protected function authorizeContactAccess(Contact $contact): void
    {
        $currentBusinessId = session('current_business_id');
        $user = auth('web')->user() ?? auth('team')->user();

        if (!$user) {
            abort(401);
        }

        if ((int) $contact->business_id !== (int) $currentBusinessId) {
            abort(403, 'غير مصرح لك بالوصول لبيانات هذه الجهة.');
        }
    }

    /**
     * عرض قائمة جهات الاتصال الخاصة بالمتجر الحالي مع البحث والتصفح
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $currentBusinessId = session('current_business_id');

        if (!$currentBusinessId) {
            return redirect()->route('businesses.index');
        }

        $business = Business::findOrFail($currentBusinessId);
        $search = $request->query('search');

        $query = Contact::where('business_id', $business->id)
            ->withCount('conversations')
            ->with(['conversations' => function ($q) {
                $q->latest('last_message_at')->limit(1)->select(['id', 'contact_id', 'platform', 'status', 'last_message_at']);
            }]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('platform_sender_id', 'like', "%{$search}%");
            });
        }

        $contacts = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return Inertia::render('Contacts/Index', [
            'business' => $business->only(['id', 'name']),
            'contacts' => $contacts,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * عرض تفاصيل جهة الاتصال ومحادثاتها
     */
    public function show(Request $request, Contact $contact): JsonResponse|Response
    {
        $this->authorizeContactAccess($contact);

        $contact->loadCount('conversations');
        $contact->load([
            'conversations' => fn ($q) => $q->latest('last_message_at')->with('latestMessage'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'contact' => $contact,
            ]);
        }

        $currentBusinessId = session('current_business_id');
        $business = Business::findOrFail($currentBusinessId);

        return Inertia::render('Contacts/Show', [
            'business' => $business->only(['id', 'name']),
            'contact' => $contact,
        ]);
    }

    /**
     * تحديث بيانات جهة الاتصال (الاسم، الهاتف، البريد، الملاحظات، الوسوم)
     */
    public function update(Request $request, Contact $contact): JsonResponse|RedirectResponse
    {
        $this->authorizeContactAccess($contact);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable'],
        ]);

        // التعامل مع الوسوم سواء جاءت كمصفوفة أو كنص مفصول بفواصل
        $tags = $validated['tags'] ?? [];
        if (is_string($tags)) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
        }

        $contact->update([
            'name' => $validated['name'] ?? $contact->name,
            'phone' => $validated['phone'] ?? $contact->phone,
            'email' => $validated['email'] ?? $contact->email,
            'notes' => $validated['notes'] ?? $contact->notes,
            'tags' => $tags,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'contact' => $contact->fresh(),
            ]);
        }

        return back()->with('success', 'تم تحديث بيانات جهة الاتصال بنجاح.');
    }

    /**
     * تصدير جميع جهات اتصال المتجر الحالي كملف CSV
     */
    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $currentBusinessId = session('current_business_id');

        if (!$currentBusinessId) {
            return redirect()->route('businesses.index');
        }

        $business = Business::findOrFail($currentBusinessId);
        $filename = 'contacts-' . $business->id . '-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($business) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for proper Arabic display in Excel
            fputs($file, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($file, [
                'ID',
                'الاسم',
                'رقم الهاتف',
                'البريد الإلكتروني',
                'المنصة',
                'معرف المنصة',
                'الوسوم',
                'الملاحظات',
                'تاريخ الإنشاء',
            ]);

            Contact::where('business_id', $business->id)
                ->orderBy('id')
                ->chunk(200, function ($contacts) use ($file) {
                    foreach ($contacts as $contact) {
                        fputcsv($file, [
                            $contact->id,
                            $contact->name ?? '',
                            $contact->phone ?? '',
                            $contact->email ?? '',
                            $contact->platform,
                            $contact->platform_sender_id,
                            is_array($contact->tags) ? implode(', ', $contact->tags) : '',
                            $contact->notes ?? '',
                            $contact->created_at?->format('Y-m-d H:i') ?? '',
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
