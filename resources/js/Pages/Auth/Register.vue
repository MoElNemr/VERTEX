<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { User, Mail, Lock, Eye, EyeOff, UserPlus, Loader2, Sparkles } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="إنشاء حساب جديد - Vertex" />

        <!-- Header -->
        <div class="mb-6 text-center">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 mb-2">
                <Sparkles class="w-3.5 h-3.5 text-blue-400" />
                <span>تجربة مجانية 14 يوماً</span>
            </div>
            <h2 class="text-xl font-extrabold text-white tracking-tight">إنشاء حساب متجر جديد</h2>
            <p class="text-xs text-slate-400 mt-1">ابدأ أتمتة مبيعاتك وربط قنوات التواصل الاجتماعي فوراً</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Full Name -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-300 mb-1.5 text-start">
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
                <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.name" />
            </div>

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
                        autocomplete="username"
                        placeholder="name@store.com"
                        class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                    />
                </div>
                <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 mb-1.5 text-start">
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
                <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.password" />
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1.5 text-start">
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
                <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.password_confirmation" />
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/20 transition disabled:opacity-50 mt-2"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <UserPlus v-else class="w-4 h-4" />
                <span>إنشاء الحساب والبدء مجاناً</span>
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
