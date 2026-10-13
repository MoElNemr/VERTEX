<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Send, Loader2, ArrowRight } from 'lucide-vue-next';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="استعادة كلمة المرور - Vertex" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-extrabold text-white tracking-tight">استعادة كلمة المرور</h2>
            <p class="text-xs text-slate-400 mt-1">
                أدخل بريدك الإلكتروني وسنرسل لك رابطاً آمناً لإعادة تعيين كلمة المرور
            </p>
        </div>

        <div
            v-if="status"
            class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-semibold text-emerald-400 text-center"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
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

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/20 transition disabled:opacity-50"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <Send v-else class="w-4 h-4" />
                <span>إرسال رابط إعادة التعيين</span>
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-800/80 text-center text-xs text-slate-400">
            <Link
                :href="route('login')"
                class="inline-flex items-center gap-1 font-bold text-blue-400 hover:text-blue-300 transition"
            >
                <ArrowRight class="w-3.5 h-3.5" />
                <span>العودة لصفحة تسجيل الدخول</span>
            </Link>
        </div>
    </GuestLayout>
</template>
