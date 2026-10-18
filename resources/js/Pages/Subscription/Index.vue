<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Sparkles, Check, Zap, CreditCard, ShieldCheck, Clock,
    AlertCircle, Copy, ExternalLink, RefreshCw, CheckCircle2, ArrowUpRight
} from 'lucide-vue-next';

const props = defineProps({
    activeSubscription: {
        type: Object,
        default: null,
    },
    plans: {
        type: Array,
        default: () => [],
    },
    recentTransactions: {
        type: Array,
        default: () => [],
    },
    fawryConfig: {
        type: Object,
        default: () => ({ mode: 'sandbox' }),
    },
});

const page = usePage();
const billingCycle = ref('monthly');
const isCheckingOut = ref(false);
const activeChargeModal = ref(null);
const isSimulating = ref(false);
const copiedCode = ref(false);

// Check if flash pending charge exists
if (page.props.flash?.pendingCharge) {
    activeChargeModal.value = page.props.flash.pendingCharge;
}

const currentPlan = computed(() => props.activeSubscription?.plan);

const selectBillingCycle = (cycle) => {
    billingCycle.value = cycle;
};

const initiateCheckout = async (plan) => {
    isCheckingOut.value = true;
    try {
        const response = await fetch(route('subscription.checkout'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                plan_id: plan.id,
                billing_cycle: billingCycle.value,
            }),
        });

        const data = await response.json();
        if (data.status === 'success' && data.charge) {
            activeChargeModal.value = data.charge;
        } else {
            alert(data.message || 'حدث خطأ أثناء تجهيز طلب الدفع.');
        }
    } catch (e) {
        alert('تعذر الاتصال بخادم الدفع. حاول مرة أخرى.');
    } finally {
        isCheckingOut.value = false;
    }
};

const copyReferenceCode = () => {
    if (!activeChargeModal.value?.fawry_reference_number) return;
    navigator.clipboard.writeText(activeChargeModal.value.fawry_reference_number);
    copiedCode.value = true;
    setTimeout(() => { copiedCode.value = false; }, 2000);
};

