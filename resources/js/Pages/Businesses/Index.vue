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
    <Head title="المتاجر وبيئات العمل - VERTEX" />

    <AuthenticatedLayout>
        <!-- Top Hero Section -->
        <div class="border-b border-slate-800 bg-slate-900/60 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-950/60 text-blue-300 border border-blue-800/60 mb-2.5">
                            <Sparkles class="w-3.5 h-3.5 text-blue-400" />
                            <span>مساحات العمل الموحدة</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                            المتاجر وبيئات العمل
                        </h1>
                        <p class="mt-1 text-xs sm:text-sm text-slate-400 max-w-2xl leading-relaxed">
                            كل متجر يمثل بيئة عمل مستقلة بقنواته ورسائله وفريقه. اختر المتجر للبدء في الرد الفوري أو أضف متجراً جديداً.
                        </p>
                    </div>

                    <!-- Right Stats / Action -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-3.5 bg-slate-850 px-3.5 py-2 rounded-xl border border-slate-750">
                            <div class="text-right">
                                <span class="block text-[10px] font-semibold text-slate-400">إجمالي المتاجر</span>
                                <span class="text-base font-black text-white">{{ businesses.length }}</span>
                            </div>
                            <div class="w-px h-6 bg-slate-700"></div>
                            <div class="text-right">
                                <span class="block text-[10px] font-semibold text-slate-400">الباقة</span>
                                <span class="text-xs font-bold text-blue-400">{{ currentSubscription?.plan?.name || 'الأساسية' }}</span>
                            </div>
                        </div>

                        <button
                            v-if="canCreateBusiness && !isCreating"
                            @click="isCreating = true"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-5 sm:py-3 rounded-xl bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-semibold text-xs sm:text-sm shadow-sm transition w-full sm:w-auto"
                        >
                            <Plus class="w-4 h-4" />
                            <span>متجر جديد</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-6 sm:py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
            <!-- Warning Alert if Plan Max Reached -->
            <div
                v-if="!canCreateBusiness"
                class="rounded-2xl bg-amber-950/30 p-4 sm:p-5 border border-amber-800/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
            >
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="p-2.5 bg-amber-900/50 rounded-xl text-amber-400 shrink-0">
                        <AlertCircle class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-amber-200">الحد الأقصى للمتاجر في باقتك الحالية</h4>
                        <p class="text-xs text-amber-400/90 mt-0.5">
                            لقد استهلكت جميع المتاجر المتاحة في خطتك. قم بالترقية لإضافة متاجر جديدة.
                        </p>
                    </div>
                </div>
                <button class="cursor-pointer whitespace-nowrap px-4 py-2 rounded-xl text-xs font-bold text-amber-100 bg-amber-800/60 hover:bg-amber-700 transition w-full sm:w-auto text-center">
                    ترقية الخطة
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
                    class="bg-slate-900 rounded-2xl sm:rounded-3xl p-5 sm:p-7 border border-blue-900/60 shadow-xl"
                >
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-blue-950/50 text-blue-400">
                                <Building2 class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">إنشاء متجر جديد</h3>
                                <p class="text-xs text-slate-400">سيتم تفعيل المتجر فوراً لتوصيل القنوات وبدء الاستقبال</p>
                            </div>
                        </div>
                        <button
                            @click="isCreating = false"
                            class="cursor-pointer text-slate-400 hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-800"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="createBusiness" class="mt-5 max-w-xl space-y-4">
                        <div>
                            <InputLabel for="name" value="اسم المتجر أو العلامة التجارية" class="text-slate-300 text-xs font-bold" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1.5 block w-full !bg-slate-950 border-slate-800 !text-white text-xs rounded-xl py-2.5"
                                v-model="form.name"
                                placeholder="مثال: متجر النخبة، Vertex Shoes..."
                                required
                                autofocus
                            />
                            <InputError class="mt-1.5" :message="form.errors.name" />
                        </div>
                        <div class="flex items-center gap-3 pt-2">
                            <PrimaryButton
                                :disabled="form.processing"
                                class="cursor-pointer bg-blue-600 hover:bg-blue-500 rounded-xl px-5 py-2.5 text-xs font-bold"
                            >
                                حفظ وتفعيل المتجر
                            </PrimaryButton>
                            <button
                                type="button"
                                @click="isCreating = false"
                                class="cursor-pointer px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-slate-200"
                            >
                                إلغاء
                            </button>
                        </div>
                    </form>
                </div>
            </transition>

            <!-- Businesses Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div
                    v-for="b in businesses"
                    :key="b.id"
                    class="group relative bg-slate-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 border transition-all duration-200 flex flex-col justify-between"
                    :class="[
                        $page.props.currentBusiness?.id === b.id
                            ? 'border-blue-500/80 ring-2 ring-blue-500/20 bg-gradient-to-b from-slate-900 to-blue-950/20'
                            : 'border-slate-800 hover:border-slate-700'
                    ]"
                >
                    <div>
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-blue-500/20 shrink-0">
                                    {{ b.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-base text-white group-hover:text-blue-400 transition truncate">
                                        {{ b.name }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-400 font-medium">
                                            <Users class="w-3.5 h-3.5 text-slate-400" />
                                            <span>{{ b.team_members_count }} أعضاء</span>
                                        </span>
                                        <span class="text-slate-600">•</span>
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-400 font-medium">
                                            <MessageSquare class="w-3.5 h-3.5 text-slate-400" />
                                            <span>{{ b.conversations_count }} محادثة</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Indicator Badge -->
                            <div v-if="$page.props.currentBusiness?.id === b.id" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-950/60 text-emerald-300 border border-emerald-800 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>النشط</span>
                            </div>
                        </div>

                        <!-- Channels Grid -->
                        <div class="mt-5 pt-4 border-t border-slate-800/80 space-y-2">
                            <div class="flex items-center justify-between text-xs font-bold text-slate-400 tracking-wider">
                                <span>القنوات الرقمية</span>
                                <span class="text-[11px] font-semibold text-slate-500">حساب واحد لكل منصة</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <!-- WhatsApp -->
                                <div
                                    class="p-2.5 rounded-xl border transition flex flex-col justify-between gap-1.5"
                                    :class="b.whatsapp_account?.status === 'connected' ? 'bg-emerald-950/20 border-emerald-800/60' : 'bg-slate-950/50 border-slate-800'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-300">واتساب</span>
                                        <Radio class="w-3 h-3" :class="b.whatsapp_account?.status === 'connected' ? 'text-emerald-400 animate-pulse' : 'text-slate-600'" />
                                    </div>
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="text-slate-500">الحالة:</span>
                                        <span class="font-bold" :class="b.whatsapp_account?.status === 'connected' ? 'text-emerald-400' : 'text-slate-500'">
                                            {{ b.whatsapp_account?.status === 'connected' ? 'متصل' : 'غير متصل' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Facebook -->
                                <div
                                    class="p-2.5 rounded-xl border transition flex flex-col justify-between gap-1.5"
                                    :class="b.facebook_account?.status === 'connected' ? 'bg-blue-950/20 border-blue-800/60' : 'bg-slate-950/50 border-slate-800'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-300">فيسبوك</span>
                                        <Radio class="w-3 h-3" :class="b.facebook_account?.status === 'connected' ? 'text-blue-400' : 'text-slate-600'" />
                                    </div>
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="text-slate-500">الحالة:</span>
                                        <span class="font-bold" :class="b.facebook_account?.status === 'connected' ? 'text-blue-400' : 'text-slate-500'">
                                            {{ b.facebook_account?.status === 'connected' ? 'متصل' : 'غير متصل' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Instagram -->
                                <div
                                    class="p-2.5 rounded-xl border transition flex flex-col justify-between gap-1.5"
                                    :class="b.instagram_account?.status === 'connected' ? 'bg-pink-950/20 border-pink-800/60' : 'bg-slate-950/50 border-slate-800'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-300">إنستجرام</span>
                                        <Radio class="w-3 h-3" :class="b.instagram_account?.status === 'connected' ? 'text-pink-400' : 'text-slate-600'" />
                                    </div>
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="text-slate-500">الحالة:</span>
                                        <span class="font-bold" :class="b.instagram_account?.status === 'connected' ? 'text-pink-400' : 'text-slate-500'">
                                            {{ b.instagram_account?.status === 'connected' ? 'متصل' : 'غير متصل' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Telegram -->
                                <div
                                    class="p-2.5 rounded-xl border transition flex flex-col justify-between gap-1.5"
                                    :class="b.telegram_account?.status === 'connected' ? 'bg-sky-950/20 border-sky-800/60' : 'bg-slate-950/50 border-slate-800'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-300">تيليجرام</span>
                                        <Radio class="w-3 h-3" :class="b.telegram_account?.status === 'connected' ? 'text-sky-400' : 'text-slate-600'" />
                                    </div>
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="text-slate-500">الحالة:</span>
                                        <span class="font-bold" :class="b.telegram_account?.status === 'connected' ? 'text-sky-400' : 'text-slate-500'">
                                            {{ b.telegram_account?.status === 'connected' ? 'متصل' : 'غير متصل' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Bottom Actions -->
                    <div class="mt-5 pt-3.5 border-t border-slate-800/80 flex items-center justify-between gap-2">
                        <Link
                            :href="route('channels.index', b.id)"
                            class="cursor-pointer py-2 px-3 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700/80 transition flex items-center justify-center gap-1.5 shrink-0"
                        >
                            <span>القنوات</span>
                        </Link>

                        <button
                            v-if="$page.props.currentBusiness?.id !== b.id"
                            @click="switchBusiness(b.id)"
                            class="cursor-pointer flex-1 py-2 px-3.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white transition flex items-center justify-center gap-1 shadow-sm"
                        >
                            <span>تفعيل المتجر</span>
                            <ArrowUpRight class="w-3.5 h-3.5" />
                        </button>
                        <div
                            v-else
                            class="flex-1 py-2 px-3.5 rounded-xl text-xs font-bold bg-emerald-950/40 text-emerald-300 border border-emerald-800/60 flex items-center justify-center gap-1"
                        >
                            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400" />
                            <span>المتجر النشط</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
