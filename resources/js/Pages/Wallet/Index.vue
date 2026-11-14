<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Wallet, Send, CreditCard, Sparkles, Check, Clock,
    Copy, ExternalLink, Zap, CheckCircle2, ArrowUpRight, Plus, AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    wallet: {
        type: Object,
        required: true,
    },
    packages: {
        type: Array,
        default: () => [],
    },
    transactions: {
        type: Array,
        default: () => [],
    },
    fawryConfig: {
        type: Object,
        default: () => ({ mode: 'sandbox' }),
    },
});

const isCheckingOut = ref(false);
const activeChargeModal = ref(null);
const isSimulating = ref(false);
const copiedCode = ref(false);

// Custom topup inputs
const customCredits = ref(1000);
const customAmount = ref(200);

const calculateCustomAmount = () => {
    // 0.20 EGP per message for custom
    customAmount.value = Math.max(10, Math.round(customCredits.value * 0.20));
};

const initiateTopup = async (pkg) => {
    isCheckingOut.value = true;
    try {
        const response = await fetch(route('wallet.topup'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                amount: pkg.price,
                credits: pkg.credits,
                package_name: pkg.name,
            }),
        });

        const data = await response.json();
        if (data.status === 'success' && data.charge) {
            activeChargeModal.value = data.charge;
        } else {
            alert(data.message || 'حدث خطأ أثناء إعداد طلب شحن الرصيد.');
        }
    } catch (e) {
        alert('تعذر الاتصال بخادم الدفع. حاول مرة أخرى.');
    } finally {
        isCheckingOut.value = false;
    }
};

