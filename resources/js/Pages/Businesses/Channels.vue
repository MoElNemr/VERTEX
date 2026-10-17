<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, onUnmounted } from 'vue';
import axios from 'axios';
import {
    CheckCircle2,
    ArrowRight,
    QrCode,
    Send,
    MessageCircle,
    Loader2,
    RefreshCw,
    Smartphone,
    Copy,
    Check,
    Sparkles,
    Bot,
    Sliders,
    Clock,
    Save,
    Layers,
    ShieldCheck,
    HelpCircle,
    SlidersHorizontal,
    Radio,
    Zap,
    Tag,
    Key,
    Eye,
    EyeOff,
    ExternalLink,
} from 'lucide-vue-next';

import ChannelGuideModal from '@/Components/ChannelGuideModal.vue';

const props = defineProps({
    business: Object,
    whatsapp: Object,
    facebook: Object,
    instagram: Object,
    telegram: Object,
    aiSetting: Object,
    sharedApiKey: String,
    initialTab: {
        type: String,
        default: 'channels',
    },
});

const activeTab = ref(props.initialTab || 'channels');
const showApiKey = ref(false);
const showGuideModal = ref(false);
const activeGuideChannel = ref('facebook');

const openGuideModal = (channel) => {
    activeGuideChannel.value = channel;
    showGuideModal.value = true;
};

// AI Settings Form (Direct to GROQ)
const aiForm = useForm({
    ai_enabled: Boolean(props.aiSetting?.ai_enabled ?? props.aiSetting?.is_enabled),
    provider: 'groq',
    ai_model: props.aiSetting?.ai_model || props.aiSetting?.model || 'llama-3.3-70b-versatile',
    api_key: props.aiSetting?.api_key || '',
    apply_to_all_stores: false,
    clear_api_key: false,
    ai_personality: props.aiSetting?.ai_personality || '',
    ai_instructions: props.aiSetting?.ai_instructions || props.aiSetting?.system_prompt || '',
    working_hours_only: Boolean(props.aiSetting?.working_hours_only),
    work_start_hour: props.aiSetting?.work_start_hour ?? 9,
    work_end_hour: props.aiSetting?.work_end_hour ?? 18,
    auto_reply_platforms: Array.isArray(props.aiSetting?.auto_reply_platforms)
        ? props.aiSetting.auto_reply_platforms
        : ['whatsapp', 'facebook', 'instagram', 'telegram'],
    reply_delay_seconds: props.aiSetting?.reply_delay_seconds ?? 2,
});

const useSharedKey = () => {
    if (props.sharedApiKey) {
        aiForm.api_key = props.sharedApiKey;
    }
};

const saveAiSettings = () => {
    aiForm.post(route('ai-settings.update', props.business.id), {
        preserveScroll: true,
    });
};

const toggleAiPlatform = (platform) => {
    const list = [...aiForm.auto_reply_platforms];
    const index = list.indexOf(platform);
    if (index > -1) {
        list.splice(index, 1);
    } else {
        list.push(platform);
    }
    aiForm.auto_reply_platforms = list;
};

const applyPreset = (type) => {
    if (type === 'shoes') {
        aiForm.ai_personality = `أنت ممثل مبيعات واستشارات خبير لمتجر الأحذية (${props.business.name})، تتحدث بلباقة وترحب بالعميل دائمًا.`;
        aiForm.ai_instructions = `تعليمات وقواعد متجر الأحذية (${props.business.name}):
1. المقاسات: نستخدم جدول المقاسات الأوروبية القياسية (EU 37 إلى 46).
2. الشحن: التوصيل لكافة المحافظات خلال 24 - 48 ساعة مع ميزة معاينة المقاس قبل الدفع.
3. الاستبدال: متاح خلال 14 يومًا بشرط عدم استخدام الحذاء خارج المنزل ووجود العلبة الأصلية.
4. النصيحة: انصح العميل باختيار مقاس أكبر بنصف درجة إذا كان الحذاء رياضي، أو نفس مقاسه المعتاد في الأحذية الكلاسيك.
5. الدفع: متاح كاش عند الاستلام ومحافظ إلكترونية.`;
    } else if (type === 'clothes') {
        aiForm.ai_personality = `أنت مستشار موضة وممثل مبيعات لمتجر الملابس والأزياء (${props.business.name})، أسلوبك أنيق ومساعد للعميل في اختيار مقاسه وألوانه.`;
        aiForm.ai_instructions = `تعليمات وقواعد متجر الملابس (${props.business.name}):
1. التشكيلة: خامات قطنية 100% عالية الجودة مصممة للاستخدام اليومي والمناسبات.
2. المقاسات: نوفر من S إلى 3XL. اطلب من العميل وزنه وطوله التقريبي لتحديد المقاس المناسب.
3. الاستبدال والاسترجاع: متاح خلال 14 يوماً مع الحفاظ على كارت الصنف (التيكت) سليمًا.
4. كود الخصم: إذا كان العميل يشتري قطعتين أو أكثر، اعرض عليه كود (FASHION10) للحصول على خصم 10%.
5. الشحن: شحن فوري والتوصيل خلال يومين عمل.`;
    } else if (type === 'electronics') {
        aiForm.ai_personality = `أنت خبير دعم تقني ومبيعات في متجر (${props.business.name})، إجاباتك محددة، دقيقة، ومبنية على المواصفات الفنية.`;
        aiForm.ai_instructions = `تعليمات متجر الإلكترونيات (${props.business.name}):
1. الضمان: جميع المنتجات جديدة وأصلية 100% ومشمولة بضمان رسمي لمدة عامين.
2. الشحن: شحن مؤمّن ضد الكسر والتلف ويتم التوصيل في علب محكمة.
3. الإرجاع: الاسترجاع خلال 14 يوماً إذا كان الجهاز في حالته الأصلية وتغليف المصنع لم يفتح.
4. الدعم الفني: لأي استفسار تركيبي أو إعداد، اشرح الخطوات البسيطة ووجه العميل لخدمة الدعم إذا لزم الأمر.`;
    } else if (type === 'general') {
        aiForm.ai_personality = `أنت ممثل خدمة عملاء محترف لمتجر (${props.business.name})، تجيب بود وترحاب وإيجاز مفيد.`;
        aiForm.ai_instructions = `قواعد وإرشادات عامة لمتجر (${props.business.name}):
1. الترحيب بالعميل والإجابة على استفسارات الأسعار والمنتجات بوضوح.
2. الشحن والتوصيل خلال 2 إلى 3 أيام عمل.
3. الاستبدال متاح خلال 14 يومًا مع الفاتورة.
4. عند مواجهة استفسار معقد، اعلم العميل أن أحد مسؤولي الفريق سيتابعه على الفور.`;
    }
};

