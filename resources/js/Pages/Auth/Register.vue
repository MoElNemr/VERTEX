<script setup>
import { ref, computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    User, Mail, Lock, Eye, EyeOff, UserPlus, Loader2, Sparkles, Check,
    CreditCard, ArrowLeft, ArrowRight, ShieldCheck, Zap, AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
});

const currentStep = ref(1);
const billingCycle = ref('monthly'); // 'monthly' or 'yearly'
const showPassword = ref(false);
const showPasswordConfirm = ref(false);

// Local validation errors for Step 1
const step1Errors = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

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
    plan_id: defaultPlanId.value || (props.plans[0]?.id ?? ''),
    billing_cycle: 'monthly',
});

// Ensure plan_id is populated once plans are available
if (!form.plan_id && props.plans.length > 0) {
    form.plan_id = props.plans[0].id;
}

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

const selectedPrice = computed(() => {
    if (!selectedPlan.value) return 0;
    return billingCycle.value === 'yearly'
        ? Number(selectedPlan.value.price_yearly)
        : Number(selectedPlan.value.price_monthly);
});

// Validate Step 1 before allowing progression to Step 2
const validateStep1 = () => {
    let isValid = true;
    step1Errors.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    };

    if (!form.name || form.name.trim() === '') {
        step1Errors.value.name = 'يرجى إدخال الاسم الكامل أو اسم المتجر.';
        isValid = false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!form.email || !emailRegex.test(form.email.trim())) {
        step1Errors.value.email = 'يرجى إدخال بريد إلكتروني صحيح.';
        isValid = false;
    }

    if (!form.password || form.password.length < 8) {
        step1Errors.value.password = 'يجب ألا تقل كلمة المرور عن 8 أحرف.';
        isValid = false;
    }

    if (form.password !== form.password_confirmation) {
        step1Errors.value.password_confirmation = 'كلمة المرور وتأكيدها غير متطابقين.';
        isValid = false;
    }

    return isValid;
};

const goToStep2 = () => {
    if (validateStep1()) {
        currentStep.value = 2;
    }
};

const goToStep1 = () => {
    currentStep.value = 1;
};

