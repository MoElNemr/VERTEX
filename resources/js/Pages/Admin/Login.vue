<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Lock, Eye, EyeOff, ShieldCheck, AlertTriangle, Loader2, Crown } from 'lucide-vue-next';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('admin.login.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="تسجيل الدخول - مركز قيادة النظام (Super Admin)" />

    <div class="min-h-screen flex flex-col justify-center items-center bg-slate-950 text-slate-100 relative overflow-hidden p-4 sm:p-6 selection:bg-purple-600 selection:text-white font-sans" dir="rtl">

        <!-- Subtle Background Glow Effects & Grid Pattern -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b15_1px,transparent_1px),linear-gradient(to_bottom,#1e293b15_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none"></div>
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-96 h-96 bg-purple-600/15 rounded-full blur-[130px] pointer-events-none"></div>
        <div class="absolute -bottom-32 right-10 w-96 h-96 bg-blue-600/10 rounded-full blur-[140px] pointer-events-none"></div>

        <!-- Master Control Badge -->
        <div class="mb-5 text-center z-10">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-mono font-bold bg-purple-500/10 text-purple-300 border border-purple-500/30 mb-3 shadow-lg shadow-purple-500/5">
                <Crown class="w-4 h-4 text-amber-400" />
                <span>VERTEX ROOT CONTROL</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">مركز القيادة والتحكم الأعلى</h1>
            <p class="text-xs text-slate-400 mt-1">بوابة الدخول الخاصة بمالك المنصة وإدارة خوادم النظام</p>
        </div>

        <!-- Main Card Container -->
        <div class="w-full max-w-md rounded-2xl bg-slate-900/95 border border-slate-800 shadow-2xl shadow-black/90 backdrop-blur-xl p-6 sm:p-8 z-10 relative overflow-hidden">
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-purple-500/60 to-transparent"></div>

            <!-- Status Alert -->
            <div v-if="status" class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-semibold text-emerald-400 text-center">
                {{ status }}
            </div>

            <!-- Security Notice -->
            <div class="mb-5 p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-start gap-2.5 text-xs text-amber-300/90 leading-relaxed">
                <AlertTriangle class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" />
                <span>منطقة سيادية: محاولات الدخول غير المصرح بها مراقبة ومسجلة تلقائياً.</span>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5 text-start">
                        البريد الإلكتروني للإدارة
                    </label>
                    <div class="relative">
                        <Mail class="w-4 h-4 text-slate-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            placeholder="admin@vertex.app"
                            class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition text-start"
                        />
                    </div>
                    <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.email" />
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 mb-1.5 text-start">
                        كلمة مرور المالك
                    </label>
                    <div class="relative">
                        <Lock class="w-4 h-4 text-slate-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••••••"
                            class="w-full ps-10 pe-10 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition text-start"
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
                    <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.password" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <Checkbox name="remember" v-model:checked="form.remember" class="rounded bg-slate-950 border-slate-800 text-purple-600 focus:ring-0" />
                        <span class="text-slate-400">حفظ الجلسة المشفرة</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-600/25 transition disabled:opacity-50 mt-2"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <ShieldCheck v-else class="w-4 h-4 text-purple-200" />
                    <span>التحقق والدخول لمركز التحكم</span>
                </button>
            </form>
        </div>

        <!-- Footer Notice -->
        <div class="mt-6 text-center text-slate-600 text-[11px] font-mono z-10">
            <span>Vertex Marketing Cloud • Root Command Console</span>
        </div>
    </div>
</template>
