<script setup>
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import {
    X,
    Copy,
    Check,
    ExternalLink,
    HelpCircle,
    CheckCircle2,
    AlertCircle,
    Info,
    MessageCircle,
    Send,
    Smartphone,
    Key,
    ShieldCheck,
    Layers,
    AlertTriangle,
    Zap,
    Globe,
} from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    initialChannel: {
        type: String,
        default: 'facebook',
    },
    business: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const selectedChannel = ref(props.initialChannel || 'facebook');

// Watch for changes in initialChannel when opened
import { watch } from 'vue';
watch(
    () => props.initialChannel,
    (newVal) => {
        if (newVal) {
            selectedChannel.value = newVal;
        }
    }
);

const copiedKey = ref('');

const copyToClipboard = (text, key) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copiedKey.value = key;
    setTimeout(() => {
        if (copiedKey.value === key) {
            copiedKey.value = '';
        }
    }, 2000);
};

const baseUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin;
    }
    return 'https://vertex.78sworks.io';
});

const metaWebhookUrl = computed(() => `${baseUrl.value}/webhooks/meta/${props.business.id}`);
const telegramWebhookUrl = computed(() => `${baseUrl.value}/webhooks/telegram/${props.business.id}`);
const metaVerifyToken = 'vertex_secret_token';

// Facebook Permissions list for badges
const fbPermissionsList = [
    { key: 'pages_messaging', desc: 'لإرسال واستقبال رسائل الماسنجر' },
    { key: 'pages_read_engagement', desc: 'لقراءة التعليقات والتفاعلات على المنشورات' },
    { key: 'pages_manage_engagement', desc: 'للرد على التعليقات برسائل أو تعليقات' },
    { key: 'pages_manage_metadata', desc: 'لربط الصفحة بالـ Webhooks' },
    { key: 'pages_show_list', desc: 'لعرض قائمة الصفحات' },
];

// Instagram Permissions list for badges
const igPermissionsList = [
    { key: 'instagram_basic', desc: 'لقراءة بيانات حساب إنستجرام الأساسي' },
    { key: 'instagram_manage_messages', desc: 'لإرسال واستقبال رسائل الدايركت DM' },
    { key: 'instagram_manage_comments', desc: 'لقراءة التعليقات على منشورات وريلز إنستجرام والرد عليها' },
    { key: 'pages_show_list', desc: 'لعرض صفحة الفيسبوك المرتبطة' },
    { key: 'pages_read_engagement', desc: 'لقراءة التفاعلات' },
    { key: 'pages_manage_metadata', desc: 'لربط الصفحة بالـ Webhooks' },
];

const fbPermissions = fbPermissionsList.map(p => p.key).join(', ');
const igPermissions = igPermissionsList.map(p => p.key).join(', ');

