<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Lock, Eye, EyeOff, LogIn, Store, Users, Loader2 } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
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
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="تسجيل الدخول - Vertex" />

        <!-- Header -->
        <div class="mb-6 text-center">
            <h2 class="text-xl font-extrabold text-white tracking-tight">تسجيل دخول صاحب المتجر</h2>
            <p class="text-xs text-slate-400 mt-1">مرحباً بك مجدداً! أدخل بياناتك للوصول إلى لوحة المتاجر</p>
        </div>

        <!-- Role Toggle Tabs -->
        <div class="grid grid-cols-2 p-1 rounded-xl bg-slate-950/80 border border-slate-800 mb-6 text-xs font-semibold">
            <button
                type="button"
                class="flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-blue-600 text-white shadow-sm transition cursor-default"
            >
                <Store class="w-3.5 h-3.5" />
                <span>صاحب المتجر</span>
            </button>
            <Link
                :href="route('team.login')"
                class="flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-slate-400 hover:text-white transition"
            >
                <Users class="w-3.5 h-3.5" />
                <span>عضو الفريق</span>
            </Link>
        </div>

        <div v-if="status" class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-semibold text-emerald-400 text-center">
            {{ status }}
        </div>

        <!-- Facebook Social Login -->
        <div class="mb-4">
            <a
                :href="route('auth.facebook.redirect')"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-[#1877F2]/10 hover:bg-[#1877F2]/20 border border-[#1877F2]/30 text-[#1877F2] hover:text-[#388af6] font-bold text-xs transition"
            >
                <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span>المتابعة باستخدام حساب فيسبوك</span>
            </a>
            <div class="relative my-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-800"></div>
                </div>
                <div class="relative flex justify-center text-[10px] uppercase">
                    <span class="bg-slate-900 px-2 text-slate-500 font-semibold">أو بالبريد الإلكتروني</span>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5 text-start">
                    البريد الإلكتروني
                </label>
                <div class="relative">
                    <Mail class="w-4 h-4 text-slate-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="name@company.com"
                        class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                    />
                </div>
                <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold text-slate-300">
                        كلمة المرور
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[11px] font-semibold text-blue-400 hover:text-blue-300 transition"
                    >
                        نسيت كلمة المرور؟
                    </Link>
                </div>
                <div class="relative">
                    <Lock class="w-4 h-4 text-slate-500 absolute start-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
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
                <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.password" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded bg-slate-950 border-slate-800 text-blue-600 focus:ring-0" />
                    <span class="text-slate-400">تذكر تسجيل دخولي</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/20 transition disabled:opacity-50"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <LogIn v-else class="w-4 h-4" />
                <span>تسجيل الدخول</span>
            </button>
        </form>

        <!-- Register Link -->
        <div class="mt-6 pt-5 border-t border-slate-800/80 text-center text-xs text-slate-400">
            <span>ليس لديك حساب بعد؟ </span>
            <Link
                :href="route('register')"
                class="font-bold text-blue-400 hover:text-blue-300 transition ms-1"
            >
                إنشاء حساب مجاني الآن
            </Link>
        </div>
    </GuestLayout>
</template>
