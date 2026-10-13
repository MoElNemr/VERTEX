<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Lock, Eye, EyeOff, LogIn, Store, Users, Loader2, ShieldCheck, Headphones } from 'lucide-vue-next';

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
    form.post(route('team.login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="تسجيل دخول فريق العمل - Vertex" />

        <!-- Header -->
        <div class="mb-6 text-center">
            <h2 class="text-xl font-extrabold text-white tracking-tight">تسجيل دخول فريق العمل</h2>
            <p class="text-xs text-slate-400 mt-1">مرحباً بك! أدخل بياناتك للوصول إلى محادثات ومهام المتاجر المسندة إليك</p>
        </div>

        <!-- Role Toggle Tabs (Identical to Login.vue, Team Member is Active) -->
        <div class="grid grid-cols-2 p-1 rounded-xl bg-slate-950/80 border border-slate-800 mb-6 text-xs font-semibold">
            <Link
                :href="route('login')"
                class="flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-slate-400 hover:text-white transition"
            >
                <Store class="w-3.5 h-3.5" />
                <span>صاحب المتجر</span>
            </Link>
            <button
                type="button"
                class="flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-blue-600 text-white shadow-sm transition cursor-default"
            >
                <Users class="w-3.5 h-3.5" />
                <span>عضو الفريق</span>
            </button>
        </div>

        <!-- Role Badges inside the card -->
        <div class="grid grid-cols-3 gap-2 mb-5">
            <div class="flex items-center justify-center gap-1 p-1.5 rounded-lg bg-slate-950/60 border border-slate-800/80 text-[10px] font-medium text-slate-300">
                <ShieldCheck class="w-3 h-3 text-purple-400 shrink-0" />
                <span>مسؤول</span>
            </div>
            <div class="flex items-center justify-center gap-1 p-1.5 rounded-lg bg-slate-950/60 border border-slate-800/80 text-[10px] font-medium text-slate-300">
                <Users class="w-3 h-3 text-amber-400 shrink-0" />
                <span>مشرف</span>
            </div>
            <div class="flex items-center justify-center gap-1 p-1.5 rounded-lg bg-slate-950/60 border border-slate-800/80 text-[10px] font-medium text-slate-300">
                <Headphones class="w-3 h-3 text-emerald-400 shrink-0" />
                <span>وكيل دعم</span>
            </div>
        </div>

        <div v-if="status" class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-semibold text-emerald-400 text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5 text-start">
                    البريد الإلكتروني للعمل
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
                        placeholder="agent@company.com"
                        class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition text-start"
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
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full ps-10 pe-10 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition text-start"
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
                    <span class="text-slate-400">تذكرني على هذا الجهاز</span>
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
                <span>تسجيل الدخول للمنصة</span>
            </button>
        </form>

        <!-- Switch to Owner Login -->
        <div class="mt-6 pt-5 border-t border-slate-800/80 text-center text-xs text-slate-400">
            <span>أنت مالك المتجر؟ </span>
            <Link
                :href="route('login')"
                class="font-bold text-blue-400 hover:text-blue-300 transition ms-1"
            >
                تسجيل الدخول كمالك متجر
            </Link>
        </div>
    </GuestLayout>
</template>