const initiateCustomTopup = async () => {
    isCheckingOut.value = true;
    try {
        const response = await fetch(route('wallet.topup'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                amount: customAmount.value,
                credits: customCredits.value,
                package_name: `شحن مخصص (${customCredits.value} رسالة)`,
            }),
        });

        const data = await response.json();
        if (data.status === 'success' && data.charge) {
            activeChargeModal.value = data.charge;
        } else {
            alert(data.message || 'حدث خطأ أثناء إعداد طلب شحن الرصيد.');
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
        const response = await fetch(route('wallet.simulate'), {
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
            router.reload({ only: ['wallet', 'transactions'] });
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
        <Head title="محفظة رصيد الرسائل الجماعية - Vertex" />

        <div class="max-w-6xl mx-auto space-y-6 text-start">
            <!-- Header Balance Banner -->
            <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-emerald-950/40 to-slate-900 border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <Sparkles class="w-3 h-3 text-emerald-400" />
                        <span>نظام المحاسبة بالرسالة (Pay-As-You-Go)</span>
                    </div>
                    <h1 class="text-xl font-black text-white">محفظة رصيد الإرسال الجماعي (Bulk Senders)</h1>
                    <p class="text-xs text-slate-400">
                        خدمات الإرسال الجماعي (واتساب، تيليجرام، فيسبوك، إنستجرام) تُخصم مباشرة من رصيد رسائلك.
                    </p>
                </div>

                <!-- Current Balance Card -->
                <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-950/80 border border-slate-800">
                    <div>
                        <span class="text-[10px] font-mono text-slate-400 block mb-0.5">الرصيد المتاح</span>
                        <div class="text-2xl font-black text-emerald-400 tracking-tight">
                            {{ Number(wallet.credits || 0).toLocaleString() }}
                            <span class="text-xs font-normal text-slate-400">رسالة</span>
                        </div>
                        <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                            الرصيد المالي: {{ wallet.balance }} {{ wallet.currency }}
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                        <Wallet class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Recharge Packages Grid -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white flex items-center gap-2">
                        <Zap class="w-4 h-4 text-amber-400" />
                        <span>باقات شحن رصيد الرسائل الجماعية عبر فوري (Fawry)</span>
                    </h2>
                    <span class="text-[11px] text-slate-400">بدون تاريخ انتهاء صلاحية للرصيد المشحون</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="pkg in packages"
                        :key="pkg.id"
                        class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition flex flex-col justify-between space-y-4 relative"
                    >
                        <span
                            v-if="pkg.badge"
                            class="absolute -top-2.5 end-3 px-2 py-0.5 rounded-full text-[9px] font-mono font-bold bg-amber-500 text-slate-950 uppercase"
                        >
                            {{ pkg.badge }}
                        </span>

                        <div class="space-y-2">
                            <h3 class="font-bold text-xs text-white">{{ pkg.name }}</h3>
                            <div class="text-2xl font-black text-white">
                                {{ Number(pkg.credits).toLocaleString() }}
                                <span class="text-xs font-normal text-slate-400">رسالة</span>
                            </div>
                            <div class="text-sm font-bold text-emerald-400">
                                {{ pkg.price }} {{ pkg.currency }}
                            </div>
                            <div class="text-[10px] text-slate-500 font-mono">
                                تكلفة الرسالة: {{ pkg.price_per_message }}
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="initiateTopup(pkg)"
                            :disabled="isCheckingOut"
                            class="w-full flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition disabled:opacity-50"
                        >
                            <CreditCard class="w-3.5 h-3.5" />
                            <span>شحن الآن عبر فوري</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Custom Topup Calculator -->
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h3 class="text-xs font-bold text-slate-200 flex items-center gap-2">
                    <Plus class="w-4 h-4 text-blue-400" />
                    <span>شحن رصيد مخصص حسب احتياجك</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">عدد الرسائل المطلوبة</label>
                        <input
                            type="number"
                            v-model.number="customCredits"
                            @input="calculateCustomAmount"
                            min="100"
                            step="100"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-blue-500 font-mono"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">المبلغ المطلوب سداده (ج.م)</label>
                        <input
                            type="number"
                            v-model.number="customAmount"
                            readonly
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-emerald-400 font-bold font-mono"
                        />
                    </div>
                    <div>
                        <button
                            type="button"
                            @click="initiateCustomTopup"
                            :disabled="isCheckingOut || customCredits < 50"
                            class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-600/20 transition disabled:opacity-50 flex items-center justify-center gap-1.5"
                        >
                            <CreditCard class="w-4 h-4" />
                            <span>دفع المبلغ وتأكيد الشحن</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transaction History Table -->
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <h3 class="text-xs font-bold text-slate-200 flex items-center gap-2">
                    <Clock class="w-4 h-4 text-slate-400" />
                    <span>سجل حركات المحفظة ورصيد الرسائل ({{ transactions.length }})</span>
                </h3>

                <div v-if="transactions.length === 0" class="text-center py-6 text-xs text-slate-500">
                    لا توجد حركات مسجلة في المحفظة حتى الآن.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-800 pb-2">
                                <th class="text-start py-2">نوع الحركة</th>
                                <th class="text-start py-2">الوصف</th>
                                <th class="text-start py-2">الرسائل</th>
                                <th class="text-start py-2">المبلغ</th>
                                <th class="text-start py-2">الرصيد بعد الحركة</th>
                                <th class="text-start py-2">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="t in transactions" :key="t.id" class="text-slate-300">
                                <td class="py-2.5">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                        :class="{
                                            'bg-emerald-500/20 text-emerald-400': t.type === 'topup',
                                            'bg-rose-500/20 text-rose-400': t.type === 'deduct' || t.type === 'usage',
                                            'bg-amber-500/20 text-amber-300': t.type === 'refund',
                                            'bg-purple-500/20 text-purple-300': t.type === 'manual_adjustment',
                                        }"
                                    >
                                        {{ t.type === 'topup' ? 'شحن رصيد' : (t.type === 'deduct' || t.type === 'usage' ? 'خصم إرسال' : (t.type === 'refund' ? 'استرداد' : 'تعديل إداري')) }}
                                    </span>
                                </td>
                                <td class="py-2.5">{{ t.description }}</td>
                                <td class="py-2.5 font-bold font-mono" :class="t.credits >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ t.credits > 0 ? '+' : '' }}{{ Number(t.credits).toLocaleString() }}
                                </td>
                                <td class="py-2.5 font-mono">{{ t.amount }} ج.م</td>
                                <td class="py-2.5 font-mono font-bold text-white">{{ Number(t.credits_after).toLocaleString() }} رسالة</td>
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
                        <h3 class="text-base font-bold text-white">سداد شحن الرصيد عبر فوري (FawryPay)</h3>
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
                        <span class="text-slate-400">الرصيد المشحون:</span>
                        <span class="font-bold text-emerald-400">{{ Number(activeChargeModal.credits).toLocaleString() }} رسالة</span>
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
                        <span>وضع تجريبي (Sandbox Mode): يمكنك اختبار الشحن فوراً</span>
                    </div>
                    <button
                        type="button"
                        @click="simulatePayment"
                        :disabled="isSimulating"
                        class="w-full py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center justify-center gap-1.5"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        <span>{{ isSimulating ? 'جاري محاكاة الشحن...' : 'محاكاة سداد فوري ناجح وإضافة الرصيد للمحفظة الآن' }}</span>
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