const submit = () => {
    // If somehow on step 1, validate first
    if (currentStep.value === 1) {
        if (!validateStep1()) return;
        currentStep.value = 2;
        return;
    }

    // Must have a plan chosen
    if (!form.plan_id && props.plans.length > 0) {
        form.plan_id = props.plans[0].id;
    }

    form.billing_cycle = billingCycle.value;

    form.post(route('register'), {
        onError: (errors) => {
            // If errors belong to step 1 credentials, route user back to Step 1
            if (errors.name || errors.email || errors.password || errors.password_confirmation) {
                currentStep.value = 1;
            }
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="إنشاء حساب واختيار الباقة والدفع - Vertex" />

        <!-- Header -->
        <div class="mb-6 text-center">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 mb-2">
                <Sparkles class="w-3.5 h-3.5 text-blue-400" />
                <span>تسجيل حساب جديد باشتراك مدفوع عبر فوري</span>
            </div>
            <h2 class="text-2xl font-black text-white tracking-tight">إنشاء حساب متجر جديد</h2>
            <p class="text-xs text-slate-400 mt-1">سجل بياناتك واختر باقتك لإتمام الدفع وتفعيل المنصة فوراً</p>
        </div>

        <!-- 2-STEP PROGRESS STEPPER -->
        <div class="mb-6 p-2 rounded-2xl bg-slate-900/80 border border-slate-800 flex items-center justify-between text-xs font-bold">
            <!-- Step 1 Tab -->
            <button
                type="button"
                @click="goToStep1"
                class="flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-xl transition"
                :class="currentStep === 1
                    ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-black'
                    : 'text-slate-400 hover:text-slate-200'"
            >
                <div
                    class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-mono"
                    :class="currentStep > 1 ? 'bg-emerald-500 text-white' : 'bg-slate-800 text-slate-200'"
                >
                    <Check v-if="currentStep > 1" class="w-3 h-3" />
                    <span v-else>1</span>
                </div>
                <span>بيانات الحساب</span>
            </button>

            <div class="w-6 h-0.5 bg-slate-800 mx-1 shrink-0"></div>

            <!-- Step 2 Tab -->
            <button
                type="button"
                @click="goToStep2"
                class="flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-xl transition"
                :class="currentStep === 2
                    ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-black'
                    : 'text-slate-500 hover:text-slate-300'"
            >
                <div
                    class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-mono bg-slate-800 text-slate-200"
                >
                    <span>2</span>
                </div>
                <span>اختيار الباقة والدفع</span>
            </button>
        </div>

        <!-- REGISTRATION FORM -->
        <form @submit.prevent="submit" class="space-y-6">

            <!-- ================= STEP 1: CREDENTIALS ================= -->
            <div v-show="currentStep === 1" class="space-y-4">
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                            <User class="w-4 h-4 text-blue-400" />
                            <span>الخطوة الأولى: بيانات الدخول والمتجر</span>
                        </h3>
                        <span class="text-[10px] text-slate-500 font-mono">1 من 2</span>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-300 mb-1 text-start">
                            الاسم الكامل / اسم المتجر <span class="text-rose-400">*</span>
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
                                class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                :class="step1Errors.name || form.errors.name ? 'border-rose-500' : 'border-slate-800'"
                            />
                        </div>
                        <InputError class="mt-1 text-xs text-rose-400" :message="step1Errors.name || form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-300 mb-1 text-start">
                            البريد الإلكتروني <span class="text-rose-400">*</span>
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
                                class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                :class="step1Errors.email || form.errors.email ? 'border-rose-500' : 'border-slate-800'"
                            />
                        </div>
                        <InputError class="mt-1 text-xs text-rose-400" :message="step1Errors.email || form.errors.email" />
                    </div>

                    <!-- Password Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="password" class="block text-xs font-semibold text-slate-300 mb-1 text-start">
                                كلمة المرور <span class="text-rose-400">*</span>
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
                                    class="w-full ps-10 pe-10 py-2.5 rounded-xl bg-slate-950 border text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    :class="step1Errors.password || form.errors.password ? 'border-rose-500' : 'border-slate-800'"
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
                            <InputError class="mt-1 text-xs text-rose-400" :message="step1Errors.password || form.errors.password" />
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1 text-start">
                                تأكيد كلمة المرور <span class="text-rose-400">*</span>
                            </label>
                            <div class="relative">
                                <Lock class="w-4 h-4 text-slate-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                                <input
                                    id="password_confirmation"
                                    :type="showPasswordConfirm ? 'text' : 'password'"
                                    v-model="form.password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                    class="w-full ps-10 pe-10 py-2.5 rounded-xl bg-slate-950 border text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    :class="step1Errors.password_confirmation || form.errors.password_confirmation ? 'border-rose-500' : 'border-slate-800'"
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirm = !showPasswordConfirm"
                                    class="absolute end-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition"
                                >
                                    <EyeOff v-if="showPasswordConfirm" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <InputError class="mt-1 text-xs text-rose-400" :message="step1Errors.password_confirmation || form.errors.password_confirmation" />
                        </div>
                    </div>
                </div>

                <!-- Next Button to Step 2 -->
                <button
                    type="button"
                    @click="goToStep2"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/20 transition cursor-pointer"
                >
                    <span>التالي: اختيار الباقة وتأكيد الدفع</span>
                    <ArrowLeft class="w-4 h-4" />
                </button>
            </div>

            <!-- ================= STEP 2: PLAN SELECTOR & PAYMENT ================= -->
            <div v-show="currentStep === 2" class="space-y-4">
                <!-- Back Button -->
                <div class="flex items-center justify-between">
                    <button
                        type="button"
                        @click="goToStep1"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition cursor-pointer"
                    >
                        <ArrowRight class="w-4 h-4" />
                        <span>تعديل بيانات الحساب</span>
                    </button>
                    <span class="text-[10px] text-slate-500 font-mono">الخطوة 2 من 2</span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <h3 class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                            <Zap class="w-4 h-4 text-amber-400" />
                            <span>اختر باقة الاشتراك (الدفع إلزامي لتفعيل الحساب)</span>
                        </h3>

                        <!-- Billing Cycle Toggle -->
                        <div class="flex items-center p-0.5 rounded-lg bg-slate-950 border border-slate-800 text-[11px] self-start sm:self-auto">
                            <button
                                type="button"
                                @click="selectBillingCycle('monthly')"
                                class="px-2.5 py-1 rounded-md transition font-medium cursor-pointer"
                                :class="billingCycle === 'monthly' ? 'bg-blue-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                            >
                                شهرياً
                            </button>
                            <button
                                type="button"
                                @click="selectBillingCycle('yearly')"
                                class="px-2.5 py-1 rounded-md transition font-medium flex items-center gap-1 cursor-pointer"
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
                                ? 'bg-blue-950/40 border-blue-500 shadow-md shadow-blue-500/10 ring-1 ring-blue-500'
                                : 'bg-slate-950/50 border-slate-800 hover:border-slate-700'"
                        >
                            <!-- Selected Badge -->
                            <div v-if="form.plan_id === plan.id" class="absolute top-3 end-3 w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center">
                                <Check class="w-3 h-3" />
                            </div>

                            <div class="text-xs font-bold text-white flex items-center gap-1.5 mb-1">
                                <span>{{ plan.name }}</span>
                                <span v-if="plan.type === 'premium'" class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-400">
                                    الأكثر شعبية
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
                                <li class="flex items-center gap-1 text-slate-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0 ms-0.5 me-1"></span>
                                    <span>الإرسال الجماعي وإعادة الاستهداف: دفع حسب الرسالة</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.plan_id" />

                    <!-- Order Summary Box -->
                    <div v-if="selectedPlan" class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">الباقة المحددة:</span>
                            <span class="font-bold text-white">{{ selectedPlan.name }} ({{ billingCycle === 'yearly' ? 'سنوي' : 'شهري' }})</span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-1.5 border-t border-slate-800">
                            <span class="text-slate-300 font-bold">إجمالي المبلغ المستحق للدفع:</span>
                            <span class="text-base font-black text-emerald-400">{{ selectedPrice }} ج.م</span>
                        </div>
                    </div>

                    <!-- Enforced Payment Notice -->
                    <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-[11px] text-amber-300 space-y-1">
                        <div class="flex items-center gap-2 font-bold">
                            <ShieldCheck class="w-4 h-4 shrink-0 text-amber-400" />
                            <span>السداد إلزامي لإتمام التسجيل وتفعيل الحساب عبر فوري (Fawry)</span>
                        </div>
                        <p class="text-[10px] text-amber-300/80 ps-6">
                            بمجرد الضغط على تأكيد التسجيل، سيتم إنشاء رقم مرجعي للدفع عبر فوري فوراً. لا يمكن استخدام ميزات المنصة قبل إتمام السداد.
                        </p>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="form.processing || !form.plan_id"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/20 transition disabled:opacity-50 cursor-pointer"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <CreditCard v-else class="w-4 h-4" />
                    <span>
                        {{ form.processing ? 'جاري إنشاء الحساب وإعداد كود الدفع...' : `تأكيد الحساب والانتقال للسداد عبر فوري (${selectedPrice} ج.م)` }}
                    </span>
                </button>
            </div>
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
