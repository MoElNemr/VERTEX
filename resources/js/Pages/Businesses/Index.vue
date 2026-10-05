<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    businesses: Array,
    canCreateBusiness: Boolean,
    currentSubscription: Object,
});

const isCreating = ref(false);

const form = useForm({
    name: '',
});

const createBusiness = () => {
    form.post(route('businesses.store'), {
        onSuccess: () => {
            form.reset();
            isCreating.value = false;
        },
    });
};

const switchBusiness = (id) => {
    router.post(route('businesses.switch', id));
};
</script>

<template>
    <Head title="إدارة البيزنس | Vertex" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        البيزنسس والمتاجر
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        إدارة بيئات العمل، ربط القنوات الرقمية، والتبديل السريع بين المتاجر
                    </p>
                </div>
                <button
                    v-if="canCreateBusiness && !isCreating"
                    @click="isCreating = true"
                    class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-xl shadow-sm transition duration-150 ease-in-out"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>إضافة بيزنس جديد</span>
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Limit Warning Banner -->
                <div
                    v-if="!canCreateBusiness"
                    class="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-xl text-amber-900 dark:text-amber-200 text-sm flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>لقد وصلت للحد الأقصى لعدد البيزنسس في باقتك الحالية ({{ currentSubscription?.plan?.name }}).</span>
                    </div>
                    <button class="cursor-pointer text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300 hover:underline">
                        ترقية الباقة
                    </button>
                </div>

                <!-- Create Business Form -->
                <div
                    v-if="isCreating"
                    class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                            بيانات البيزنس الجديد
                        </h3>
                        <button
                            type="button"
                            @click="isCreating = false"
                            class="cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="createBusiness" class="space-y-4 max-w-lg">
                        <div>
                            <InputLabel for="name" value="اسم البيزنس / المتجر" class="text-slate-700 dark:text-slate-300" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="مثلاً: متجر الأناقة أو عيادة النور"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div class="flex items-center gap-3 pt-2">
                            <PrimaryButton
                                :disabled="form.processing"
                                class="cursor-pointer bg-blue-600 hover:bg-blue-700 focus:ring-blue-500"
                            >
                                حفظ وإنشاء
                            </PrimaryButton>
                            <button
                                type="button"
                                @click="isCreating = false"
                                class="cursor-pointer px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 transition"
                            >
                                إلغاء
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Businesses Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="b in businesses"
                        :key="b.id"
                        class="bg-white dark:bg-slate-800 rounded-2xl p-6 border transition-all duration-200 flex flex-col justify-between"
                        :class="[
                            $page.props.currentBusiness?.id === b.id
                                ? 'border-blue-500 shadow-md ring-1 ring-blue-500'
                                : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'
                        ]"
                    >
                        <div>
                            <!-- Header & Active Tag -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-lg border border-blue-100 dark:border-blue-900">
                                        {{ b.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 dark:text-slate-100 text-lg">
                                            {{ b.name }}
                                        </h4>
                                        <span class="text-xs text-slate-400">
                                            {{ b.conversations_count }} محادثة نشطة
                                        </span>
                                    </div>
                                </div>
                                <span
                                    v-if="$page.props.currentBusiness?.id === b.id"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    النشط الآن
                                </span>
                            </div>

                            <!-- Channels Status Section -->
                            <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-700/80 space-y-3">
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    حالة القنوات المربوطة
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <!-- WhatsApp -->
                                    <div
                                        class="p-2.5 rounded-xl border flex items-center justify-between"
                                        :class="b.whatsapp_account?.status === 'connected' ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200' : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400'"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2z"/>
                                            </svg>
                                            <span class="font-medium">واتساب</span>
                                        </div>
                                        <span class="text-[10px] font-bold">{{ b.whatsapp_account?.status === 'connected' ? 'متصل' : 'غير مربوط' }}</span>
                                    </div>

                                    <!-- Facebook -->
                                    <div
                                        class="p-2.5 rounded-xl border flex items-center justify-between"
                                        :class="b.facebook_account?.status === 'connected' ? 'bg-blue-50/50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200' : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400'"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                            <span class="font-medium">فيسبوك</span>
                                        </div>
                                        <span class="text-[10px] font-bold">{{ b.facebook_account?.status === 'connected' ? 'متصل' : 'غير مربوط' }}</span>
                                    </div>

                                    <!-- Instagram -->
                                    <div
                                        class="p-2.5 rounded-xl border flex items-center justify-between"
                                        :class="b.instagram_account?.status === 'connected' ? 'bg-pink-50/50 dark:bg-pink-950/20 border-pink-200 dark:border-pink-800 text-pink-800 dark:text-pink-200' : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400'"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                            </svg>
                                            <span class="font-medium">إنستجرام</span>
                                        </div>
                                        <span class="text-[10px] font-bold">{{ b.instagram_account?.status === 'connected' ? 'متصل' : 'غير مربوط' }}</span>
                                    </div>

                                    <!-- Telegram -->
                                    <div
                                        class="p-2.5 rounded-xl border flex items-center justify-between"
                                        :class="b.telegram_account?.status === 'connected' ? 'bg-sky-50/50 dark:bg-sky-950/20 border-sky-200 dark:border-sky-800 text-sky-800 dark:text-sky-200' : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400'"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-sky-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                                            </svg>
                                            <span class="font-medium">تيليجرام</span>
                                        </div>
                                        <span class="text-[10px] font-bold">{{ b.telegram_account?.status === 'connected' ? 'متصل' : 'غير مربوط' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/80 flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">
                                {{ b.team_members_count }} أعضاء فريق
                            </span>
                            <button
                                v-if="$page.props.currentBusiness?.id !== b.id"
                                @click="switchBusiness(b.id)"
                                class="cursor-pointer px-3.5 py-1.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 rounded-lg transition duration-150"
                            >
                                تبديل إلى هذا البيزنس
                            </button>
                            <span v-else class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                البيزنس النشط
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
