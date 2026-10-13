<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Lock, Eye, EyeOff, KeyRound, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="إعادة تعيين كلمة المرور - Vertex" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-extrabold text-white tracking-tight">إعادة تعيين كلمة المرور</h2>
            <p class="text-xs text-slate-400 mt-1">أدخل كلمة المرور الجديدة لحسابك للمتابعة</p>
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
                        class="w-full ps-10 pe-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                    />
                </div>
                <InputError class="mt-1.5 text-xs text-rose-400" :message="form.errors.email" />
            </div>

            <!-- New Password -->
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 mb-1.5 text-start">
                    كلمة المرور الجديدة
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

            <!-- Confirm New Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 mb-1.5 text-start">
                    تأكيد كلمة المرور الجديدة
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

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/20 transition disabled:opacity-50 mt-2"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <KeyRound v-else class="w-4 h-4" />
                <span>حفظ كلمة المرور الجديدة</span>
            </button>
        </form>
    </GuestLayout>
</template>
