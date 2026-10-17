<script setup>
import { ref, computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    User, Mail, Lock, Eye, EyeOff, UserPlus, Loader2, Sparkles, Check,
    CreditCard, ArrowLeft, ShieldCheck, Zap
} from 'lucide-vue-next';

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
});

const billingCycle = ref('monthly'); // 'monthly' or 'yearly'
const showPassword = ref(false);

// Default to standard or first plan
const defaultPlanId = computed(() => {
    if (!props.plans || props.plans.length === 0) return '';
    const standard = props.plans.find(p => p.type === 'standard');
    return standard ? standard.id : props.plans[0].id;
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    plan_id: defaultPlanId.value,
    billing_cycle: 'monthly',
});

// Sync billing cycle to form
const selectBillingCycle = (cycle) => {
    billingCycle.value = cycle;
    form.billing_cycle = cycle;
};

const selectPlan = (planId) => {
    form.plan_id = planId;
};

const selectedPlan = computed(() => {
    return props.plans.find(p => p.id === form.plan_id) || props.plans[0];
});

const submit = () => {
    form.billing_cycle = billingCycle.value;
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="إنشاء حساب واختيار الباقة - Vertex" />

        <!-- Header -->
        <div class="mb-6 text-center">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 mb-2">
                <Sparkles class="w-3.5 h-3.5 text-blue-400" />
                <span>اختر باقتك وسدد بسهولة عبر فوري (Fawry)</span>
            </div>
            <h2 class="text-2xl font-black text-white tracking-tight">إنشاء حساب متجر جديد</h2>
            <p class="text-xs text-slate-400 mt-1">أتمتة الردود وإعادة الاستهداف الذكية لكافة قنوات متجرك</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 1. USER CREDENTIALS -->
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3.5">
                <h3 class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                    <User class="w-4 h-4 text-blue-400" />
                    <span>بيانات الحساب</span>
                </h3>

                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-300 mb-1 text-start">
                        الاسم الكامل / اسم المتجر
                    </label>
                    <div class="relative">
                        <User class="w-4 h-4 text-slate-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            id="name"
                            type="text"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="محمد علي"
                            class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        />
                    </div>
                    <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 mb-1 text-start">
                        البريد الإلكتروني
                    </label>
                    <div class="relative">
                        <Mail class="w-4 h-4 text-slate-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="name@store.com"
                            class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        />
                    </div>
                    <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.email" />
                </div>

                <!-- Password Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-300 mb-1 text-start">
                            كلمة المرور
                        </label>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-slate-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full ps-10 pe-10 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute end-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.password" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1 text-start">
                            تأكيد كلمة المرور
                        </label>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-slate-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input
                                id="password_confirmation"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                            />
                        </div>
                        <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.password_confirmation" />
                    </div>
                </div>
            </div>

            <!-- 2. PRICING PLAN SELECTOR -->
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                        <Zap class="w-4 h-4 text-amber-400" />
                        <span>اختر باقة الاشتراك</span>
                    </h3>

                    <!-- Billing Cycle Toggle -->
                    <div class="flex items-center p-0.5 rounded-lg bg-slate-950 border border-slate-800 text-[11px]">
                        <button
                            type="button"
                            @click="selectBillingCycle('monthly')"
                            class="px-2.5 py-1 rounded-md transition font-medium"
                            :class="billingCycle === 'monthly' ? 'bg-blue-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                        >
                            شهرياً
                        </button>
                        <button
                            type="button"
                            @click="selectBillingCycle('yearly')"
                            class="px-2.5 py-1 rounded-md transition font-medium flex items-center gap-1"
                            :class="billingCycle === 'yearly' ? 'bg-blue-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                        >
                            <span>سنوياً</span>
                            <span class="text-[9px] px-1 py-0.2 rounded bg-emerald-500/20 text-emerald-400">وفر شهرين</span>
                        </button>
                    </div>
                </div>

                <div v-if="plans.length === 0" class="text-center py-4 text-xs text-slate-500">
                    جاري تحميل باقات التسعير...
                </div>

                <!-- Plans Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        @click="selectPlan(plan.id)"
                        class="p-4 rounded-xl border text-start cursor-pointer transition relative"
                        :class="form.plan_id === plan.id
                            ? 'bg-blue-950/40 border-blue-500 shadow-md shadow-blue-500/10'
                            : 'bg-slate-950/50 border-slate-800 hover:border-slate-700'"
                    >
                        <!-- Selected Badge -->
                        <div v-if="form.plan_id === plan.id" class="absolute top-3 end-3 w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center">
                            <Check class="w-3 h-3" />
                        </div>

                        <div class="text-xs font-bold text-white flex items-center gap-1.5 mb-1">
                            <span>{{ plan.name }}</span>
                            <span v-if="plan.type === 'standard'" class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-400">
                                14 يوم تجربة
                            </span>
                        </div>

                        <div class="text-lg font-black text-white my-2">
                            {{ billingCycle === 'yearly' ? plan.price_yearly : plan.price_monthly }}
                            <span class="text-[10px] font-normal text-slate-400">ج.م / {{ billingCycle === 'yearly' ? 'سنة' : 'شهر' }}</span>
                        </div>

                        <p class="text-[11px] text-slate-400 line-clamp-2 mb-2">
                            {{ plan.description || 'تشمل خدمات الرد الآلي والمحادثات الموحدة وإعادة الاستهداف لكافة القنوات' }}
                        </p>

                        <ul class="text-[10px] text-slate-300 space-y-1">
                            <li class="flex items-center gap-1">
                                <Check class="w-3 h-3 text-emerald-400 shrink-0" />
                                <span>{{ plan.max_businesses ? plan.max_businesses + ' متجر' : 'متاجر غير محدودة' }}</span>
                            </li>
                            <li class="flex items-center gap-1">
                                <Check class="w-3 h-3 text-emerald-400 shrink-0" />
                                <span>صندوق الوارد الموحد (Unified Inbox)</span>
                            </li>
                            <li class="flex items-center gap-1">
                                <Check class="w-3 h-3 text-emerald-400 shrink-0" />
                                <span>حملات إعادة الاستهداف (Retargeting)</span>
                            </li>
                            <li class="flex items-center gap-1 text-slate-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0 ms-0.5 me-1"></span>
                                <span>الإرسال الجماعي: محاسبة حسب الرسالة</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.plan_id" />

                <!-- Payment Notice -->
                <div class="flex items-center gap-2 p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-[11px] text-amber-300">
                    <CreditCard class="w-4 h-4 shrink-0 text-amber-400" />
                    <span>السداد متاح عبر منافذ فوري (Fawry) ومحافظ الهاتف الذكي والبطاقات البنكية.</span>
                </div>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/20 transition disabled:opacity-50"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <UserPlus v-else class="w-4 h-4" />
                <span>
                    {{ (selectedPlan && ((billingCycle === 'yearly' ? selectedPlan.price_yearly : selectedPlan.price_monthly) > 0)) ? 'متابعة السداد عبر فوري وتأكيد الحساب' : 'إنشاء الحساب والبدء بالتجربة المجانية' }}
                </span>
            </button>
        </form>

        <!-- Login Link -->
        <div class="mt-6 pt-5 border-t border-slate-800/80 text-center text-xs text-slate-400">
            <span>لديك حساب بالفعل؟ </span>
            <Link
                :href="route('login')"
                class="font-bold text-blue-400 hover:text-blue-300 transition ms-1"
            >
                تسجيل الدخول
            </Link>
        </div>
    </GuestLayout>
</template>
