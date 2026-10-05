<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    CheckCircle2,
    ArrowRight,
    QrCode,
    Sparkles,
    Send,
    MessageCircle,
    Copy,
    Check
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
        <!-- Page Header -->
        <div class="border-b border-slate-800 bg-slate-900/90 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-3">
                    <Link :href="route('businesses.index')" class="hover:text-blue-400 transition">المتاجر</Link>
                    <span class="text-slate-600">/</span>
                    <span class="text-slate-300">{{ business.name }}</span>
                    <span class="text-slate-600">/</span>
                    <span class="text-blue-400">إعدادات القنوات</span>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                            ربط القنوات والمنصات
                        </h1>
                        <p class="mt-1 text-sm text-slate-400">
                            قم بربط حسابات واتساب، تيليجرام، فيسبوك، وإنستجرام لمتجر <span class="font-bold text-white">{{ business.name }}</span>.
                        </p>
                    </div>

                    <Link
                        :href="route('businesses.index')"
                        class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 transition"
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
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold border border-emerald-500/20">
                                    <MessageCircle class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-white">واتساب (WhatsApp)</h3>
                                    <p class="text-xs text-slate-400">ربط مباشر برمز QR عبر مكتبة Baileys</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="whatsapp?.status === 'connected' ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="whatsapp?.status === 'connected' ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                                <span>{{ whatsapp?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                            </span>
                        </div>

                        <!-- WhatsApp Body Area -->
                        <div class="mt-6 p-6 rounded-xl bg-slate-950 border border-slate-800/80 text-center">
                            <div v-if="whatsapp?.status === 'connected'" class="space-y-3">
                                <div class="inline-flex p-3 rounded-full bg-emerald-950/60 text-emerald-400 border border-emerald-800">
                                    <CheckCircle2 class="w-8 h-8" />
                                </div>
                                <h4 class="font-bold text-white text-base">واتساب متصل ويعمل لايف</h4>
                                <p class="text-xs text-slate-400 font-mono">{{ whatsapp.phone_number || 'الرقم متصل' }}</p>
                            </div>

                            <div v-else class="space-y-4 py-2">
                                <div class="inline-flex p-4 rounded-2xl bg-slate-900 border border-slate-800 text-slate-300">
                                    <QrCode class="w-12 h-12 text-slate-300" />
                                </div>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                                    اضغط لتوليد رمز QR ومسحه من تطبيق واتساب بهاتفك في ثوانٍ دون الحاجة لموافقة ميتا أو رسوم اشتراك.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-slate-800 flex items-center justify-between">
                        <button
                            v-if="whatsapp?.status !== 'connected'"
                            class="cursor-pointer w-full py-3 px-4 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/20"
                        >
                            <QrCode class="w-4 h-4" />
                            <span>توليد كود الـ QR للربط</span>
                        </button>
                        <button
                            v-else
                            @click="disconnect('whatsapp')"
                            class="cursor-pointer py-2 px-3 text-xs font-bold text-rose-400 hover:bg-rose-950/40 rounded-xl transition"
                        >
                            فصل الاتصال
                        </button>
                    </div>
                </div>

                <!-- 2. Telegram Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-sky-500/10 text-sky-400 flex items-center justify-center font-bold border border-sky-500/20">
                                    <Send class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-white">تيليجرام (Telegram)</h3>
                                    <p class="text-xs text-slate-400">ربط فوري وتلقائي عبر Webhook</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="telegram?.status === 'connected' ? 'bg-sky-950/80 text-sky-300 border border-sky-800' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="telegram?.status === 'connected' ? 'bg-sky-400 animate-pulse' : 'bg-slate-500'"></span>
                                <span>{{ telegram?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                            </span>
                        </div>

                        <!-- Telegram Form / Status -->
                        <div class="mt-6">
                            <div v-if="telegram?.status === 'connected'" class="p-5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-500 block">البوت المتصل:</span>
                                    <span class="font-bold text-sm text-sky-400">@{{ telegram.bot_username }}</span>
                                </div>
                                <button
                                    @click="disconnect('telegram')"
                                    class="cursor-pointer text-xs font-bold text-rose-400 hover:underline"
                                >
                                    فصل البوت
                                </button>
                            </div>

                            <form v-else @submit.prevent="connectTelegram" class="space-y-4">
                                <div>
                                    <label for="bot_token" class="block text-xs font-bold text-slate-300">
                                        رمز البوت (Telegram Bot Token)
                                    </label>
                                    <input
                                        id="bot_token"
                                        v-model="telegramForm.bot_token"
                                        type="text"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition"
                                        placeholder="7123456789:AAH7Xxxxx..."
                                        required
                                    />
                                    <InputError class="mt-1.5" :message="telegramForm.errors.bot_token" />
                                    <p class="text-[11px] text-slate-500 mt-1.5">احصل على التوكن من خلال محادثة @BotFather داخل تيليجرام.</p>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="telegramForm.processing"
                                    class="cursor-pointer w-full justify-center bg-sky-600 hover:bg-sky-500 text-white py-3 rounded-xl text-xs font-bold transition shadow-lg shadow-sky-900/20"
                                >
                                    اختبار التوكن وتفعيل البوت
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 3. Facebook Page Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-bold border border-blue-500/20">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-white">صفحة فيسبوك (Facebook)</h3>
                                    <p class="text-xs text-slate-400">استقبال الرسائل والـ Comment-to-DM</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="facebook?.status === 'connected' ? 'bg-blue-950/80 text-blue-300 border border-blue-800' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="facebook?.status === 'connected' ? 'bg-blue-400 animate-pulse' : 'bg-slate-500'"></span>
                                <span>{{ facebook?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                            </span>
                        </div>

                        <!-- Facebook Form / Status -->
                        <div class="mt-6">
                            <div v-if="facebook?.status === 'connected'" class="p-5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-500 block">الصفحة المربوطة:</span>
                                    <span class="font-bold text-sm text-blue-400">{{ facebook.page_name }}</span>
                                    <span class="text-[10px] text-slate-500 block font-mono">ID: {{ facebook.page_id }}</span>
                                </div>
                                <button
                                    @click="disconnect('facebook')"
                                    class="cursor-pointer text-xs font-bold text-rose-400 hover:underline"
                                >
                                    فصل الصفحة
                                </button>
                            </div>

                            <form v-else @submit.prevent="connectFacebook" class="space-y-4">
                                <div>
                                    <label for="page_id" class="block text-xs font-bold text-slate-300">
                                        معرف الصفحة (Page ID)
                                    </label>
                                    <input
                                        id="page_id"
                                        v-model="facebookForm.page_id"
                                        type="text"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="مثلاً: 104523984578..."
                                        required
                                    />
                                </div>
                                <div>
                                    <label for="page_access_token" class="block text-xs font-bold text-slate-300">
                                        Page Access Token / System User Token
                                    </label>
                                    <input
                                        id="page_access_token"
                                        v-model="facebookForm.page_access_token"
                                        type="password"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="EAA..."
                                        required
                                    />
                                    <InputError class="mt-1.5" :message="facebookForm.errors.page_access_token" />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="facebookForm.processing"
                                    class="cursor-pointer w-full justify-center bg-blue-600 hover:bg-blue-500 text-white py-3 rounded-xl text-xs font-bold transition shadow-lg shadow-blue-900/20"
                                >
                                    ربط الصفحة وتفعيل الاستقبال
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 4. Instagram Business Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-pink-500/10 text-pink-400 flex items-center justify-center font-bold border border-pink-500/20">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-white">إنستجرام (Instagram)</h3>
                                    <p class="text-xs text-slate-400">إدارة رسائل الخاص Direct Messages</p>
                                </div>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="instagram?.status === 'connected' ? 'bg-pink-950/80 text-pink-300 border border-pink-800' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="instagram?.status === 'connected' ? 'bg-pink-400 animate-pulse' : 'bg-slate-500'"></span>
                                <span>{{ instagram?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                            </span>
                        </div>

                        <!-- Instagram Form / Status -->
                        <div class="mt-6">
                            <div v-if="instagram?.status === 'connected'" class="p-5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-500 block">الحساب المربوط:</span>
                                    <span class="font-bold text-sm text-pink-400">@{{ instagram.username || instagram.instagram_business_id }}</span>
                                </div>
                                <button
                                    @click="disconnect('instagram')"
                                    class="cursor-pointer text-xs font-bold text-rose-400 hover:underline"
                                >
                                    فصل الحساب
                                </button>
                            </div>

                            <form v-else @submit.prevent="connectInstagram" class="space-y-4">
                                <div>
                                    <label for="instagram_business_id" class="block text-xs font-bold text-slate-300">
                                        Instagram Business Account ID
                                    </label>
                                    <input
                                        id="instagram_business_id"
                                        v-model="instagramForm.instagram_business_id"
                                        type="text"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition"
                                        placeholder="مثلاً: 17841405..."
                                        required
                                    />
                                </div>
                                <div>
                                    <label for="access_token" class="block text-xs font-bold text-slate-300">
                                        Instagram Access Token
                                    </label>
                                    <input
                                        id="access_token"
                                        v-model="instagramForm.access_token"
                                        type="password"
                                        dir="ltr"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition"
                                        placeholder="EAA..."
                                        required
                                    />
                                    <InputError class="mt-1.5" :message="instagramForm.errors.access_token" />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="instagramForm.processing"
                                    class="cursor-pointer w-full justify-center bg-pink-600 hover:bg-pink-500 text-white py-3 rounded-xl text-xs font-bold transition shadow-lg shadow-pink-900/20"
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
