<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Building2,
    Plus,
    CheckCircle2,
    MessageSquare,
    Users,
    ArrowUpRight,
    Sparkles,
    AlertCircle,
    X,
    Send,
    Radio
} from 'lucide-vue-next';

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
    <Head title="My Businesses - Vertex" />

    <AuthenticatedLayout>
        <!-- Top Hero Section -->
        <div class="border-b border-slate-200/80 bg-white/50 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 mb-3">
                            <Sparkles class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                            <span>Omnichannel Workspaces</span>
                        </div>
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                            المتاجر وبيئات العمل
                        </h1>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 max-w-2xl">
                            كل متجر يمثل بيئة عمل مستقلة بقنواته ورسائله وفريقه. اختر المتجر للبدء في الرد الفوري أو أضف متجراً جديداً.
                        </p>
                    </div>

                    <!-- Right Stats / Action -->
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex items-center gap-4 bg-slate-100/80 dark:bg-slate-800/80 px-4 py-2.5 rounded-2xl border border-slate-200/60 dark:border-slate-700/60">
                            <div class="text-right">
                                <span class="block text-xs font-semibold text-slate-400">إجمالي المتاجر</span>
                                <span class="text-lg font-black text-slate-900 dark:text-white">{{ businesses.length }}</span>
                            </div>
                            <div class="w-px h-8 bg-slate-300 dark:bg-slate-700"></div>
                            <div class="text-right">
                                <span class="block text-xs font-semibold text-slate-400">الباقة</span>
                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ currentSubscription?.plan?.name || 'Standard' }}</span>
                            </div>
                        </div>

                        <button
                            v-if="canCreateBusiness && !isCreating"
                            @click="isCreating = true"
                            class="cursor-pointer inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm shadow-sm shadow-blue-500/25 transition duration-150"
                        >
                            <Plus class="w-4 h-4" />
                            <span>متجر جديد</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Warning Alert if Plan Max Reached -->
            <div
                v-if="!canCreateBusiness"
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/20 p-5 border border-amber-200/80 dark:border-amber-800/50 flex items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3.5">
                    <div class="p-2.5 bg-amber-100 dark:bg-amber-900/50 rounded-xl text-amber-700 dark:text-amber-400">
                        <AlertCircle class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">الحد الأقصى للبيزنسس في باقتك الحالية</h4>
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
                            لقد استهلكت جميع المتاجر المتاحة في خطتك. قم بالترقية لإضافة متاجر غير محدودة.
                        </p>
                    </div>
                </div>
                <button class="cursor-pointer whitespace-nowrap px-4 py-2 rounded-xl text-xs font-bold text-amber-900 bg-amber-200/80 hover:bg-amber-300 dark:bg-amber-800/60 dark:text-amber-100 dark:hover:bg-amber-700 transition">
                    ترقية الآن
                </button>
            </div>

            <!-- New Business Form Modal/Card -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="isCreating"
                    class="bg-white dark:bg-slate-800/90 backdrop-blur-sm rounded-3xl p-7 border border-blue-200 dark:border-blue-900/50 shadow-xl shadow-blue-500/5"
                >
                    <div class="flex items-center justify-between pb-5 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                                <Building2 class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">إنشاء متجر جديد</h3>
                                <p class="text-xs text-slate-500">سيتم تفعيل المتجر فوراً لتوصيل القنوات وبدء الاستقبال</p>
                            </div>
                        </div>
                        <button
                            @click="isCreating = false"
                            class="cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="createBusiness" class="mt-6 max-w-xl space-y-4">
                        <div>
                            <InputLabel for="name" value="اسم المتجر أو البيزنس" class="text-slate-700 dark:text-slate-300 font-semibold" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1.5 block w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-900/60 focus:ring-2 focus:ring-blue-500"
                                placeholder="مثال: فاشن بوينت، فرع المعادي، أو توكيلات الشرق"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div class="flex items-center gap-3 pt-3">
                            <PrimaryButton
                                :disabled="form.processing"
                                class="cursor-pointer bg-blue-600 hover:bg-blue-700 rounded-xl px-5 py-2.5 text-sm font-semibold"
                            >
                                حفظ وتفعيل المتجر
                            </PrimaryButton>
                            <button
                                type="button"
                                @click="isCreating = false"
                                class="cursor-pointer px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                إلغاء
                            </button>
                        </div>
                    </form>
                </div>
            </transition>

            <!-- Businesses Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="b in businesses"
                    :key="b.id"
                    class="group relative bg-white dark:bg-slate-800/80 rounded-3xl p-6 border transition-all duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-none flex flex-col justify-between"
                    :class="[
                        $page.props.currentBusiness?.id === b.id
                            ? 'border-blue-500/80 ring-2 ring-blue-500/20 bg-gradient-to-b from-white to-blue-50/20 dark:from-slate-800 dark:to-blue-950/10'
                            : 'border-slate-200/80 dark:border-slate-700/80'
                    ]"
                >
                    <div>
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3.5">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-black text-xl shadow-md shadow-blue-500/20">
                                    {{ b.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                                        {{ b.name }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 font-medium">
                                            <Users class="w-3.5 h-3.5 text-slate-400" />
                                            <span>{{ b.team_members_count }} أعضاء</span>
                                        </span>
                                        <span class="text-slate-300 dark:text-slate-700">•</span>
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 font-medium">
                                            <MessageSquare class="w-3.5 h-3.5 text-slate-400" />
                                            <span>{{ b.conversations_count }} محادثة</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Indicator Badge -->
                            <div v-if="$page.props.currentBusiness?.id === b.id" class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>النشط</span>
                            </div>
                        </div>

                        <!-- Channels Grid -->
                        <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-700/60 space-y-2.5">
                            <div class="flex items-center justify-between text-xs font-bold text-slate-400 tracking-wider">
                                <span>القنوات الرقمية</span>
                                <span class="text-[11px] font-semibold text-slate-500">حساب واحد لكل منصة</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5 pt-1">
                                <!-- WhatsApp -->
                                <div
                                    class="p-3 rounded-2xl border transition flex flex-col justify-between gap-2"
                                    :class="b.whatsapp_account?.status === 'connected' ? 'bg-emerald-50/40 border-emerald-200/80 dark:bg-emerald-950/20 dark:border-emerald-800/60' : 'bg-slate-50/60 border-slate-200/60 dark:bg-slate-900/40 dark:border-slate-800'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">واتساب</span>
                                        <Radio class="w-3.5 h-3.5" :class="b.whatsapp_account?.status === 'connected' ? 'text-emerald-600 dark:text-emerald-400 animate-pulse' : 'text-slate-300 dark:text-slate-600'" />
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-slate-400">الحالة:</span>
                                        <span class="font-bold" :class="b.whatsapp_account?.status === 'connected' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'">
                                            {{ b.whatsapp_account?.status === 'connected' ? 'متصل' : 'غير متصل' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Facebook -->
                                <div
                                    class="p-3 rounded-2xl border transition flex flex-col justify-between gap-2"
                                    :class="b.facebook_account?.status === 'connected' ? 'bg-blue-50/40 border-blue-200/80 dark:bg-blue-950/20 dark:border-blue-800/60' : 'bg-slate-50/60 border-slate-200/60 dark:bg-slate-900/40 dark:border-slate-800'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">فيسبوك</span>
                                        <Radio class="w-3.5 h-3.5" :class="b.facebook_account?.status === 'connected' ? 'text-blue-600 dark:text-blue-400' : 'text-slate-300 dark:text-slate-600'" />
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-slate-400">الحالة:</span>
                                        <span class="font-bold" :class="b.facebook_account?.status === 'connected' ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400'">
                                            {{ b.facebook_account?.status === 'connected' ? 'متصل' : 'غير متصل' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Instagram -->
                                <div
                                    class="p-3 rounded-2xl border transition flex flex-col justify-between gap-2"
                                    :class="b.instagram_account?.status === 'connected' ? 'bg-pink-50/40 border-pink-200/80 dark:bg-pink-950/20 dark:border-pink-800/60' : 'bg-slate-50/60 border-slate-200/60 dark:bg-slate-900/40 dark:border-slate-800'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">إنستجرام</span>
                                        <Radio class="w-3.5 h-3.5" :class="b.instagram_account?.status === 'connected' ? 'text-pink-600 dark:text-pink-400' : 'text-slate-300 dark:text-slate-600'" />
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-slate-400">الحالة:</span>
                                        <span class="font-bold" :class="b.instagram_account?.status === 'connected' ? 'text-pink-600 dark:text-pink-400' : 'text-slate-400'">
                                            {{ b.instagram_account?.status === 'connected' ? 'متصل' : 'غير متصل' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Telegram -->
                                <div
                                    class="p-3 rounded-2xl border transition flex flex-col justify-between gap-2"
                                    :class="b.telegram_account?.status === 'connected' ? 'bg-sky-50/40 border-sky-200/80 dark:bg-sky-950/20 dark:border-sky-800/60' : 'bg-slate-50/60 border-slate-200/60 dark:bg-slate-900/40 dark:border-slate-800'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">تيليجرام</span>
                                        <Radio class="w-3.5 h-3.5" :class="b.telegram_account?.status === 'connected' ? 'text-sky-600 dark:text-sky-400' : 'text-slate-300 dark:text-slate-600'" />
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-slate-400">الحالة:</span>
                                        <span class="font-bold" :class="b.telegram_account?.status === 'connected' ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400'">
                                            {{ b.telegram_account?.status === 'connected' ? 'متصل' : 'غير متصل' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Bottom Switcher Bar -->
                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between gap-2.5">
                        <Link
                            :href="route('channels.index', b.id)"
                            class="cursor-pointer py-2.5 px-3 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 transition flex items-center justify-center gap-1.5 shrink-0"
                            title="إعدادات القنوات"
                        >
                            <span>القنوات</span>
                        </Link>

                        <button
                            v-if="$page.props.currentBusiness?.id !== b.id"
                            @click="switchBusiness(b.id)"
                            class="cursor-pointer flex-1 py-2.5 px-4 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition flex items-center justify-center gap-1.5 shadow-sm shadow-blue-500/20"
                        >
                            <span>تفعيل المتجر</span>
                            <ArrowUpRight class="w-3.5 h-3.5" />
                        </button>
                        <div
                            v-else
                            class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-center gap-1.5"
                        >
                            <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            <span>المتجر النشط</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