const simulatePayment = async () => {
    if (!activeChargeModal.value?.reference_number) return;
    isSimulating.value = true;
    try {
        const response = await fetch(route('subscription.simulate'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                reference_number: activeChargeModal.value.reference_number,
            }),
        });

        const data = await response.json();
        if (data.status === 'success') {
            activeChargeModal.value = null;
            router.reload({ only: ['activeSubscription', 'recentTransactions'] });
        } else {
            alert(data.message || 'فشلت عملية المحاكاة.');
        }
    } catch (e) {
        alert('حدث خطأ أثناء محاكاة السداد.');
    } finally {
        isSimulating.value = false;
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="إدارة الاشتراكات وباقات التسعير - Vertex" />

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header Banner -->
            <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950/40 to-slate-900 border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                        <Sparkles class="w-3 h-3 text-blue-400" />
                        <span>بوابات الدفع الإلكتروني</span>
                    </div>
                    <h1 class="text-xl font-black text-white">إدارة باقة الاشتراك والدفع عبر فوري (Fawry)</h1>
                    <p class="text-xs text-slate-400">
                        الخطط تشمل خدمات صندوق الوارد والردود الآلية وإعادة الاستهداف. الإرسال الجماعي يحاسب حسب الرسالة.
                    </p>
                </div>

                <!-- Current Subscription Status Pill -->
                <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-end">
                    <span class="text-[10px] font-mono text-slate-400 block mb-0.5">الباقة الحالية</span>
                    <div class="text-sm font-black text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" :class="activeSubscription?.status === 'active' ? 'bg-emerald-400' : (activeSubscription?.status === 'trial' ? 'bg-blue-400' : 'bg-rose-400')"></span>
                        <span>{{ currentPlan?.name || 'حساب غير مفعل' }}</span>
                        <span v-if="activeSubscription" class="text-[10px] px-1.5 py-0.2 rounded font-mono" :class="activeSubscription.status === 'active' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-blue-500/20 text-blue-300'">
                            {{ activeSubscription.status === 'active' ? 'نشطة' : 'فترة تجريبية' }}
                        </span>
                        <span v-else class="text-[10px] px-1.5 py-0.2 rounded font-mono bg-rose-500/20 text-rose-400 border border-rose-500/30">
                            بانتظار السداد والتفعيل
                        </span>
                    </div>
                    <span v-if="activeSubscription?.ends_at" class="text-[10px] text-slate-400 mt-1 block">
                        تنتهي في: {{ new Date(activeSubscription.ends_at).toLocaleDateString('ar-EG') }}
                    </span>
                    <span v-else-if="!activeSubscription" class="text-[10px] text-amber-400 mt-1 block">
                        يرجى السداد لتفعيل حسابك
                    </span>
                </div>
            </div>

            <!-- Billing Cycle Toggle -->
            <div class="flex items-center justify-center gap-3">
                <span class="text-xs font-semibold text-slate-300">نظام المحاسبة:</span>
                <div class="flex items-center p-0.5 rounded-xl bg-slate-900 border border-slate-800 text-xs">
                    <button
                        type="button"
                        @click="selectBillingCycle('monthly')"
                        class="px-4 py-1.5 rounded-lg transition font-bold"
                        :class="billingCycle === 'monthly' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
                    >
                        دفع شهري
                    </button>
                    <button
                        type="button"
                        @click="selectBillingCycle('yearly')"
                        class="px-4 py-1.5 rounded-lg transition font-bold flex items-center gap-1.5"
                        :class="billingCycle === 'yearly' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
                    >
                        <span>دفع سنوي</span>
                        <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-400">وفر شهرين</span>
                    </button>
                </div>
            </div>

            <!-- Available Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="p-6 rounded-2xl bg-slate-900/80 border transition flex flex-col justify-between relative"
                    :class="currentPlan?.id === plan.id
                        ? 'border-blue-500 ring-1 ring-blue-500/50 shadow-xl shadow-blue-500/10'
                        : 'border-slate-800 hover:border-slate-700'"
                >
                    <!-- Current Badge -->
                    <div v-if="currentPlan?.id === plan.id" class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full text-[10px] font-mono font-bold bg-blue-600 text-white shadow-sm">
                        باقتك الحالية
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h3 class="font-bold text-base text-white">{{ plan.name }}</h3>
                            <p class="text-xs text-slate-400 mt-1 line-clamp-2">
                                {{ plan.description || 'أتمتة الردود وإعادة الاستهداف وتوحيد القنوات' }}
                            </p>
                        </div>

                        <div class="text-2xl font-black text-white">
                            {{ billingCycle === 'yearly' ? plan.price_yearly : plan.price_monthly }}
                            <span class="text-xs font-normal text-slate-400">ج.م / {{ billingCycle === 'yearly' ? 'سنوياً' : 'شهرياً' }}</span>
                        </div>

                        <!-- Features List -->
                        <ul class="space-y-2 text-xs text-slate-300 pt-2 border-t border-slate-800">
                            <li class="flex items-center gap-2">
                                <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                <span>{{ plan.max_businesses ? plan.max_businesses + ' متاجر نشطة' : 'متاجر غير محدودة' }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                <span>صندوق الوارد الموحد (Unified Inbox)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                                <span>حملات إعادة الاستهداف (Retargeting)</span>
                            </li>
                            <li class="flex items-center gap-2 text-slate-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0 ms-1 me-1"></span>
                                <span>الإرسال الجماعي: دفع حسب الرسالة</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-6 pt-4 border-t border-slate-800">
                        <button
                            v-if="currentPlan?.id === plan.id"
                            disabled
                            class="w-full py-2.5 rounded-xl bg-slate-800 text-slate-400 text-xs font-bold text-center cursor-default"
                        >
                            باقتك المفعلة حالياً
                        </button>
                        <button
                            v-else
                            @click="initiateCheckout(plan)"
                            :disabled="isCheckingOut"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/20 transition disabled:opacity-50"
                        >
                            <CreditCard class="w-4 h-4" />
                            <span>ترقية الباقة عبر فوري (Fawry)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h3 class="text-xs font-bold text-slate-200 flex items-center gap-2">
                    <Clock class="w-4 h-4 text-slate-400" />
                    <span>سجل عمليات الدفع والاشتراك ({{ recentTransactions.length }})</span>
                </h3>

                <div v-if="recentTransactions.length === 0" class="text-center py-6 text-xs text-slate-500">
                    لا توجد عمليات سداد سابقة مسجلة.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-800 pb-2">
                                <th class="text-start py-2 font-mono">رقم المرجع (Fawry Ref)</th>
                                <th class="text-start py-2">الباقة</th>
                                <th class="text-start py-2">المبلغ</th>
                                <th class="text-start py-2">الحالة</th>
                                <th class="text-start py-2">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="t in recentTransactions" :key="t.id" class="text-slate-300">
                                <td class="py-2.5 font-mono text-blue-400 font-bold">
                                    {{ t.fawry_reference_number || t.reference_number }}
                                </td>
                                <td class="py-2.5">{{ t.plan?.name || 'اشتراك باقة' }}</td>
                                <td class="py-2.5 font-bold text-white">{{ t.amount }} {{ t.currency }}</td>
                                <td class="py-2.5">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                        :class="t.status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-300'"
                                    >
                                        {{ t.status === 'paid' ? 'مكتمل' : 'قيد السداد' }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-slate-400 font-mono text-[11px]">
                                    {{ new Date(t.created_at).toLocaleDateString('ar-EG') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- FAWRY CHECKOUT MODAL -->
        <div v-if="activeChargeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md p-6 rounded-2xl bg-slate-900 border border-slate-700 shadow-2xl space-y-5 text-start relative">
                <!-- Close Button -->
                <button
                    type="button"
                    @click="activeChargeModal = null"
                    class="absolute top-4 end-4 text-slate-400 hover:text-white"
                >
                    ✕
                </button>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
                        <CreditCard class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">إتمام السداد عبر فوري (FawryPay)</h3>
                        <p class="text-xs text-slate-400">الدفع متاح في أي منفذ فوري أو محفظة إلكترونية</p>
                    </div>
                </div>

                <!-- Reference Code Box -->
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2 text-center">
                    <span class="text-[11px] font-mono text-slate-400 uppercase tracking-wider block">الرقم المرجعي للدفع (Fawry Reference Code)</span>
                    <div class="text-2xl font-mono font-black text-amber-400 tracking-wider">
                        {{ activeChargeModal.fawry_reference_number }}
                    </div>
                    <button
                        type="button"
                        @click="copyReferenceCode"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-[11px] font-bold text-slate-300 border border-slate-800 transition"
                    >
                        <Check v-if="copiedCode" class="w-3.5 h-3.5 text-emerald-400" />
                        <Copy v-else class="w-3.5 h-3.5" />
                        <span>{{ copiedCode ? 'تم النسخ!' : 'نسخ الرقم المرجعي' }}</span>
                    </button>
                </div>

                <!-- Order Details -->
                <div class="space-y-1.5 text-xs text-slate-300 p-3 rounded-xl bg-slate-950/60 border border-slate-800/80">
                    <div class="flex justify-between">
                        <span class="text-slate-400">الباقة المختارة:</span>
                        <span class="font-bold text-white">{{ activeChargeModal.plan?.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">نظام الفوترة:</span>
                        <span>{{ activeChargeModal.plan?.billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}</span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-slate-800">
                        <span class="text-slate-400 font-bold">إجمالي المبلغ:</span>
                        <span class="text-sm font-black text-emerald-400">{{ activeChargeModal.amount }} {{ activeChargeModal.currency }}</span>
                    </div>
                </div>

                <!-- Sandbox Simulation Button (strictly sandbox mode only) -->
                <div v-if="activeChargeModal.fawry_mode === 'sandbox' && fawryConfig.mode === 'sandbox'" class="p-3 rounded-xl bg-blue-950/40 border border-blue-500/30 space-y-2">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-blue-300">
                        <Zap class="w-3.5 h-3.5 text-amber-400" />
                        <span>وضع تجريبي (Sandbox Mode): يمكنك اختبار السداد فوراً</span>
                    </div>
                    <button
                        type="button"
                        @click="simulatePayment"
                        :disabled="isSimulating"
                        class="w-full py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center justify-center gap-1.5"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        <span>{{ isSimulating ? 'جاري محاكاة السداد...' : 'محاكاة سداد فوري ناجح وتفعيل الباقة الآن' }}</span>
                    </button>
                </div>

                <!-- External Fawry Link if available -->
                <a
                    v-if="activeChargeModal.checkout_url"
                    :href="activeChargeModal.checkout_url"
                    target="_blank"
                    class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition"
                >
                    <span>الانتقال لبوابة فوري باي (FawryPay)</span>
                    <ExternalLink class="w-4 h-4" />
                </a>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