const fbQuery = 'me/accounts?fields=name,id,access_token';
const igQuery = 'me/accounts?fields=name,access_token,instagram_business_account{id,username}';
const fbWebhookFields = 'messages, messaging_postbacks, feed';
const igWebhookFields = 'messages, comments, live_comments';

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal" maxWidth="3xl">
        <div class="flex flex-col h-[85vh] max-h-[85vh] overflow-hidden text-slate-100">
            <!-- Header -->
            <div class="shrink-0 px-6 py-5 border-b border-slate-800 bg-slate-950/70 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl flex items-center justify-center font-bold border"
                        :class="{
                            'bg-emerald-950/60 border-emerald-800/60 text-emerald-400': selectedChannel === 'whatsapp',
                            'bg-sky-950/60 border-sky-800/60 text-sky-400': selectedChannel === 'telegram',
                            'bg-blue-950/60 border-blue-800/60 text-blue-400': selectedChannel === 'facebook',
                            'bg-pink-950/60 border-pink-800/60 text-pink-400': selectedChannel === 'instagram',
                        }"
                    >
                        <MessageCircle v-if="selectedChannel === 'whatsapp'" class="w-5 h-5" />
                        <Send v-else-if="selectedChannel === 'telegram'" class="w-5 h-5" />
                        <svg v-else-if="selectedChannel === 'facebook'" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <svg v-else-if="selectedChannel === 'instagram'" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-extrabold text-white">دليل وطريقة ربط القنوات</h2>
                        <p class="text-xs text-slate-400">خطوات واضحة للحصول على البيانات وربط حساباتك بنجاح</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="closeModal"
                    class="cursor-pointer text-slate-400 hover:text-white p-2 rounded-xl hover:bg-slate-800 transition"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Channel Selector Tabs -->
            <div class="shrink-0 px-6 pt-3 bg-slate-950/40 border-b border-slate-800 flex items-center gap-2 overflow-x-auto">
                <button
                    type="button"
                    @click="selectedChannel = 'facebook'"
                    class="cursor-pointer pb-2.5 px-3 text-xs font-bold border-b-2 transition flex items-center gap-1.5 whitespace-nowrap"
                    :class="selectedChannel === 'facebook'
                        ? 'border-blue-500 text-blue-400'
                        : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span>فيسبوك (Facebook)</span>
                </button>

                <button
                    type="button"
                    @click="selectedChannel = 'instagram'"
                    class="cursor-pointer pb-2.5 px-3 text-xs font-bold border-b-2 transition flex items-center gap-1.5 whitespace-nowrap"
                    :class="selectedChannel === 'instagram'
                        ? 'border-pink-500 text-pink-400'
                        : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    <span>إنستجرام (Instagram)</span>
                </button>

                <button
                    type="button"
                    @click="selectedChannel = 'whatsapp'"
                    class="cursor-pointer pb-2.5 px-3 text-xs font-bold border-b-2 transition flex items-center gap-1.5 whitespace-nowrap"
                    :class="selectedChannel === 'whatsapp'
                        ? 'border-emerald-500 text-emerald-400'
                        : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    <MessageCircle class="w-3.5 h-3.5" />
                    <span>واتساب (WhatsApp)</span>
                </button>

                <button
                    type="button"
                    @click="selectedChannel = 'telegram'"
                    class="cursor-pointer pb-2.5 px-3 text-xs font-bold border-b-2 transition flex items-center gap-1.5 whitespace-nowrap"
                    :class="selectedChannel === 'telegram'
                        ? 'border-sky-500 text-sky-400'
                        : 'border-transparent text-slate-400 hover:text-slate-200'"
                >
                    <Send class="w-3.5 h-3.5" />
                    <span>تيليجرام (Telegram)</span>
                </button>
            </div>

            <!-- Body / Steps -->
            <div class="flex-1 min-h-0 px-6 py-6 overflow-y-auto space-y-6 text-sm">

                <!-- ================== FACEBOOK GUIDE ================== -->
                <div v-if="selectedChannel === 'facebook'" class="space-y-6">
                    <!-- Overview Banner -->
                    <div class="p-4 rounded-xl bg-blue-950/30 border border-blue-800/40 text-blue-200 flex items-start gap-3">
                        <Info class="w-5 h-5 text-blue-400 shrink-0 mt-0.5" />
                        <div class="text-xs space-y-1">
                            <p class="font-bold">دليل ربط صفحة فيسبوك الكامل:</p>
                            <p class="text-blue-300/90 leading-relaxed">
                                تحتاج إلى استخراج رمز وصول دائم (<span class="text-white font-mono font-bold">Page Access Token</span>) مع الصلاحيات المطلوبة، وتفعيل الـ <span class="text-white font-mono font-bold">Webhooks</span> لاستقبال الرسائل والتعليقات لحظياً.
                            </p>
                        </div>
                    </div>

                    <!-- Step 1: Permanent Token & Page ID via Graph API Explorer -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-black">1</span>
                            <h3 class="font-bold text-white text-sm">استخراج معرّف الصفحة (Page ID) والتوكن الدائم (Page Access Token)</h3>
                        </div>

                        <!-- Prerequisite Alert -->
                        <div class="p-3.5 rounded-xl bg-blue-950/40 border border-blue-500/30 text-blue-200 text-xs space-y-1.5">
                            <div class="flex items-center gap-2 font-bold text-blue-300">
                                <ShieldCheck class="w-4 h-4 text-blue-400 shrink-0" />
                                <span>شرط مسبق بسيط:</span>
                            </div>
                            <p class="text-slate-300 leading-relaxed text-[11px] ps-6">
                                يجب أن يكون لديك تطبيق على منصة
                                <a href="https://developers.facebook.com/apps" target="_blank" class="text-blue-400 underline font-bold inline-flex items-center gap-1 mx-1">
                                    <span>Meta for Developers</span>
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                                (إذا لم يكن لديك تطبيق، اضغط <strong>Create App</strong> واختر حالة الاستخدام <strong>Engage with customers on Messenger</strong> أو <strong>Other &gt; Business</strong>).
                            </p>
                        </div>

                        <ol class="list-decimal list-inside space-y-2.5 text-xs text-slate-300 leading-relaxed">
                            <li>
                                افتح أداة
                                <a href="https://developers.facebook.com/tools/explorer" target="_blank" class="text-blue-400 hover:text-blue-300 underline font-bold inline-flex items-center gap-1 mx-1">
                                    <span>Meta Graph API Explorer</span>
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                            </li>
                            <li>
                                من القائمة العلوية اليمنى <span class="text-white font-bold font-mono">Meta App</span>: اختر التطبيق الخاص بك.
                            </li>
                            <li>
                                في خانة <span class="text-white font-bold font-mono">User or Page</span>: اتركها كما هي على خيار <span class="text-blue-300 font-bold">User Token</span>.
                            </li>
                            <li>
                                في قسم <span class="text-white font-bold">Permissions</span> (الصلاحيات): أضف الصلاحيات الأربعة المطلوبة لإدارة الرسائل والصفحات:
                            </li>
                        </ol>

                        <!-- Facebook Permissions Badges Grid -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-bold text-slate-300">الصلاحيات المطلوبة (Permissions):</label>
                                <button
                                    type="button"
                                    @click="copyToClipboard(fbPermissions, 'fb_all_perms')"
                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-400 hover:text-blue-300 transition"
                                >
                                    <Check v-if="copiedKey === 'fb_all_perms'" class="w-3.5 h-3.5 text-emerald-400" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ copiedKey === 'fb_all_perms' ? 'تم نسخ جميع الصلاحيات' : 'نسخ جميع الصلاحيات دفعة واحدة' }}</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div
                                    v-for="perm in fbPermissionsList"
                                    :key="perm.key"
                                    class="flex items-center justify-between p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-xs hover:border-slate-700 transition"
                                >
                                    <div class="min-w-0 pr-1">
                                        <span class="font-mono font-bold text-blue-400 block truncate">{{ perm.key }}</span>
                                        <span class="text-[11px] text-slate-400 block truncate">{{ perm.desc }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(perm.key, 'fb_perm_' + perm.key)"
                                        class="cursor-pointer shrink-0 p-1.5 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition ms-2"
                                        :title="'نسخ ' + perm.key"
                                    >
                                        <Check v-if="copiedKey === 'fb_perm_' + perm.key" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <ol start="5" class="list-decimal list-inside space-y-2.5 text-xs text-slate-300 leading-relaxed pt-1">
                            <li>
                                اضغط على زر <span class="text-white font-bold bg-blue-600 px-2 py-0.5 rounded text-[11px]">Generate Access Token</span> ووافق في نافذة فيسبوك المنبثقة على منح الصلاحيات لصفحاتك.
                            </li>
                            <li>
                                في شريط الاستعلام بالأعلى بجوار زر <span class="text-emerald-400 font-mono font-bold">GET</span>، الصق المسار التالي ثم اضغط زر <span class="text-white font-bold bg-blue-600 px-2 py-0.5 rounded text-[11px]">Submit</span>:
                            </li>
                        </ol>

                        <!-- Query Path Badge with Copy -->
                        <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span class="font-bold text-slate-200">مسار الاستعلام لجلب معرف الصفحة والتوكن الدائم:</span>
                                <button
                                    type="button"
                                    @click="copyToClipboard(fbQuery, 'fb_query_btn')"
                                    class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300 font-bold"
                                >
                                    <Check v-if="copiedKey === 'fb_query_btn'" class="w-3.5 h-3.5 text-emerald-400" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ copiedKey === 'fb_query_btn' ? 'تم النسخ' : 'نسخ المسار' }}</span>
                                </button>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-950 font-mono text-xs text-blue-300 select-all break-all border border-slate-800">
                                {{ fbQuery }}
                            </div>
                            <div class="text-[11px] text-slate-400 leading-relaxed space-y-1">
                                <p>
                                    سيعرض لك Meta في مربع الاستجابة بالأسفل قائمة بصفحاتك، انسخ منها التالي:
                                </p>
                                <ul class="list-disc list-inside ps-2 space-y-0.5 text-slate-300">
                                    <li>قيمة حقل <code class="text-blue-400 font-mono font-bold">"id"</code>: ضعها في خانة <strong>معرف الصفحة (Page ID)</strong> هنا.</li>
                                    <li>قيمة حقل <code class="text-emerald-400 font-mono font-bold">"access_token"</code>: ضعها في خانة <strong>رمز الوصول (Page Access Token)</strong> هنا.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Permanent Token Guarantee Callout -->
                        <div class="p-3 rounded-lg bg-emerald-950/30 border border-emerald-800/40 text-emerald-300 text-xs flex items-start gap-2.5">
                            <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                            <div class="space-y-0.5 leading-relaxed">
                                <p class="font-bold text-emerald-200">توكن دائم لا ينتهي أبداً (Never Expires):</p>
                                <p class="text-[11px] text-emerald-300/90">
                                    رمز الوصول المستخرج بهذه الطريقة هو توكن دائم للصفحة، ولا يحتاج لأي تمديد يدوي. كما يقوم نظام فيرتكس بالاشتراك في صفحتك وتأكيد الربط فور النقر على زر الحفظ.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Webhooks Setup -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-black">2</span>
                            <h3 class="font-bold text-white text-sm">تفعيل استقبال الرسائل والتعليقات (Webhooks) في لوحة Meta</h3>
                        </div>

                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 text-xs text-slate-300 space-y-2 leading-relaxed">
                            <p class="text-slate-300 font-medium">
                                💡 <strong class="text-white">خطوات ضبط الويب هوك في تطبيقك على Meta:</strong>
                            </p>
                            <ol class="list-decimal list-inside space-y-1.5 text-slate-400 text-[11px] pt-1">
                                <li>
                                    ادخل إلى تطبيقك في <a href="https://developers.facebook.com/apps" target="_blank" class="text-blue-400 underline font-bold inline-flex items-center gap-0.5">Meta for Developers <ExternalLink class="w-2.5 h-2.5" /></a>.
                                </li>
                                <li>
                                    من القائمة الجانبية: اضغط على <span class="text-white font-bold font-mono">Webhooks</span> (أو اذهب إلى <span class="text-white font-bold">Use cases</span> ثم اضغط <span class="text-blue-300 font-bold">Customize</span> بجوار <span class="text-white font-bold">Engage with customers on Messenger</span>).
                                </li>
                                <li>
                                    اضغط على زر <span class="text-white font-bold">Add callback URL</span> أو <span class="text-white font-bold">Edit callback URL</span>، وأدخل الرابط والرمز الموضحين أدناه ثم اضغط <span class="text-emerald-400 font-bold">Verify and save</span>.
                                </li>
                                <li>
                                    في جدول حقول الصفحة بالأسفل، فعّل الاشتراك في الحقول: <code class="text-blue-300 font-mono font-bold">messages</code> و <code class="text-blue-300 font-mono font-bold">messaging_postbacks</code> و <code class="text-blue-300 font-mono font-bold">feed</code>.
                                </li>
                            </ol>
                        </div>

                        <!-- Webhook Credentials Box -->
                        <div class="space-y-2.5">
                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1 font-bold text-slate-300">
                                    <span>عنوان الويب هوك (Callback URL):</span>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(metaWebhookUrl, 'fb_meta_url')"
                                        class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300"
                                    >
                                        <Check v-if="copiedKey === 'fb_meta_url'" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        <span>{{ copiedKey === 'fb_meta_url' ? 'تم النسخ' : 'نسخ الرابط' }}</span>
                                    </button>
                                </div>
                                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-[11px] font-mono text-blue-300 select-all break-all">
                                    {{ metaWebhookUrl }}
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1 font-bold text-slate-300">
                                    <span>رمز التحقق (Verify Token):</span>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(metaVerifyToken, 'fb_meta_token')"
                                        class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300"
                                    >
                                        <Check v-if="copiedKey === 'fb_meta_token'" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        <span>{{ copiedKey === 'fb_meta_token' ? 'تم النسخ' : 'نسخ الرمز' }}</span>
                                    </button>
                                </div>
                                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-[11px] font-mono text-emerald-400 select-all">
                                    {{ metaVerifyToken }}
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1 font-bold text-slate-300">
                                    <span>الحقول المشترك بها في Meta Webhook:</span>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(fbWebhookFields, 'fb_webhook_fields')"
                                        class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300"
                                    >
                                        <Check v-if="copiedKey === 'fb_webhook_fields'" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        <span>{{ copiedKey === 'fb_webhook_fields' ? 'تم النسخ' : 'نسخ الحقول' }}</span>
                                    </button>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-1 rounded bg-slate-900 border border-slate-800 font-mono text-xs text-blue-300">messages</span>
                                    <span class="px-2.5 py-1 rounded bg-slate-900 border border-slate-800 font-mono text-xs text-blue-300">messaging_postbacks</span>
                                    <span class="px-2.5 py-1 rounded bg-slate-900 border border-slate-800 font-mono text-xs text-blue-300">feed</span>
                                    <span class="text-[11px] text-slate-400">(messages للرسائل، feed للتعليقات والرد التلقائي)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================== INSTAGRAM GUIDE ================== -->
                <div v-else-if="selectedChannel === 'instagram'" class="space-y-6">
                    <!-- Overview Banner -->
                    <div class="p-4 rounded-xl bg-pink-950/30 border border-pink-800/40 text-pink-200 flex items-start gap-3">
                        <Info class="w-5 h-5 text-pink-400 shrink-0 mt-0.5" />
                        <div class="text-xs space-y-1">
                            <p class="font-bold">دليل ربط إنستجرام للأعمال الكامل:</p>
                            <p class="text-pink-300/90 leading-relaxed">
                                يشترط إنستجرام حساباً احترافياً مربوطاً بصفحة فيسبوك، وتوليد التوكن بصلاحيات إنستجرام، وتفعيل ويب هوك إنستجرام لاستقبال الرسائل والتعليقات والريلز.
                            </p>
                        </div>
                    </div>

                    <!-- Step 1: Prerequisites -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-600 text-white flex items-center justify-center text-xs font-black">1</span>
                            <h3 class="font-bold text-white text-sm">الشروط المسبقة لربط إنستجرام</h3>
                        </div>
                        <ol class="list-decimal list-inside space-y-2 text-xs text-slate-300 leading-relaxed">
                            <li>
                                يجب أن يكون حساب إنستجرام حساباً احترافياً (<span class="text-white font-bold font-mono">Business</span> أو <span class="text-white font-bold font-mono">Creator</span>).
                            </li>
                            <li>
                                يجب أن يكون حساب إنستجرام <strong class="text-white">مربوطاً بصفحة فيسبوك</strong> (من إعدادات تطبيق إنستجرام &gt; الحسابات المرتبطة Linked Accounts، أو من إعدادات صفحة الفيسبوك &gt; Linked Accounts).
                            </li>
                            <li>
                                في تطبيق إنستجرام على الهاتف: ادخل إلى الإعدادات والخصوصية &gt; الرسائل والردود على القصص &gt; أدوات التحكم في الرسائل &gt; تأكد من تفعيل <span class="text-pink-400 font-bold">السماح بالوصول إلى الرسائل (Allow access to messages)</span>.
                            </li>
                        </ol>
                    </div>

                    <!-- Step 2: Extract Instagram Business ID & Permanent Token via Graph API Explorer -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-600 text-white flex items-center justify-center text-xs font-black">2</span>
                            <h3 class="font-bold text-white text-sm">استخراج معرّف إنستجرام (Instagram Business ID) والتوكن الدائم</h3>
                        </div>

                        <ol class="list-decimal list-inside space-y-2.5 text-xs text-slate-300 leading-relaxed">
                            <li>
                                افتح أداة
                                <a href="https://developers.facebook.com/tools/explorer" target="_blank" class="text-pink-400 hover:text-pink-300 underline font-bold inline-flex items-center gap-1 mx-1">
                                    <span>Meta Graph API Explorer</span>
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                            </li>
                            <li>
                                اختر تطبيقك من قائمة <span class="text-white font-bold font-mono">Meta App</span>.
                            </li>
                            <li>
                                في قسم <span class="text-white font-bold">Permissions</span>: أضف صلاحيات إنستجرام والصفحة المطلوبة لإدارة الرسائل والتعليقات:
                            </li>
                        </ol>

                        <!-- Instagram Permissions Badges Grid -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-bold text-slate-300">صلاحيات إنستجرام والصفحة المطلوبة:</label>
                                <button
                                    type="button"
                                    @click="copyToClipboard(igPermissions, 'ig_all_perms')"
                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-pink-400 hover:text-pink-300 transition"
                                >
                                    <Check v-if="copiedKey === 'ig_all_perms'" class="w-3.5 h-3.5 text-emerald-400" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ copiedKey === 'ig_all_perms' ? 'تم نسخ جميع الصلاحيات' : 'نسخ جميع الصلاحيات دفعة واحدة' }}</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div
                                    v-for="perm in igPermissionsList"
                                    :key="perm.key"
                                    class="flex items-center justify-between p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-xs hover:border-slate-700 transition"
                                >
                                    <div class="min-w-0 pr-1">
                                        <span class="font-mono font-bold text-pink-400 block truncate">{{ perm.key }}</span>
                                        <span class="text-[11px] text-slate-400 block truncate">{{ perm.desc }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(perm.key, 'ig_perm_' + perm.key)"
                                        class="cursor-pointer shrink-0 p-1.5 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition ms-2"
                                        :title="'نسخ ' + perm.key"
                                    >
                                        <Check v-if="copiedKey === 'ig_perm_' + perm.key" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <ol start="4" class="list-decimal list-inside space-y-2.5 text-xs text-slate-300 leading-relaxed pt-1">
                            <li>
                                اضغط <span class="text-white font-bold bg-pink-600 px-2 py-0.5 rounded text-[11px]">Generate Access Token</span>، وتأكد في نافذة الموافقة من <strong class="text-white">تحديد صفحة الفيسبوك وحساب إنستجرام المرتبط بها معاً</strong>.
                            </li>
                            <li>
                                في شريط الاستعلام بالأعلى بجوار زر <span class="text-emerald-400 font-mono font-bold">GET</span>، الصق المسار التالي ثم اضغط <span class="text-white font-bold bg-pink-600 px-2 py-0.5 rounded text-[11px]">Submit</span>:
                            </li>
                        </ol>

                        <!-- Path Badge with Copy -->
                        <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span class="font-bold text-slate-200">مسار الاستعلام لجلب معرف إنستجرام والتوكن الدائم:</span>
                                <button
                                    type="button"
                                    @click="copyToClipboard(igQuery, 'ig_query_btn')"
                                    class="inline-flex items-center gap-1 text-pink-400 hover:text-pink-300 font-bold"
                                >
                                    <Check v-if="copiedKey === 'ig_query_btn'" class="w-3.5 h-3.5 text-emerald-400" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ copiedKey === 'ig_query_btn' ? 'تم النسخ' : 'نسخ المسار' }}</span>
                                </button>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-950 font-mono text-xs text-pink-300 select-all break-all border border-slate-800">
                                {{ igQuery }}
                            </div>
                            <div class="text-[11px] text-slate-400 leading-relaxed space-y-1">
                                <p>
                                    سيعرض لك Meta في مربع الاستجابة بالأسفل بيانات الحساب، انسخ منها التالي:
                                </p>
                                <ul class="list-disc list-inside ps-2 space-y-0.5 text-slate-300">
                                    <li>قيمة <code class="text-pink-400 font-mono font-bold">"id"</code> داخل قسم <code class="text-white font-mono">instagram_business_account</code>: ضعها في خانة <strong>Instagram Business Account ID</strong>.</li>
                                    <li>قيمة <code class="text-emerald-400 font-mono font-bold">"access_token"</code> الخاصة بالصفحة: ضعها في خانة <strong>Instagram Access Token</strong>.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Permanent Token Guarantee Callout -->
                        <div class="p-3 rounded-lg bg-emerald-950/30 border border-emerald-800/40 text-emerald-300 text-xs flex items-start gap-2.5">
                            <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                            <div class="space-y-0.5 leading-relaxed">
                                <p class="font-bold text-emerald-200">توكن دائم لا ينتهي أبداً:</p>
                                <p class="text-[11px] text-emerald-300/90">
                                    التوكن الناتج عن هذا الاستعلام دائم ولا يتطلب أي خطوات تمديد إضافية، ويعمل على إدارة رسائل وتعليقات إنستجرام بسلاسة كاملة.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Instagram Webhooks -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-600 text-white flex items-center justify-center text-xs font-black">3</span>
                            <h3 class="font-bold text-white text-sm">تفعيل استقبال الرسائل والتعليقات (Webhooks) لإنستجرام</h3>
                        </div>

                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 text-xs text-slate-300 space-y-2 leading-relaxed">
                            <p class="text-slate-300 font-medium">
                                💡 <strong class="text-white">خطوات ضبط ويب هوك إنستجرام في تطبيقك على Meta:</strong>
                            </p>
                            <ol class="list-decimal list-inside space-y-1.5 text-slate-400 text-[11px] pt-1">
                                <li>
                                    من لوحة تحكم تطبيقك في <a href="https://developers.facebook.com/apps" target="_blank" class="text-pink-400 underline font-bold inline-flex items-center gap-0.5">Meta for Developers <ExternalLink class="w-2.5 h-2.5" /></a> &gt; اضغط على <span class="text-white font-bold">Webhooks</span> من القائمة الجانبية.
                                </li>
                                <li>
                                    من القائمة المنسدلة في أعلى الصفحة (التي تختار نوع الكائن): اختر <strong class="text-pink-400 font-bold font-mono">Instagram</strong> (وليس Page).
                                </li>
                                <li>
                                    اضغط على <span class="text-white font-bold">Add callback URL</span> أو <span class="text-white font-bold">Edit callback URL</span>، وأدخل الرابط ورمز التحقق الموضحين أدناه ثم اضغط <span class="text-emerald-400 font-bold">Verify and save</span>.
                                </li>
                                <li>
                                    في جدول حقول إنستجرام بالأسفل، فعّل الاشتراك (Subscribe) في الحقول: <code class="text-pink-300 font-mono font-bold">messages</code> و <code class="text-pink-300 font-mono font-bold">comments</code> و <code class="text-pink-300 font-mono font-bold">live_comments</code>.
                                </li>
                            </ol>
                        </div>

                        <!-- Webhook Credentials Box -->
                        <div class="space-y-2.5">
                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1 font-bold text-slate-300">
                                    <span>عنوان الويب هوك (Callback URL):</span>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(metaWebhookUrl, 'ig_meta_url')"
                                        class="inline-flex items-center gap-1 text-pink-400 hover:text-pink-300"
                                    >
                                        <Check v-if="copiedKey === 'ig_meta_url'" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        <span>{{ copiedKey === 'ig_meta_url' ? 'تم النسخ' : 'نسخ الرابط' }}</span>
                                    </button>
                                </div>
                                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-[11px] font-mono text-pink-300 select-all break-all">
                                    {{ metaWebhookUrl }}
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1 font-bold text-slate-300">
                                    <span>رمز التحقق (Verify Token):</span>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(metaVerifyToken, 'ig_meta_token')"
                                        class="inline-flex items-center gap-1 text-pink-400 hover:text-pink-300"
                                    >
                                        <Check v-if="copiedKey === 'ig_meta_token'" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        <span>{{ copiedKey === 'ig_meta_token' ? 'تم النسخ' : 'نسخ الرمز' }}</span>
                                    </button>
                                </div>
                                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-[11px] font-mono text-emerald-400 select-all">
                                    {{ metaVerifyToken }}
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1 font-bold text-slate-300">
                                    <span>الحقول المشترك بها في Instagram Webhooks:</span>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(igWebhookFields, 'ig_webhook_fields')"
                                        class="inline-flex items-center gap-1 text-pink-400 hover:text-pink-300"
                                    >
                                        <Check v-if="copiedKey === 'ig_webhook_fields'" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        <span>{{ copiedKey === 'ig_webhook_fields' ? 'تم النسخ' : 'نسخ الحقول' }}</span>
                                    </button>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-1 rounded bg-slate-900 border border-slate-800 font-mono text-xs text-pink-300">messages</span>
                                    <span class="px-2.5 py-1 rounded bg-slate-900 border border-slate-800 font-mono text-xs text-pink-300">comments</span>
                                    <span class="px-2.5 py-1 rounded bg-slate-900 border border-slate-800 font-mono text-xs text-pink-300">live_comments</span>
                                    <span class="text-[11px] text-slate-400">(messages للدايركت DM، comments لتعليقات البوستات والريلز)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================== WHATSAPP GUIDE ================== -->
                <div v-else-if="selectedChannel === 'whatsapp'" class="space-y-6">
                    <!-- Overview Banner -->
                    <div class="p-4 rounded-xl bg-emerald-950/30 border border-emerald-800/40 text-emerald-200 flex items-start gap-3">
                        <MessageCircle class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                        <div class="text-xs space-y-1">
                            <p class="font-bold">ربط رقم واتساب عبر كود الاقتران الرقمي:</p>
                            <p class="text-emerald-300/90 leading-relaxed">
                                يمكنك ربط رقم الواتساب الخاص بك بكل سهولة باستخدام كود الربط الرقمي المكون من 8 خانات مباشرة من هاتفك بدون الحاجة لكاميرا.
                            </p>
                        </div>
                    </div>

                    <!-- Pairing Code Guide -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="flex items-center gap-2">
                            <Smartphone class="w-5 h-5 text-emerald-400" />
                            <h3 class="font-bold text-white text-sm">خطوات ربط الحساب بكود الاقتران</h3>
                        </div>
                        <ol class="list-decimal list-inside space-y-2 text-xs text-slate-300 leading-relaxed">
                            <li>أدخل رقم الهاتف مسبوقاً بمفتاح الدولة بدون أي أصفار في البداية (مثال لمصر: <code class="text-emerald-400 font-mono">201012345678</code> أو للسعودية: <code class="text-emerald-400 font-mono">9665xxxxxxxx</code>).</li>
                            <li>اضغط على زر <span class="text-blue-400 font-bold">الحصول على كود الربط</span> وسيظهر لك كود مكون من 8 خانات فوراً.</li>
                            <li>افتح تطبيق واتساب على هاتفك &gt; اذهب إلى <span class="text-white font-bold">الإعدادات</span> (أو القائمة ⋮ ثلاث نقاط في أندرويد) &gt; <span class="text-emerald-400 font-bold">الأجهزة المرتبطة (Linked Devices)</span>.</li>
                            <li>اضغط على <span class="text-white font-bold">ربط جهاز (Link a device)</span> ثم اختر بالأسفل <span class="text-white font-bold">الربط برقم الهاتف بدلاً من ذلك (Link with phone number instead)</span>.</li>
                            <li>اكتب الكود الظاهر على شاشة فيرتكس ليتم تأكيد الاتصال وتفعيل القناة فوراً.</li>
                        </ol>
                    </div>
                </div>

                <!-- ================== TELEGRAM GUIDE ================== -->
                <div v-else-if="selectedChannel === 'telegram'" class="space-y-6">
                    <!-- Overview Banner -->
                    <div class="p-4 rounded-xl bg-sky-950/30 border border-sky-800/40 text-sky-200 flex items-start gap-3">
                        <Send class="w-5 h-5 text-sky-400 shrink-0 mt-0.5" />
                        <div class="text-xs space-y-1">
                            <p class="font-bold">ربط تيليجرام خلال دقيقة واحدة:</p>
                            <p class="text-sky-300/90 leading-relaxed">
                                تحتاج فقط إلى توكن بوت تيليجرام (Telegram Bot Token) الذي يمنحه لك بوت BotFather الرسمي.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-sky-600 text-white flex items-center justify-center text-xs font-black">1</span>
                            <h3 class="font-bold text-white text-sm">خطوات إنشاء البوت في تيليجرام</h3>
                        </div>

                        <ol class="list-decimal list-inside space-y-2 text-xs text-slate-300 leading-relaxed">
                            <li>
                                افتح تطبيق تيليجرام وابحث عن
                                <a href="https://t.me/BotFather" target="_blank" class="text-sky-400 font-bold underline inline-flex items-center gap-1 mx-1">
                                    <span>@BotFather</span>
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                                الرسمي (الموثق بعلامة زرقاء).
                            </li>
                            <li>
                                أرسل الأمر التالي لبدء إنشاء بوت جديد:
                                <div class="mt-1 flex items-center gap-2">
                                    <code class="px-2.5 py-1 rounded bg-slate-900 border border-slate-800 text-sky-400 font-mono font-bold">/newbot</code>
                                    <button
                                        type="button"
                                        @click="copyToClipboard('/newbot', 'tg_cmd')"
                                        class="text-[11px] text-slate-400 hover:text-white inline-flex items-center gap-1"
                                    >
                                        <Check v-if="copiedKey === 'tg_cmd'" class="w-3 h-3 text-emerald-400" />
                                        <Copy v-else class="w-3 h-3" />
                                        <span>نسخ</span>
                                    </button>
                                </div>
                            </li>
                            <li>سيطلب منك إدخال <span class="text-white font-bold">الاسم الظاهر للبوت</span> (مثال: <span class="text-slate-400">خدمة عملاء متجر فيرتكس</span>).</li>
                            <li>ثم سيطلب إدخال <span class="text-white font-bold">اسم المستخدم (Username)</span> للبوت ويجب أن ينتهي بكلمة <code class="text-sky-400">bot</code> (مثال: <code class="text-slate-400">vertex_store_bot</code>).</li>
                            <li>
                                سيرسل لك BotFather رسالة تتضمن التوكن الخاص بالـ HTTP API بالشكل التالي:
                                <div class="mt-1 p-2 rounded bg-slate-900 border border-slate-800 text-slate-400 font-mono text-[11px]">
                                    7123456789:AAH7Xxxxx...
                                </div>
                            </li>
                            <li>انسخ هذا التوكن والصقه في خانة رمز البوت في بطاقة تيليجرام هنا، واضغط على زر <span class="text-blue-400 font-bold">اختبار التوكن وتفعيل البوت</span>.</li>
                        </ol>

                        <div class="p-3 rounded-lg bg-emerald-950/20 border border-emerald-800/40 text-emerald-300 text-xs flex items-center gap-2">
                            <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>يقوم فيرتكس بضبط الـ Webhook تلقائياً وبدء استقبال الرسائل فور إدخال التوكن الصحيح.</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="shrink-0 px-6 py-4 border-t border-slate-800 bg-slate-950/70 flex items-center justify-between">
                <span class="text-xs text-slate-400">
                    متجر: <span class="text-white font-bold">{{ business.name }}</span>
                </span>
                <button
                    type="button"
                    @click="closeModal"
                    class="cursor-pointer px-5 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white transition shadow-sm"
                >
                    فهمت الخطوات، العودة للربط
                </button>
            </div>
        </div>
    </Modal>
</template>