// Forms
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

// Comment to DM Forms
const fbCommentForm = useForm({
    platform: 'facebook',
    comment_reply_enabled: Boolean(props.facebook?.comment_reply_enabled),
    comment_reply_text: props.facebook?.comment_reply_text || 'تم الرد على الخاص بنجاح! راجع رسائلك 📩',
    dm_message_text: props.facebook?.dm_message_text || 'أهلاً بك {name}! بخصوص استفسارك على المنشور، تفضل تفاصيل العرض...',
    comment_keywords: Array.isArray(props.facebook?.comment_keywords) ? props.facebook.comment_keywords.join(', ') : (props.facebook?.comment_keywords || ''),
});

const igCommentForm = useForm({
    platform: 'instagram',
    comment_reply_enabled: Boolean(props.instagram?.comment_reply_enabled),
    comment_reply_text: props.instagram?.comment_reply_text || 'تم الرد في الخاص بنجاح! راجع الدايركت 📩',
    dm_message_text: props.instagram?.dm_message_text || 'مرحباً {name}! تفضل تفاصيل المنتج والعرض المتاح حالياً...',
    comment_keywords: Array.isArray(props.instagram?.comment_keywords) ? props.instagram.comment_keywords.join(', ') : (props.instagram?.comment_keywords || ''),
});

const saveFbCommentSettings = () => {
    fbCommentForm.post(route('channels.comment-settings.update', props.business.id), {
        preserveScroll: true,
    });
};

const saveIgCommentSettings = () => {
    igCommentForm.post(route('channels.comment-settings.update', props.business.id), {
        preserveScroll: true,
    });
};

const connectMode = ref('phone'); // default to phone pairing code
const isGeneratingQr = ref(false);
const qrCodeData = ref(props.whatsapp?.qr_code || null);
const qrErrorMessage = ref('');

// Phone pairing code state
const phoneNumber = ref('');
const pairingCode = ref('');
const isRequestingPairingCode = ref(false);
const pairingError = ref('');
const codeCopied = ref(false);

const computedQrImage = computed(() => {
    if (!qrCodeData.value) return null;
    if (qrCodeData.value.startsWith('data:image')) {
        return qrCodeData.value;
    }
    return `https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=8&data=${encodeURIComponent(qrCodeData.value)}`;
});

let autoRefreshTimer = null;

const startAutoRefresh = () => {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    autoRefreshTimer = setInterval(async () => {
        if (props.whatsapp?.status === 'connected' || connectMode.value !== 'qr' || !qrCodeData.value) {
            return;
        }
        try {
            const res = await axios.post(route('channels.whatsapp.qr', props.business.id));
            if (res.data?.status === 'connected') {
                clearInterval(autoRefreshTimer);
                window.location.reload();
            } else if (res.data?.qr_image) {
                qrCodeData.value = res.data.qr_image;
            } else if (res.data?.qr_code) {
                qrCodeData.value = res.data.qr_code;
            }
        } catch (e) {}
    }, 10000);
};

const generateWhatsAppQr = async () => {
    isGeneratingQr.value = true;
    qrErrorMessage.value = '';
    try {
        const response = await axios.post(route('channels.whatsapp.qr', props.business.id));
        const resData = response.data || {};

        if (resData.qr_image) {
            qrCodeData.value = resData.qr_image;
        } else if (resData.qr_code) {
            qrCodeData.value = resData.qr_code;
        }

        // If session is still connecting and QR is not ready yet, poll up to 4 times
        if (!qrCodeData.value && resData.status === 'connecting') {
            for (let i = 0; i < 4; i++) {
                await new Promise(r => setTimeout(r, 1200));
                try {
                    const pollRes = await axios.post(route('channels.whatsapp.qr', props.business.id));
                    if (pollRes.data?.qr_image) {
                        qrCodeData.value = pollRes.data.qr_image;
                        break;
                    } else if (pollRes.data?.qr_code) {
                        qrCodeData.value = pollRes.data.qr_code;
                        break;
                    }
                } catch (e) {}
            }
        }

        if (qrCodeData.value) {
            startAutoRefresh();
        }
    } catch (err) {
        qrErrorMessage.value = err.response?.data?.message || 'تعذر توليد رمز QR، تأكد من تشغيل خادم واتساب.';
    } finally {
        isGeneratingQr.value = false;
    }
};

const requestPairingCode = async () => {
    if (!phoneNumber.value) {
        pairingError.value = 'يرجى إدخال رقم الهاتف مع مفتاح الدولة (مثال: 201012345678)';
        return;
    }

    isRequestingPairingCode.value = true;
    pairingError.value = '';
    pairingCode.value = '';

    try {
        const response = await axios.post(route('channels.whatsapp.pairing-code', props.business.id), {
            phone_number: phoneNumber.value
        });

        if (response.data?.pairing_code) {
            pairingCode.value = response.data.pairing_code;
            startStatusPolling();
        } else if (response.data?.message) {
            pairingError.value = response.data.message;
        }
    } catch (err) {
        pairingError.value = err.response?.data?.message || 'تعذر الحصول على كود الربط، تحقق من صحة الرقم والخادم.';
    } finally {
        isRequestingPairingCode.value = false;
    }
};

let statusPollTimer = null;

const startStatusPolling = () => {
    if (statusPollTimer) clearInterval(statusPollTimer);
    statusPollTimer = setInterval(async () => {
        try {
            const statusUrl = `/businesses/${props.business.id}/channels/whatsapp/status`;
            const res = await axios.get(statusUrl);
            if (res.data?.connected) {
                clearInterval(statusPollTimer);
                window.location.reload();
            }
        } catch (e) {}
    }, 2000);
};

const copyPairingCode = () => {
    if (!pairingCode.value) return;
    navigator.clipboard.writeText(pairingCode.value);
    codeCopied.value = true;
    setTimeout(() => { codeCopied.value = false; }, 2500);
};

