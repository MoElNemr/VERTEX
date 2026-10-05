<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Radio,
    CheckCircle2,
    XCircle,
    ArrowRight,
    QrCode,
    Sparkles,
    Trash2,
    ExternalLink,
    Send,
    ShieldCheck,
    MessageCircle,
    Smartphone
} from 'lucide-vue-next';

const props = defineProps({
    business: Object,
    whatsapp: Object,
    facebook: Object,
    instagram: Object,
    telegram: Object,
});

// Forms for each channel
const telegramForm = useForm({
    bot_token: props.telegram?.bot_token || '',
});

const facebookForm = useForm({
    page_id: props.facebook?.page_id || '',
    page_access_token: '',
});

const instagramForm = useForm({
    instagram_business_id: props.instagram?.instagram_business_id || '',
    access_token: '',
});

const connectTelegram = () => {
    telegramForm.post(route('channels.telegram.connect', props.business.id));
};

const connectFacebook = () => {
    facebookForm.post(route('channels.facebook.connect', props.business.id));
};

const connectInstagram = () => {
    instagramForm.post(route('channels.instagram.connect', props.business.id));
};

const disconnect = (channel) => {
    if (confirm(`هل أنت متأكد من رغبتك في فصل هذه القناة؟`)) {
        useForm({}).delete(route('channels.disconnect', [props.business.id, channel]));
    }
};
</script>

