<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ShieldCheck, Users, Headphones, ArrowLeft } from 'lucide-vue-next';

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

const submit = () => {
    form.post(route('team.login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="تسجيل دخول فريق العمل - VERTEX" />

    <div class="min-h-screen flex flex-col justify-center items-center bg-slate-950 text-slate-100 p-4 sm:p-6" dir="rtl">
        <!-- Background Glow -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 right-1/2 translate-x-1/2 w-96 h-96 bg-blue-600/15 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-indigo-600/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-md">
            <!-- Header Brand -->
            <div class="text-center mb-6">
                <Link href="/" class="inline-flex items-center justify-center mb-3">
                    <ApplicationLogo class="h-10 w-auto fill-current text-blue-500" />
                </Link>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-950/80 text-indigo-300 border border-indigo-800/60 mb-2">
                    <ShieldCheck class="w-3.5 h-3.5 text-indigo-400" />
                    <span>بوابة الوكلاء وفريق العمل</span>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight">تسجيل دخول فريق العمل</h1>
                <p class="text-xs text-slate-400 mt-1">
                    أدخل بيانات الدخول المخصصة لك للوصول لمحادثات المتاجر المسندة إليك
                </p>
            </div>

            <!-- Role Pills Info -->
            <div class="grid grid-cols-3 gap-2 mb-6">
                <div class="flex items-center justify-center gap-1.5 p-2 rounded-xl bg-slate-900/80 border border-slate-800 text-[11px] font-medium text-slate-300">
                    <ShieldCheck class="w-3.5 h-3.5 text-purple-400" />
                    <span>مسؤول</span>
                </div>
                <div class="flex items-center justify-center gap-1.5 p-2 rounded-xl bg-slate-900/80 border border-slate-800 text-[11px] font-medium text-slate-300">
                    <Users class="w-3.5 h-3.5 text-amber-400" />
                    <span>مشرف</span>
                </div>
                <div class="flex items-center justify-center gap-1.5 p-2 rounded-xl bg-slate-900/80 border border-slate-800 text-[11px] font-medium text-slate-300">
                    <Headphones class="w-3.5 h-3.5 text-emerald-400" />
                    <span>وكيل دعم</span>
                </div>
            </div>

            <!-- Card Box -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-sm">
                <div v-if="status" class="mb-4 text-xs font-semibold text-emerald-400 bg-emerald-950/40 border border-emerald-800/60 rounded-xl p-3">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="email" value="البريد الإلكتروني للعمل" class="text-slate-300 text-xs font-semibold mb-1" />
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full text-right bg-slate-950/80 border-slate-800 text-slate-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl text-sm"
                            v-model="form.email"
                            required
                            autofocus
                            placeholder="agent@example.com"
                            autocomplete="username"
                        />
                        <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="كلمة المرور" class="text-slate-300 text-xs font-semibold mb-1" />
                        <TextInput
                            id="password"
                            type="password"
                            class="block w-full text-right bg-slate-950/80 border-slate-800 text-slate-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl text-sm"
                            v-model="form.password"
                            required
                            placeholder="••••••••"
                            autocomplete="current-password"
                        />
                        <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <Checkbox name="remember" v-model:checked="form.remember" class="border-slate-700 bg-slate-950 text-blue-600 rounded" />
                            <span class="text-xs text-slate-400 select-none">تذكرني على هذا الجهاز</span>
                        </label>
                    </div>

                    <div class="pt-2">
                        <PrimaryButton
                            class="w-full justify-center py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-600/20 cursor-pointer"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            تسجيل الدخول للمنصة
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Bottom Switch to Owner Login -->
            <div class="mt-6 text-center">
                <Link
                    :href="route('login')"
                    class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-blue-400 transition"
                >
                    <span>أنت مالك المتجر؟ الدخول من بوابة الملاك الرئيسية</span>
                    <ArrowLeft class="w-3.5 h-3.5" />
                </Link>
            </div>
        </div>
    </div>
</template>