onUnmounted(() => {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    if (statusPollTimer) clearInterval(statusPollTimer);
});

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
        <div class="border-b border-slate-800 bg-slate-900/60 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-3">
                    <Link :href="route('businesses.index')" class="hover:text-blue-400 transition">المتاجر</Link>
                    <span class="text-slate-600">/</span>
                    <span class="text-slate-300">{{ business.name }}</span>
                    <span class="text-slate-600">/</span>
                    <span class="text-blue-400">إعدادات القنوات</span>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-950/60 text-blue-300 border border-blue-800/60 mb-2">
                            <span>متجر: {{ business.name }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                            {{ activeTab === 'channels' ? 'ربط القنوات والمنصات' : 'إعدادات المساعد الذكي والبرومبت' }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-400">
                            {{ activeTab === 'channels'
                                ? `قم بربط وتفعيل حسابات واتساب، تيليجرام، فيسبوك، وإنستجرام لمتجر ${business.name}.`
                                : `تخصيص البرومبت، النبرة، وقواعد الرد الآلي بالذكاء الاصطناعي لمتجر ${business.name}.` }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="openGuideModal('facebook')"
                            class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/20 transition"
                        >
                            <HelpCircle class="w-4 h-4" />
                            <span>دليل وخطوات ربط القنوات</span>
                        </button>

                        <Link
                            :href="route('businesses.index')"
                            class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 transition"
                        >
                            <span>العودة للمتاجر</span>
                            <ArrowRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="mt-6 flex items-center gap-3 border-b border-slate-800 pt-2">
                    <button
                        type="button"
                        @click="activeTab = 'channels'"
                        class="cursor-pointer inline-flex items-center gap-2.5 px-5 py-3 text-xs sm:text-sm font-bold border-b-2 transition"
                        :class="activeTab === 'channels'
                            ? 'border-blue-500 text-blue-400 bg-blue-500/10 rounded-t-xl shadow-sm'
                            : 'border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 rounded-t-xl'"
                    >
                        <Layers class="w-4 h-4" />
                        <span>قنوات التواصل</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'ai'"
                        class="cursor-pointer inline-flex items-center gap-2.5 px-5 py-3 text-xs sm:text-sm font-bold border-b-2 transition"
                        :class="activeTab === 'ai'
                            ? 'border-violet-500 text-violet-400 bg-violet-500/10 rounded-t-xl shadow-sm'
                            : 'border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 rounded-t-xl'"
                    >
                        <Sparkles class="w-4 h-4 text-violet-400" />
                        <span>المساعد الذكي والبرومبت (AI)</span>
                        <span
                            class="px-2 py-0.5 text-[10px] rounded-full font-bold"
                            :class="aiForm.ai_enabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                        >
                            {{ aiForm.ai_enabled ? 'مفعل' : 'معطل' }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Global Help & Quick Setup Guide Banner -->
            <div v-if="activeTab === 'channels'" class="mb-6 p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-blue-950/70 via-slate-900 to-indigo-950/70 border border-blue-500/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center shrink-0">
                        <HelpCircle class="w-6 h-6 text-blue-400" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>دليل وخطوات ربط القنوات والمنصات (شرح خطوة بخطوة)</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 font-semibold border border-blue-500/30">دليل مصور</span>
                        </h4>
                        <p class="text-xs text-slate-300 mt-0.5">
                            هل تبحث عن روابط الويب هوك، رمز التحقق (Verify Token)، أو كيفية توليد مفاتيح الربط لفيسبوك، واتساب، إنستجرام وتيليجرام؟
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="openGuideModal('facebook')"
                    class="shrink-0 cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition"
                >
                    <HelpCircle class="w-4 h-4" />
                    <span>فتح دليل الربط التفصيلي</span>
                </button>
            </div>

            <!-- Channels 2-column Grid -->
            <div v-if="activeTab === 'channels'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- 1. WhatsApp Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-slate-800 text-slate-200 flex items-center justify-center font-bold border border-slate-700">
                                    <MessageCircle class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-white">واتساب (WhatsApp)</h3>
                                    <p class="text-xs text-slate-400">ربط مباشر برمز QR عبر مكتبة Baileys</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openGuideModal('whatsapp')"
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition"
                                    title="شرح وخطوات ربط واتساب"
                                >
                                    <HelpCircle class="w-3.5 h-3.5 text-emerald-400" />
                                    <span>طريقة الربط</span>
                                </button>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="whatsapp?.status === 'connected' ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="whatsapp?.status === 'connected' ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                                    <span>{{ whatsapp?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Mode Switcher (If not connected) -->
                        <div v-if="whatsapp?.status !== 'connected'" class="mt-4 flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800 text-xs">
                            <button
                                @click="connectMode = 'qr'"
                                class="flex-1 py-2 px-3 rounded-lg font-bold transition flex items-center justify-center gap-1.5"
                                :class="connectMode === 'qr' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white'"
                            >
                                <QrCode class="w-3.5 h-3.5" />
                                <span>مسح رمز QR</span>
                            </button>
                            <button
                                @click="connectMode = 'phone'"
                                class="flex-1 py-2 px-3 rounded-lg font-bold transition flex items-center justify-center gap-1.5"
                                :class="connectMode === 'phone' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white'"
                            >
                                <Smartphone class="w-3.5 h-3.5" />
                                <span>كود الربط برقم الهاتف</span>
                            </button>
                        </div>

                        <!-- WhatsApp Body -->
                        <div class="mt-4 p-6 rounded-xl bg-slate-950 border border-slate-800/80 text-center">
                            <!-- State 1: Connected -->
                            <div v-if="whatsapp?.status === 'connected'" class="space-y-3">
                                <div class="inline-flex p-3 rounded-full bg-slate-900 text-emerald-400 border border-emerald-800/50">
                                    <CheckCircle2 class="w-8 h-8" />
                                </div>
                                <h4 class="font-bold text-white text-base">واتساب متصل ويعمل لايف</h4>
                                <p class="text-xs text-slate-400 font-mono">{{ whatsapp.phone_number || 'الرقم متصل' }}</p>
                            </div>

                            <!-- State 2: QR Mode -->
                            <div v-else-if="connectMode === 'qr'">
                                <div v-if="qrCodeData" class="space-y-3 py-1">
                                    <div class="bg-white p-3 rounded-2xl inline-block shadow-lg border border-slate-700">
                                        <img
                                            :src="computedQrImage"
                                            alt="WhatsApp QR Code"
                                            class="w-48 h-48 object-contain rounded-xl"
                                        />
                                    </div>
                                    <div class="flex items-center justify-center gap-2 text-[11px] text-emerald-400">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                        <span>الكود نشط ويتجدد تلقائياً لضمان الصلاحية</span>
                                    </div>
                                    <p class="text-xs text-slate-300 max-w-sm mx-auto leading-relaxed">
                                        افتح تطبيق واتساب بهاتفك > <strong>الأجهزة المرتبطة</strong> > <strong>ربط جهاز</strong>، ثم وجّه الكاميرا نحو الشاشة.
                                    </p>
                                </div>

                                <div v-else class="space-y-4 py-2">
                                    <div class="inline-flex p-4 rounded-2xl bg-slate-900 border border-slate-800 text-slate-300">
                                        <QrCode class="w-12 h-12 text-slate-300" />
                                    </div>
                                    <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                                        اضغط لتوليد رمز QR ومسحه من تطبيق واتساب بهاتفك في ثوانٍ دون الحاجة لموافقة ميتا أو رسوم اشتراك.
                                    </p>
                                    <p v-if="qrErrorMessage" class="text-xs text-rose-400 mt-2">
                                        {{ qrErrorMessage }}
                                    </p>
                                </div>
                            </div>

                            <!-- State 3: Phone Pairing Code Mode -->
                            <div v-else class="space-y-4 py-1 text-right">
                                <div v-if="pairingCode" class="space-y-4 text-center">
                                    <p class="text-xs text-slate-400">أدخل هذا الكود داخل تطبيق واتساب على هاتفك:</p>
                                    <div class="inline-flex items-center gap-3 bg-slate-900 border border-blue-500/50 p-4 rounded-2xl">
                                        <span class="font-mono text-2xl font-black text-blue-400 tracking-widest">{{ pairingCode }}</span>
                                        <button
                                            @click="copyPairingCode"
                                            class="cursor-pointer p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                                            title="نسخ الكود"
                                        >
                                            <Check v-if="codeCopied" class="w-5 h-5 text-emerald-400" />
                                            <Copy v-else class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <div class="text-xs text-slate-300 max-w-sm mx-auto space-y-1">
                                        <p>1. افتح واتساب > <strong>الأجهزة المرتبطة</strong> > <strong>ربط جهاز</strong></p>
                                        <p>2. اضغط على <strong>الربط برقم الهاتف بدلاً من ذلك</strong></p>
                                        <p>3. اكتب الكود المعروض أعلاه وسيتصل حسابك فوراً!</p>
                                    </div>
                                </div>

                                <div v-else class="space-y-3">
                                    <label class="block text-xs font-medium text-slate-300">
                                        رقم الهاتف (مع مفتاح الدولة بدون أصفار إضافية):
                                    </label>
                                    <input
                                        v-model="phoneNumber"
                                        type="text"
                                        dir="ltr"
                                        placeholder="201012345678"
                                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 font-mono focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                    <p class="text-[11px] text-slate-400">
                                        مثال لمصر: <span class="font-mono text-slate-300">2010xxxxxxxx</span> | السعودية: <span class="font-mono text-slate-300">9665xxxxxxxx</span>
                                    </p>
                                    <p v-if="pairingError" class="text-xs text-rose-400">
                                        {{ pairingError }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-slate-800 flex items-center justify-between">
                        <!-- QR Mode Button -->
                        <button
                            v-if="whatsapp?.status !== 'connected' && connectMode === 'qr'"
                            @click="generateWhatsAppQr"
                            :disabled="isGeneratingQr"
                            class="cursor-pointer w-full py-3 px-4 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 active:bg-blue-700 disabled:opacity-50 text-white transition flex items-center justify-center gap-2 shadow-sm"
                        >
                            <Loader2 v-if="isGeneratingQr" class="w-4 h-4 animate-spin" />
                            <RefreshCw v-else-if="qrCodeData" class="w-4 h-4" />
                            <QrCode v-else class="w-4 h-4" />
                            <span>{{ isGeneratingQr ? 'جاري الاتصال وتوليد الرمز...' : (qrCodeData ? 'تحديث رمز الـ QR يدوياً' : 'توليد كود الـ QR للربط') }}</span>
                        </button>

                        <!-- Phone Mode Button -->
                        <button
                            v-else-if="whatsapp?.status !== 'connected' && connectMode === 'phone'"
                            @click="requestPairingCode"
                            :disabled="isRequestingPairingCode"
                            class="cursor-pointer w-full py-3 px-4 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 active:bg-blue-700 disabled:opacity-50 text-white transition flex items-center justify-center gap-2 shadow-sm"
                        >
                            <Loader2 v-if="isRequestingPairingCode" class="w-4 h-4 animate-spin" />
                            <Smartphone v-else class="w-4 h-4" />
                            <span>{{ isRequestingPairingCode ? 'جاري طلب الكود من واتساب...' : (pairingCode ? 'طلب كود جديد' : 'الحصول على كود الربط') }}</span>
                        </button>

                        <!-- Disconnect Button -->
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
                                <div class="w-12 h-12 rounded-xl bg-slate-800 text-slate-200 flex items-center justify-center font-bold border border-slate-700">
                                    <Send class="w-6 h-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-white">تيليجرام (Telegram)</h3>
                                    <p class="text-xs text-slate-400">ربط فوري وتلقائي عبر Webhook</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openGuideModal('telegram')"
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition"
                                    title="شرح وخطوات ربط تيليجرام"
                                >
                                    <HelpCircle class="w-3.5 h-3.5 text-sky-400" />
                                    <span>طريقة الربط</span>
                                </button>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="telegram?.status === 'connected' ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="telegram?.status === 'connected' ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                                    <span>{{ telegram?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Telegram Form -->
                        <div class="mt-6">
                            <div v-if="telegram?.status === 'connected'" class="p-5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-500 block">البوت المتصل:</span>
                                    <span class="font-bold text-sm text-blue-400">@{{ telegram.bot_username }}</span>
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
                                        autocomplete="off"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 !bg-slate-950 border border-slate-800 !text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="7123456789:AAH7Xxxxx..."
                                        required
                                    />
                                    <InputError class="mt-1.5" :message="telegramForm.errors.bot_token" />
                                    <p class="text-[11px] text-slate-500 mt-1.5">احصل على التوكن من خلال محادثة @BotFather داخل تيليجرام.</p>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="telegramForm.processing"
                                    class="cursor-pointer w-full justify-center bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white py-3 rounded-xl text-xs font-bold transition shadow-sm"
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
                                <div class="w-12 h-12 rounded-xl bg-slate-800 text-slate-200 flex items-center justify-center font-bold border border-slate-700">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-white">صفحة فيسبوك (Facebook)</h3>
                                    <p class="text-xs text-slate-400">استقبال الرسائل والـ Comment-to-DM</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openGuideModal('facebook')"
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition"
                                    title="شرح وخطوات ربط فيسبوك"
                                >
                                    <HelpCircle class="w-3.5 h-3.5 text-blue-400" />
                                    <span>طريقة الربط</span>
                                </button>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="facebook?.status === 'connected' ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="facebook?.status === 'connected' ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                                    <span>{{ facebook?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Facebook Form -->
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

                            <!-- Facebook Comment-to-DM Automation -->
                            <div v-if="facebook?.status === 'connected'" class="mt-4 p-4 rounded-xl bg-slate-950/90 border border-slate-800/80 space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                    <div class="flex items-center gap-2">
                                        <MessageCircle class="w-4 h-4 text-blue-400" />
                                        <h4 class="text-xs font-bold text-slate-200">الرد التلقائي على التعليقات (Comment-to-DM)</h4>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="fbCommentForm.comment_reply_enabled"
                                            class="sr-only peer"
                                        />
                                        <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>

                                <form @submit.prevent="saveFbCommentSettings" class="space-y-3">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-[11px] font-bold text-slate-300">الرد العام على التعليق في البوست</label>
                                            <span class="text-[10px] text-slate-500 font-mono">يدعم {name}</span>
                                        </div>
                                        <textarea
                                            v-model="fbCommentForm.comment_reply_text"
                                            rows="2"
                                            class="mt-1 block w-full rounded-xl text-xs px-3 py-2 bg-slate-900 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-1 focus:ring-blue-500"
                                            placeholder="تم الرد على الخاص بنجاح! راجع رسائلك 📩"
                                        ></textarea>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-[11px] font-bold text-slate-300">الرسالة الخاصة المرسلة في الخاص (DM)</label>
                                            <span class="text-[10px] text-slate-500 font-mono">يدعم {name}</span>
                                        </div>
                                        <textarea
                                            v-model="fbCommentForm.dm_message_text"
                                            rows="3"
                                            class="mt-1 block w-full rounded-xl text-xs px-3 py-2 bg-slate-900 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-1 focus:ring-blue-500"
                                            placeholder="أهلاً بك {name}! بخصوص استفسارك على المنشور، تفضل التفاصيل..."
                                        ></textarea>
                                    </div>

                                    <div>
                                        <label class="text-[11px] font-bold text-slate-300">فلتر الكلمات المفتاحية (اختياري - مفصول بفاصلة)</label>
                                        <input
                                            type="text"
                                            v-model="fbCommentForm.comment_keywords"
                                            class="mt-1 block w-full rounded-xl text-xs px-3 py-2 bg-slate-900 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-1 focus:ring-blue-500"
                                            placeholder="سعر, بكام, تفاصيل, مقاس (اتركه فارغاً للرد على الكل)"
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="fbCommentForm.processing"
                                        class="cursor-pointer w-full py-2.5 px-3 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white transition flex items-center justify-center gap-1.5 shadow-sm"
                                    >
                                        <Save class="w-3.5 h-3.5" />
                                        <span>{{ fbCommentForm.processing ? 'جاري الحفظ...' : 'حفظ إعدادات الرد على التعليقات' }}</span>
                                    </button>
                                </form>
                            </div>

                            <form v-else @submit.prevent="connectFacebook" class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-slate-400">تحتاج مساعدة في استخراج المعرف والتوكن؟</span>
                                    <button
                                        type="button"
                                        @click="openGuideModal('facebook')"
                                        class="cursor-pointer inline-flex items-center gap-1 text-[11px] text-blue-400 hover:text-blue-300 font-bold"
                                    >
                                        <HelpCircle class="w-3.5 h-3.5" />
                                        <span>عرض الخطوات بالتفصيل</span>
                                    </button>
                                </div>
                                <div>
                                    <label for="page_id" class="block text-xs font-bold text-slate-300">
                                        معرف الصفحة (Page ID)
                                    </label>
                                    <input
                                        id="page_id"
                                        v-model="facebookForm.page_id"
                                        type="text"
                                        dir="ltr"
                                        autocomplete="new-password"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 !bg-slate-950 border border-slate-800 !text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="104523984578..."
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
                                        autocomplete="new-password"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 !bg-slate-950 border border-slate-800 !text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="EAA..."
                                        required
                                    />
                                    <InputError class="mt-1.5" :message="facebookForm.errors.page_access_token" />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="facebookForm.processing"
                                    class="cursor-pointer w-full justify-center bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white py-3 rounded-xl text-xs font-bold transition shadow-sm mt-2"
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
                                <div class="w-12 h-12 rounded-xl bg-slate-800 text-slate-200 flex items-center justify-center font-bold border border-slate-700">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-white">إنستجرام (Instagram)</h3>
                                    <p class="text-xs text-slate-400">إدارة رسائل الخاص Direct Messages</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openGuideModal('instagram')"
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition"
                                    title="شرح وخطوات ربط إنستجرام"
                                >
                                    <HelpCircle class="w-3.5 h-3.5 text-pink-400" />
                                    <span>طريقة الربط</span>
                                </button>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="instagram?.status === 'connected' ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="instagram?.status === 'connected' ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                                    <span>{{ instagram?.status === 'connected' ? 'متصل بنجاح' : 'غير متصل' }}</span>
                                </span>
                            </div>
                        </div>

                        <!-- Instagram Form -->
                        <div class="mt-6">
                            <div v-if="instagram?.status === 'connected'" class="p-5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-slate-500 block">الحساب المربوط:</span>
                                    <span class="font-bold text-sm text-blue-400">@{{ instagram.username || instagram.instagram_business_id }}</span>
                                </div>
                                <button
                                    @click="disconnect('instagram')"
                                    class="cursor-pointer text-xs font-bold text-rose-400 hover:underline"
                                >
                                    فصل الحساب
                                </button>
                            </div>

                            <!-- Instagram Comment-to-DM Automation -->
                            <div v-if="instagram?.status === 'connected'" class="mt-4 p-4 rounded-xl bg-slate-950/90 border border-slate-800/80 space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                    <div class="flex items-center gap-2">
                                        <MessageCircle class="w-4 h-4 text-pink-400" />
                                        <h4 class="text-xs font-bold text-slate-200">الرد التلقائي على تعليقات إنستجرام (Comment-to-DM)</h4>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="igCommentForm.comment_reply_enabled"
                                            class="sr-only peer"
                                        />
                                        <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-pink-600"></div>
                                    </label>
                                </div>

                                <form @submit.prevent="saveIgCommentSettings" class="space-y-3">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-[11px] font-bold text-slate-300">الرد العام على التعليق في المنشور</label>
                                            <span class="text-[10px] text-slate-500 font-mono">يدعم {name} أو {username}</span>
                                        </div>
                                        <textarea
                                            v-model="igCommentForm.comment_reply_text"
                                            rows="2"
                                            class="mt-1 block w-full rounded-xl text-xs px-3 py-2 bg-slate-900 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-1 focus:ring-pink-500"
                                            placeholder="تم الرد في الخاص بنجاح! راجع الدايركت 📩"
                                        ></textarea>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-[11px] font-bold text-slate-300">الرسالة الخاصة في الدايركت (Direct DM)</label>
                                            <span class="text-[10px] text-slate-500 font-mono">يدعم {name} أو {username}</span>
                                        </div>
                                        <textarea
                                            v-model="igCommentForm.dm_message_text"
                                            rows="3"
                                            class="mt-1 block w-full rounded-xl text-xs px-3 py-2 bg-slate-900 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-1 focus:ring-pink-500"
                                            placeholder="مرحباً {name}! تفضل تفاصيل المنتج والعرض المتاح حالياً..."
                                        ></textarea>
                                    </div>

                                    <div>
                                        <label class="text-[11px] font-bold text-slate-300">فلتر الكلمات المفتاحية (اختياري - مفصول بفاصلة)</label>
                                        <input
                                            type="text"
                                            v-model="igCommentForm.comment_keywords"
                                            class="mt-1 block w-full rounded-xl text-xs px-3 py-2 bg-slate-900 border border-slate-800 text-slate-100 placeholder-slate-600 focus:ring-1 focus:ring-pink-500"
                                            placeholder="سعر, بكام, تفاصيل, مقاس (اتركه فارغاً للرد على الكل)"
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="igCommentForm.processing"
                                        class="cursor-pointer w-full py-2.5 px-3 rounded-xl text-xs font-bold bg-pink-600 hover:bg-pink-500 text-white transition flex items-center justify-center gap-1.5 shadow-sm"
                                    >
                                        <Save class="w-3.5 h-3.5" />
                                        <span>{{ igCommentForm.processing ? 'جاري الحفظ...' : 'حفظ إعدادات الرد على التعليقات' }}</span>
                                    </button>
                                </form>
                            </div>

                            <form v-else @submit.prevent="connectInstagram" class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-slate-400">تحتاج مساعدة في استخراج معرف وتوكن إنستجرام؟</span>
                                    <button
                                        type="button"
                                        @click="openGuideModal('instagram')"
                                        class="cursor-pointer inline-flex items-center gap-1 text-[11px] text-pink-400 hover:text-pink-300 font-bold"
                                    >
                                        <HelpCircle class="w-3.5 h-3.5" />
                                        <span>عرض الخطوات بالتفصيل</span>
                                    </button>
                                </div>
                                <div>
                                    <label for="instagram_business_id" class="block text-xs font-bold text-slate-300">
                                        Instagram Business Account ID
                                    </label>
                                    <input
                                        id="instagram_business_id"
                                        v-model="instagramForm.instagram_business_id"
                                        type="text"
                                        dir="ltr"
                                        autocomplete="new-password"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 !bg-slate-950 border border-slate-800 !text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="17841405..."
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
                                        autocomplete="new-password"
                                        class="mt-1.5 block w-full rounded-xl text-xs font-mono px-3.5 py-3 !bg-slate-950 border border-slate-800 !text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                        placeholder="EAA..."
                                        required
                                    />
                                    <InputError class="mt-1.5" :message="instagramForm.errors.access_token" />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="instagramForm.processing"
                                    class="cursor-pointer w-full justify-center bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white py-3 rounded-xl text-xs font-bold transition shadow-sm mt-2"
                                >
                                    ربط إنستجرام وتفعيل الاستقبال
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>

            <!-- AI Assistant Settings Panel -->
            <div v-else-if="activeTab === 'ai'" class="max-w-5xl mx-auto space-y-6">
                <!-- Top Notice Card -->
                <div class="bg-gradient-to-r from-violet-950/40 to-slate-900 border border-violet-800/40 rounded-2xl p-6 shadow-xl backdrop-blur-sm relative overflow-hidden">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
                        <div class="flex items-start gap-4">
                            <div class="p-3.5 bg-violet-600/20 border border-violet-500/30 rounded-2xl text-violet-400 shrink-0 shadow-inner">
                                <Bot class="w-8 h-8" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-bold text-white">المساعد الذكي لمتجر: {{ business.name }}</h2>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-violet-500/20 text-violet-300 border border-violet-500/30">
                                        <Sparkles class="w-3 h-3" />
                                        نظام مستقل لكل متجر
                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                                    كل متجر لديك يمتلك برومبت، شخصية، وقواعد عمل منفصلة كلياً. لو عندك متجر أحذية ومتجر ملابس، كل متجر سيرد تلقائياً ببضاعته وقواعده دون تداخل.
                                </p>
                            </div>
                        </div>

                        <!-- Main AI Toggle for this store -->
                        <div class="flex items-center gap-3 bg-slate-950/80 px-4 py-3 rounded-2xl border border-slate-800 shrink-0">
                            <span class="text-xs font-bold" :class="aiForm.ai_enabled ? 'text-emerald-400' : 'text-slate-400'">
                                {{ aiForm.ai_enabled ? 'الذكاء الاصطناعي مفعّل' : 'الذكاء الاصطناعي معطّل' }}
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="aiForm.ai_enabled"
                                    class="sr-only peer"
                                />
                                <div class="w-12 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[3px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Quick Preset Templates -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            <Tag class="w-4 h-4 text-violet-400" />
                            <h3 class="text-xs sm:text-sm font-bold text-white">نماذج برومبت جاهزة بنقرة واحدة (Quick Presets)</h3>
                        </div>
                        <span class="text-[11px] text-slate-400">انقر لملء الشخصية والتعليمات تلقائياً لمتجرك</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <button
                            type="button"
                            @click="applyPreset('shoes')"
                            class="p-3 rounded-xl bg-slate-950 hover:bg-violet-950/30 border border-slate-800 hover:border-violet-500/50 text-start transition group cursor-pointer"
                        >
                            <div class="font-bold text-xs text-white group-hover:text-violet-300 flex items-center justify-between">
                                <span>👞 متجر أحذية</span>
                                <span class="text-[10px] text-slate-500 font-normal">جاهز</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">مقاسات EU 37-46، شحن 48h، معاينة</p>
                        </button>

                        <button
                            type="button"
                            @click="applyPreset('clothes')"
                            class="p-3 rounded-xl bg-slate-950 hover:bg-violet-950/30 border border-slate-800 hover:border-violet-500/50 text-start transition group cursor-pointer"
                        >
                            <div class="font-bold text-xs text-white group-hover:text-violet-300 flex items-center justify-between">
                                <span>👗 متجر ملابس وأزياء</span>
                                <span class="text-[10px] text-slate-500 font-normal">جاهز</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">مقاسات S-3XL، قطن 100%، كود خصم</p>
                        </button>

                        <button
                            type="button"
                            @click="applyPreset('electronics')"
                            class="p-3 rounded-xl bg-slate-950 hover:bg-violet-950/30 border border-slate-800 hover:border-violet-500/50 text-start transition group cursor-pointer"
                        >
                            <div class="font-bold text-xs text-white group-hover:text-violet-300 flex items-center justify-between">
                                <span>📱 متجر إلكترونيات</span>
                                <span class="text-[10px] text-slate-500 font-normal">جاهز</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">ضمان سنتين، شحن مؤمّن، مواصفات</p>
                        </button>

                        <button
                            type="button"
                            @click="applyPreset('general')"
                            class="p-3 rounded-xl bg-slate-950 hover:bg-violet-950/30 border border-slate-800 hover:border-violet-500/50 text-start transition group cursor-pointer"
                        >
                            <div class="font-bold text-xs text-white group-hover:text-violet-300 flex items-center justify-between">
                                <span>🏢 متجر عام ودعم</span>
                                <span class="text-[10px] text-slate-500 font-normal">جاهز</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 line-clamp-1">ترحيب بالعملاء، إجابات سريعة</p>
                        </button>
                    </div>
                </div>

                <!-- Main Form -->
                <form @submit.prevent="saveAiSettings" class="space-y-6">
                    <!-- Model & Persona Card -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
                        <div class="border-b border-slate-800 pb-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    <Sliders class="w-4 h-4 text-violet-400" />
                                    محرك Groq ونبرة الذكاء الاصطناعي لمتجر {{ business.name }}
                                </h3>
                                <span class="text-[11px] font-semibold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                                    GROQ Direct API
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">اتصال سحابي فائق السرعة عبر محركات GROQ</p>
                        </div>

                        <!-- Groq API Key Section -->
                        <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-4 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <label class="text-xs font-bold text-slate-200 flex items-center gap-2">
                                    <Key class="w-4 h-4 text-amber-400" />
                                    مفتاح Groq API للمتجر (Groq API Key)
                                    <span v-if="aiForm.api_key" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <CheckCircle2 class="w-3 h-3" />
                                        المفتاح محدد
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        مطلوب للربط المباشر
                                    </span>
                                </label>
                                <a
                                    href="https://console.groq.com/keys"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-[11px] text-violet-400 hover:text-violet-300 inline-flex items-center gap-1 transition"
                                >
                                    <span>إنشاء مفتاح مجاني من console.groq.com</span>
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                            </div>

                            <div class="relative">
                                <input
                                    :type="showApiKey ? 'text' : 'password'"
                                    v-model="aiForm.api_key"
                                    placeholder="gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 pe-12 text-xs font-mono text-slate-200 placeholder-slate-600 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition"
                                />
                                <div class="absolute inset-y-0 end-0 pe-2.5 flex items-center">
                                    <button
                                        type="button"
                                        @click="showApiKey = !showApiKey"
                                        class="p-1.5 text-slate-400 hover:text-slate-200 rounded-lg hover:bg-slate-800 transition cursor-pointer"
                                        :title="showApiKey ? 'إخفاء المفتاح' : 'إظهار المفتاح'"
                                    >
                                        <EyeOff v-if="showApiKey" class="w-3.5 h-3.5" />
                                        <Eye v-else class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="aiForm.errors.api_key" />

                            <!-- Notice that key is not unique and can be shared across all user stores -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1 text-[11px] text-slate-400">
                                <p>
                                    💡 كل متجر يمتلك إعداداته المستقلة، ويمكنك استخدام نفس مفتاح Groq لجميع متاجرك بكل سهولة.
                                </p>
                                <button
                                    v-if="sharedApiKey && !aiForm.api_key"
                                    type="button"
                                    @click="useSharedKey"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-violet-950/60 hover:bg-violet-900/80 text-violet-300 border border-violet-700/50 text-[11px] font-semibold transition cursor-pointer shrink-0"
                                >
                                    <Sparkles class="w-3 h-3 text-violet-400" />
                                    <span>استخدام نفس المفتاح المحفوظ في متجر آخر</span>
                                </button>
                            </div>

                            <!-- Apply to all stores checkbox -->
                            <div class="pt-2 border-t border-slate-900 flex items-center gap-2">
                                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        v-model="aiForm.apply_to_all_stores"
                                        class="rounded bg-slate-900 border-slate-700 text-violet-600 focus:ring-violet-500"
                                    />
                                    <span class="text-xs font-medium text-slate-300">
                                        تطبيق هذا المفتاح تلقائياً على كافة متاجري الأخرى
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- AI Model (Groq models) -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-slate-300">
                                    نموذج GROQ للذكاء الاصطناعي (Groq AI Model)
                                </label>
                                <span class="text-[11px] text-emerald-400 font-medium">⚡ اتصال مباشر عبر Groq</span>
                            </div>
                            <select
                                v-model="aiForm.ai_model"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-xs sm:text-sm text-slate-200 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition"
                            >
                                <option value="llama-3.3-70b-versatile">Llama 3.3 70B Versatile (الأقوى والأعلى ذكاءً - موصى به لكافة الردود والاستفسارات)</option>
                                <option value="llama-3.1-8b-instant">Llama 3.1 8B Instant (فائق السرعة وأقل استهلاكاً - استجابة فورية)</option>
                                <option value="mixtral-8x7b-32768">Mixtral 8x7B 32k (نافذة سياق واسعة واستيعاب عالي للمعلومات)</option>
                                <option value="gemma2-9b-it">Gemma 2 9B IT (نموذج Google المتطور عبر محرك Groq)</option>
                                <option value="llama-3.2-3b-preview">Llama 3.2 3B (خفيف وسريع جداً للردود التلقائية البسيطة)</option>
                                <option value="llama-3.2-1b-preview">Llama 3.2 1B (أصغر وأسرع نموذج)</option>
                            </select>
                            <InputError class="mt-1" :message="aiForm.errors.ai_model" />
                        </div>

                        <!-- Personality & Tone -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-2">
                                شخصية ونبرة المساعد لمتجر {{ business.name }} (Persona & Tone)
                            </label>
                            <textarea
                                v-model="aiForm.ai_personality"
                                rows="3"
                                placeholder="مثال: أنت ممثل مبيعات محترف وودود في متجر فيرتكس، ترحب دائمًا بالعميل باسمه وتقدم نصائح دقيقة بلهجة مهذبة..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3.5 text-xs sm:text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition leading-relaxed"
                            ></textarea>
                            <p class="text-[11px] text-slate-500 mt-1">تحدد نبرة الخطاب وأسلوب التفاعل مع عملاء هذا المتجر تحديداً.</p>
                            <InputError class="mt-1" :message="aiForm.errors.ai_personality" />
                        </div>

                        <!-- System Prompt / Custom Instructions -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-slate-300">
                                    البرومبت وتعليمات المتجر (Store Prompt & Guidelines)
                                </label>
                                <span class="text-[11px] font-mono text-slate-500">
                                    {{ aiForm.ai_instructions ? aiForm.ai_instructions.length : 0 }} حرف
                                </span>
                            </div>
                            <textarea
                                v-model="aiForm.ai_instructions"
                                rows="6"
                                placeholder="اكتب هنا جميع تفاصيل متجرك مثل:
- أنواع المنتجات وأسعارها والمقاسات المتوفرة.
- مناطق وسرعة الشحن وتكلفته.
- سياسة الاستبدال والاسترجاع والشروط.
- العروض الحالية وأكواد الخصم."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3.5 text-xs font-mono text-slate-200 placeholder-slate-600 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition leading-relaxed"
                            ></textarea>
                            <p class="text-[11px] text-slate-500 mt-1">هذا البرومبت يغذي المساعد الذكي بمعلومات منتجات وقواعد متجر {{ business.name }} فقط.</p>
                            <InputError class="mt-1" :message="aiForm.errors.ai_instructions" />
                        </div>
                    </div>

                    <!-- Auto-Reply Channels & Working Hours -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
                        <div class="border-b border-slate-800 pb-4">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <Zap class="w-4 h-4 text-amber-400" />
                                قنوات التفعيل وجدولة ساعات العمل
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">تحكم في أين ومتى يتولى الذكاء الاصطناعي الرد التلقائي</p>
                        </div>

                        <!-- Channels Checkboxes -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-3">
                                تفعيل الرد الآلي على القنوات التالية لمتجر {{ business.name }}:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <button
                                    type="button"
                                    @click="toggleAiPlatform('whatsapp')"
                                    class="p-3 rounded-xl border flex items-center justify-between text-xs font-bold transition cursor-pointer"
                                    :class="aiForm.auto_reply_platforms.includes('whatsapp')
                                        ? 'bg-emerald-950/30 border-emerald-600/60 text-emerald-300'
                                        : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200'"
                                >
                                    <span>واتساب (WhatsApp)</span>
                                    <CheckCircle2 v-if="aiForm.auto_reply_platforms.includes('whatsapp')" class="w-4 h-4 text-emerald-400" />
                                </button>

                                <button
                                    type="button"
                                    @click="toggleAiPlatform('telegram')"
                                    class="p-3 rounded-xl border flex items-center justify-between text-xs font-bold transition cursor-pointer"
                                    :class="aiForm.auto_reply_platforms.includes('telegram')
                                        ? 'bg-sky-950/30 border-sky-600/60 text-sky-300'
                                        : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200'"
                                >
                                    <span>تيليجرام (Telegram)</span>
                                    <CheckCircle2 v-if="aiForm.auto_reply_platforms.includes('telegram')" class="w-4 h-4 text-sky-400" />
                                </button>

                                <button
                                    type="button"
                                    @click="toggleAiPlatform('facebook')"
                                    class="p-3 rounded-xl border flex items-center justify-between text-xs font-bold transition cursor-pointer"
                                    :class="aiForm.auto_reply_platforms.includes('facebook')
                                        ? 'bg-blue-950/30 border-blue-600/60 text-blue-300'
                                        : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200'"
                                >
                                    <span>فيسبوك (Messenger)</span>
                                    <CheckCircle2 v-if="aiForm.auto_reply_platforms.includes('facebook')" class="w-4 h-4 text-blue-400" />
                                </button>

                                <button
                                    type="button"
                                    @click="toggleAiPlatform('instagram')"
                                    class="p-3 rounded-xl border flex items-center justify-between text-xs font-bold transition cursor-pointer"
                                    :class="aiForm.auto_reply_platforms.includes('instagram')
                                        ? 'bg-pink-950/30 border-pink-600/60 text-pink-300'
                                        : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200'"
                                >
                                    <span>إنستجرام (Direct)</span>
                                    <CheckCircle2 v-if="aiForm.auto_reply_platforms.includes('instagram')" class="w-4 h-4 text-pink-400" />
                                </button>
                            </div>
                        </div>

                        <!-- Working Hours vs 24/7 -->
                        <div class="pt-2 border-t border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-slate-200 block">تقييد الردود بساعات العمل فقط</span>
                                <span class="text-[11px] text-slate-400">إذا تم التعطيل، سيرد المساعد التلقائي على مدار 24 ساعة يومياً.</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer select-none shrink-0">
                                <input
                                    type="checkbox"
                                    v-model="aiForm.working_hours_only"
                                    class="sr-only peer"
                                />
                                <div class="w-12 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[3px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                            </label>
                        </div>

                        <!-- Hours Inputs -->
                        <div v-if="aiForm.working_hours_only" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">بداية العمل (ساعة بنظام 24: من 0 إلى 23)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="23"
                                    v-model.number="aiForm.work_start_hour"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">نهاية العمل (ساعة بنظام 24: من 0 إلى 23)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="23"
                                    v-model.number="aiForm.work_end_hour"
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-200 focus:outline-none focus:border-amber-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Submit Actions -->
                    <div class="flex items-center justify-between gap-4 pt-2">
                        <div class="text-xs text-slate-400">
                            يتم حفظ التعديلات فوراً لمتجر <span class="font-bold text-white">{{ business.name }}</span>.
                        </div>

                        <button
                            type="submit"
                            :disabled="aiForm.processing"
                            class="cursor-pointer inline-flex items-center gap-2 px-6 py-3.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-bold rounded-xl text-xs sm:text-sm transition shadow-lg shadow-violet-600/30 disabled:opacity-50"
                        >
                            <Loader2 v-if="aiForm.processing" class="w-4 h-4 animate-spin" />
                            <Save v-else class="w-4 h-4" />
                            <span>{{ aiForm.processing ? 'جاري الحفظ...' : `حفظ إعدادات المساعد لمتجر ${business.name}` }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Channel Guide Modal -->
        <ChannelGuideModal
            :show="showGuideModal"
            :initial-channel="activeGuideChannel"
            :business="business"
            @close="showGuideModal = false"
        />
    </AuthenticatedLayout>
</template>