<template>
    <Head :title="`إعدادات القنوات - ${business.name}`" />

    <AuthenticatedLayout>
        <!-- Header -->
        <div class="border-b border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-3" aria-label="Breadcrumb">
                    <Link :href="route('businesses.index')" class="hover:text-blue-600 transition">المتاجر</Link>
                    <span>/</span>
                    <span class="text-slate-700 dark:text-slate-300">{{ business.name }}</span>
                    <span>/</span>
                    <span class="text-blue-600 dark:text-blue-400">إعدادات القنوات</span>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                            ربط القنوات والمنصات
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            قم بتوصيل قنوات الرسائل الرسمية والغير رسمية لاستقبال الرسائل مباشرة في صندوق الوارد الموحد لمتجر <span class="font-bold text-slate-800 dark:text-slate-200">{{ business.name }}</span>.
                        </p>
                    </div>

                    <Link
                        :href="route('businesses.index')"
                        class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 transition"
                    >
                        <span>العودة للمتاجر</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </div>

        <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Channels 2-column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- 1. WhatsApp Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                                    <MessageCircle class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-slate-900 dark:text-white">واتساب (WhatsApp)</h3>
                                    <p class="text-xs text-slate-400">ربط مباشر برمز QR عبر Baileys</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="whatsapp?.status === 'connected' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="whatsapp?.status === 'connected' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                                <span>{{ whatsapp?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                            </span>
                        </div>

                        <!-- WhatsApp Body Area -->
                        <div class="mt-6 p-6 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-700/60 text-center">
                            <div v-if="whatsapp?.status === 'connected'" class="space-y-3">
                                <div class="inline-flex p-3 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600">
                                    <CheckCircle2 class="w-8 h-8" />
                                </div>
                                <h4 class="font-bold text-slate-900 dark:text-white">واتساب متصل ويعمل لايف</h4>
                                <p class="text-xs text-slate-500 font-mono">{{ whatsapp.phone_number || 'رقم متصل' }}</p>
                            </div>

                            <div v-else class="space-y-4 py-2">
                                <div class="inline-flex p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm text-slate-700 dark:text-slate-300">
                                    <QrCode class="w-10 h-10" />
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                                    اضغط لتوليد رمز QR ومسحه من تطبيق واتساب في ثوانٍ دون الحاجة لموافقة ميتا أو رسوم اشتراك.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <button
                            v-if="whatsapp?.status !== 'connected'"
                            class="cursor-pointer w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white transition flex items-center justify-center gap-2 shadow-sm"
                        >
                            <QrCode class="w-4 h-4" />
                            <span>توليد كود الـ QR للربط</span>
                        </button>
                        <button
                            v-else
                            @click="disconnect('whatsapp')"
                            class="cursor-pointer py-2 px-3 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition"
                        >
                            فصل الاتصال
                        </button>
                    </div>
                </div>

                <!-- 2. Telegram Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold">
                                    <Send class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-slate-900 dark:text-white">تيليجرام (Telegram)</h3>
                                    <p class="text-xs text-slate-400">ربط فوري وتلقائي عبر Webhook</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="telegram?.status === 'connected' ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="telegram?.status === 'connected' ? 'bg-sky-500 animate-pulse' : 'bg-slate-400'"></span>
                                <span>{{ telegram?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                            </span>
                        </div>

                        <!-- Telegram Form / Status -->
                        <div class="mt-6">
                            <div v-if="telegram?.status === 'connected'" class="p-5 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-400 block">البوت المتصل:</span>
                                    <span class="font-bold text-sm text-sky-700 dark:text-sky-300">@{{ telegram.bot_username }}</span>
                                </div>
                                <button
                                    @click="disconnect('telegram')"
                                    class="cursor-pointer text-xs font-bold text-rose-600 hover:underline"
                                >
                                    فصل البوت
                                </button>
                            </div>

                            <form v-else @submit.prevent="connectTelegram" class="space-y-4">
                                <div>
                                    <InputLabel for="bot_token" value="رمز البوت (Telegram Bot Token)" class="text-xs font-bold text-slate-700 dark:text-slate-300" />
                                    <input
                                        id="bot_token"
                                        v-model="telegramForm.bot_token"
                                        type="text"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="7123456789:AAH7Xxxxx..."
                                        required
                                    />
                                    <InputError class="mt-1.5" :message="telegramForm.errors.bot_token" />
                                    <p class="text-[11px] text-slate-400 mt-1">احصل على التوكن من خلال محادثة @BotFather داخل تيليجرام.</p>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="telegramForm.processing"
                                    class="cursor-pointer w-full justify-center bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white py-2.5 rounded-xl text-xs font-bold transition shadow-sm"
                                >
                                    اختبار التوكن وتفعيل البوت
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 3. Facebook Page Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-slate-900 dark:text-white">صفحة فيسبوك (Facebook)</h3>
                                    <p class="text-xs text-slate-400">استقبال الرسائل والـ Comment-to-DM</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="facebook?.status === 'connected' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="facebook?.status === 'connected' ? 'bg-blue-500 animate-pulse' : 'bg-slate-400'"></span>
                                <span>{{ facebook?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                            </span>
                        </div>

                        <!-- Facebook Form / Status -->
                        <div class="mt-6">
                            <div v-if="facebook?.status === 'connected'" class="p-5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-400 block">الصفحة المربوطة:</span>
                                    <span class="font-bold text-sm text-blue-700 dark:text-blue-300">{{ facebook.page_name }}</span>
                                    <span class="text-[10px] text-slate-400 block font-mono">ID: {{ facebook.page_id }}</span>
                                </div>
                                <button
                                    @click="disconnect('facebook')"
                                    class="cursor-pointer text-xs font-bold text-rose-600 hover:underline"
                                >
                                    فصل الصفحة
                                </button>
                            </div>

                            <form v-else @submit.prevent="connectFacebook" class="space-y-3.5">
                                <div>
                                    <InputLabel for="page_id" value="معرف الصفحة (Page ID)" class="text-xs font-bold text-slate-700 dark:text-slate-300" />
                                    <input
                                        id="page_id"
                                        v-model="facebookForm.page_id"
                                        type="text"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="مثلاً: 104523984578..."
                                        required
                                    />
                                </div>
                                <div>
                                    <InputLabel for="page_access_token" value="Page Access Token / System User Token" class="text-xs font-bold text-slate-700 dark:text-slate-300" />
                                    <input
                                        id="page_access_token"
                                        v-model="facebookForm.page_access_token"
                                        type="password"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="EAA..."
                                        required
                                    />
                                    <InputError class="mt-1.5" :message="facebookForm.errors.page_access_token" />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="facebookForm.processing"
                                    class="cursor-pointer w-full justify-center bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white py-2.5 rounded-xl text-xs font-bold transition shadow-sm mt-2"
                                >
                                    ربط الصفحة وتفعيل الاستقبال
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 4. Instagram Business Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-pink-500/10 text-pink-600 dark:text-pink-400 flex items-center justify-center font-bold">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-slate-900 dark:text-white">إنستجرام (Instagram)</h3>
                                    <p class="text-xs text-slate-400">إدارة رسائل الخاص Direct Messages</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="instagram?.status === 'connected' ? 'bg-pink-50 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300 border border-pink-200 dark:border-pink-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="instagram?.status === 'connected' ? 'bg-pink-500 animate-pulse' : 'bg-slate-400'"></span>
                                <span>{{ instagram?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                            </span>
                        </div>

                        <!-- Instagram Form / Status -->
                        <div class="mt-6">
                            <div v-if="instagram?.status === 'connected'" class="p-5 rounded-xl bg-pink-50 dark:bg-pink-950/40 border border-pink-200 dark:border-pink-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-400 block">الحساب المربوط:</span>
                                    <span class="font-bold text-sm text-pink-700 dark:text-pink-300">@{{ instagram.username || instagram.instagram_business_id }}</span>
                                </div>
                                <button
                                    @click="disconnect('instagram')"
                                    class="cursor-pointer text-xs font-bold text-rose-600 hover:underline"
                                >
                                    فصل الحساب
                                </button>
                            </div>

                            <form v-else @submit.prevent="connectInstagram" class="space-y-3.5">
                                <div>
                                    <InputLabel for="instagram_business_id" value="Instagram Business Account ID" class="text-xs font-bold text-slate-700 dark:text-slate-300" />
                                    <input
                                        id="instagram_business_id"
                                        v-model="instagramForm.instagram_business_id"
                                        type="text"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="مثلاً: 17841405..."
                                        required
                                    />
                                </div>
                                <div>
                                    <InputLabel for="access_token" value="Instagram Access Token" class="text-xs font-bold text-slate-700 dark:text-slate-300" />
                                    <input
                                        id="access_token"
                                        v-model="instagramForm.access_token"
                                        type="password"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="EAA..."
                                        required
                                    />
                                    <InputError class="mt-1.5" :message="instagramForm.errors.access_token" />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="instagramForm.processing"
                                    class="cursor-pointer w-full justify-center bg-pink-600 hover:bg-pink-700 active:bg-pink-800 text-white py-2.5 rounded-xl text-xs font-bold transition shadow-sm mt-2"
                                >
                                    ربط إنستجرام وتفعيل الاستقبال
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
