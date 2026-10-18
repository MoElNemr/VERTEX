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

const fbPermissions = 'pages_show_list, pages_read_engagement, pages_manage_metadata, pages_messaging, pages_manage_posts';
const igPermissions = 'instagram_basic, instagram_manage_messages, instagram_manage_comments, pages_show_list, pages_manage_metadata';
const igQuery = 'me?fields=instagram_business_account{id,username}';

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
                            <p class="font-bold">ما تحتاجه لربط صفحة فيسبوك في فيرتكس:</p>
                            <p class="text-blue-300/90 leading-relaxed">
                                تحتاج إلى معلومتين أساسيتين: <span class="text-white font-mono font-bold">Page ID</span> (معرف الصفحة) و <span class="text-white font-mono font-bold">Page Access Token</span> (رمز وصول الصفحة مع صلاحيات الرسائل والمحادثات).
                            </p>
                        </div>
                    </div>

                    <!-- Step 1: Page ID -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-black">1</span>
                            <h3 class="font-bold text-white text-sm">الحصول على معرف الصفحة (Page ID)</h3>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            افتح صفحة فيسبوك الخاصة بك &gt; ادخل قسم <span class="text-slate-200 font-bold">حول (About)</span> &gt; <span class="text-slate-200 font-bold">شفافية الصفحة (Page Transparency)</span>، أو من خلال إعدادات الأعمال، ستجد معرّف الصفحة المكون من أرقام (مثال: <code class="text-blue-400 font-mono">104523984578</code>).
                        </p>
                        <a
                            href="https://business.facebook.com/latest/settings/pages"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs text-blue-400 hover:text-blue-300 font-bold"
                        >
                            <span>فتح صفحات Meta Business Suite</span>
                            <ExternalLink class="w-3.5 h-3.5" />
                        </a>
                    </div>

                    <!-- Step 2: Access Token -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-black">2</span>
                            <h3 class="font-bold text-white text-sm">إنشاء رمز وصول الصفحة (Page Access Token)</h3>
                        </div>

                        <!-- System User Add Button Disabled Diagnostic Notice -->
                        <div class="p-3.5 rounded-xl bg-amber-950/30 border border-amber-800/50 text-amber-200 text-xs space-y-2">
                            <div class="flex items-start gap-2 font-bold text-amber-300">
                                <AlertTriangle class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" />
                                <span>تنبيه هام بخصوص زر (+ Add) في مستخدمو النظام (System Users):</span>
                            </div>
                            <p class="text-amber-200/90 leading-relaxed text-[11px]">
                                إذا دخلت إلى صفحة مستخدمو النظام ووجدت زر <span class="font-bold text-white bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700">+ Add</span> باللون الرمادي غير مفعل وتظهر رسالة:
                                <br />
                                <span class="font-mono text-amber-300">"In order to add a system user, an app must be part of this business portfolio"</span>
                                <br />
                                فهذا يعني أن محفظة أعمالك في ميتا لا تحتوي على تطبيق (App) مربوط بعد. وفرنا لك خيارين بالأسفل: <strong class="text-white">الطريقة الأولى (أسرع في دقيقة واحدة وبدون إنشاء تطبيق)</strong>، أو <strong class="text-white">الطريقة الثانية (طريقة فك قفل زر Add وإنشاء مستخدم نظام)</strong>.
                            </p>
                        </div>

                        <!-- METHOD 1: Graph API Explorer -->
                        <div class="p-4 rounded-xl bg-blue-950/20 border border-blue-800/40 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-xs font-bold text-blue-300">
                                    <Zap class="w-4 h-4 text-amber-400" />
                                    <span>الطريقة الأولى (الأسهل والأسرع - دقيقة واحدة بدون تطبيق): عبر Graph API Explorer</span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">موصى به للمتاجر</span>
                            </div>
                            <ol class="list-decimal list-inside space-y-2 text-xs text-slate-300 leading-relaxed">
                                <li>
                                    افتح أداة
                                    <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="text-blue-400 hover:text-blue-300 underline font-bold inline-flex items-center gap-1 mx-1">
                                        <span>Meta Graph API Explorer</span>
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                    (تأكد أن تطبيقك <code class="text-blue-300">vertex</code> محدد في خانة Meta App).
                                </li>
                                <li>
                                    في القائمة اليمين تحت <span class="text-white font-bold">User or Page</span>، اضغط على زر <span class="text-amber-300 font-bold bg-slate-900 px-1.5 py-0.5 rounded border border-slate-700">Get Token</span>، ثم اختر من القائمة <span class="text-white font-bold">Get Page Access Token</span>.
                                </li>
                                <li>
                                    ستفتح لك نافذة منبثقة من فيسبوك: اضغط "متابعة" ثم اختر صفحة فيسبوك الخاصة بك وضع عليها علامة صح واضغط "تم".
                                </li>
                                <li>
                                    سيظهر الآن اسم صفحتك مكان Get Token، وستمتلئ خانة <span class="text-white font-bold">Access Token</span> بكود التوكن فوراً! اضغط على أيقونة النسخ 📋 لنسخه.
                                </li>
                                <li>
                                    <span class="text-white font-bold">للحصول على معرف الصفحة (Page ID):</span> في نفس الشاشة، اضغط على زر <span class="text-white font-bold bg-blue-600 px-2 py-0.5 rounded text-[11px]">Submit</span> الأزرق في شريط الـ GET، وسيظهر لك في الصندوق على اليسار كود JSON يحتوي على <code class="text-blue-300">"id": "1045..."</code> انسخ هذا الرقم.
                                </li>
                                <li>
                                    <span class="text-white font-bold">إضافة الصلاحيات للتوكن:</span> تحت قسم Permissions، اضغط <span class="text-slate-200 font-bold">Add a Permission</span> وأضف:
                                    <code class="text-blue-400 font-mono">pages_messaging</code> و <code class="text-blue-400 font-mono">pages_show_list</code> و <code class="text-blue-400 font-mono">pages_read_engagement</code>، ثم اضغط على زر <span class="text-white font-bold bg-blue-600 px-2 py-0.5 rounded text-[11px]">Generate Access Token</span> ووافق عليها.
                                </li>
                                <li>
                                    <span class="text-amber-300 font-bold">تمديد التوكن ليصبح دائماً (Never Expires):</span>
                                    افتح
                                    <a href="https://developers.facebook.com/tools/debug/accesstoken/" target="_blank" class="text-blue-400 hover:text-blue-300 underline font-bold inline-flex items-center gap-1 mx-1">
                                        <span>أداة Access Token Debugger</span>
                                        <ExternalLink class="w-3 h-3" />
                                    </a>
                                    ثم الصق التوكن واضغط <span class="text-white font-bold">Debug</span>، وانزل لأسفل واضغط <span class="text-emerald-400 font-bold">Extend Access Token</span> للحصول على توكن دائم لا ينتهي.
                                </li>
                            </ol>
                        </div>

                        <!-- METHOD 2: System User -->
                        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-200">
                                <ShieldCheck class="w-4 h-4 text-emerald-400" />
                                <span>الطريقة الثانية: مستخدم النظام الدائم (System User Token)</span>
                            </div>

                            <div class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-[11px] text-slate-300 space-y-1.5 leading-relaxed">
                                <p class="font-bold text-emerald-400">خطوة فك قفل زر (+ Add) المعطل:</p>
                                <ol class="list-decimal list-inside space-y-1 text-slate-400">
                                    <li>افتح <a href="https://business.facebook.com/latest/settings/apps" target="_blank" class="text-blue-400 underline font-bold">إعدادات الأعمال &gt; الحسابات &gt; التطبيقات (Apps)</a>.</li>
                                    <li>اضغط على <span class="text-white font-bold">إضافة (Add)</span> &gt; <span class="text-white font-bold">ربط معرّف تطبيق (Connect an App ID)</span>، واكتب معرّف تطبيقك (إذا لم يكن لديك تطبيق، يمكنك إنشاء تطبيق مجاني بنوع Business خلال دقيقة عبر <a href="https://developers.facebook.com/apps" target="_blank" class="text-blue-400 underline font-bold">Meta Developers</a>).</li>
                                    <li>بمجرد ربط التطبيق، سيتفعل زر <span class="text-white font-bold">+ Add</span> في صفحة مستخدمو النظام فوراً.</li>
                                </ol>
                            </div>

                            <ol class="list-decimal list-inside space-y-1.5 text-xs text-slate-300 leading-relaxed">
                                <li>افتح <a href="https://business.facebook.com/settings/system-users" target="_blank" class="text-emerald-400 underline font-bold">مستخدمو النظام (System Users)</a> واضغط <span class="text-white font-bold">+ Add</span>.</li>
                                <li>اكتب اسماً للمستخدم (مثال: <code class="text-slate-300">vertex_bot</code>) واختر الدور <span class="text-white font-bold">مسؤول (Admin)</span>.</li>
                                <li>اضغط على <span class="text-white font-bold">إضافة أصول (Add Assets)</span>، اختر صفحة الفيسبوك، وفعّل خيار <span class="text-white font-bold">التحكم الكامل (Full Control)</span>.</li>
                                <li>اضغط على <span class="text-white font-bold">إنشاء رمز جديد (Generate New Token)</span>، اختر تطبيقك وحدد الصلاحية <span class="text-emerald-400 font-bold">Never (دائم بدون انتهاء)</span>، واختر الصلاحيات وانسخ التوكن.</li>
                            </ol>
                        </div>

                        <!-- Permissions Box -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-bold text-slate-300">الصلاحيات المطلوبة للتوكن (Permissions):</label>
                                <button
                                    type="button"
                                    @click="copyToClipboard(fbPermissions, 'fb_perm')"
                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-400 hover:text-blue-300 transition"
                                >
                                    <Check v-if="copiedKey === 'fb_perm'" class="w-3.5 h-3.5 text-emerald-400" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ copiedKey === 'fb_perm' ? 'تم نسخ الصلاحيات' : 'نسخ الصلاحيات' }}</span>
                                </button>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-[11px] font-mono text-slate-300 break-all select-all">
                                {{ fbPermissions }}
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Webhook info -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-black">3</span>
                            <h3 class="font-bold text-white text-sm">بيانات الـ Webhook (لاستقبال الرسائل والتعليقات فورياً)</h3>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            يقوم نظام فيرتكس بالاشتراك في الـ Webhook تلقائياً عند حفظ التوكن. إذا أردت التحقق أو ضبط تطبيقك المخصص في Meta Developers:
                        </p>

                        <div class="space-y-2">
                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1 font-bold text-slate-300">
                                    <span>عنوان الويب هوك (Callback URL):</span>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(metaWebhookUrl, 'meta_url')"
                                        class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300"
                                    >
                                        <Check v-if="copiedKey === 'meta_url'" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        <span>{{ copiedKey === 'meta_url' ? 'تم النسخ' : 'نسخ الرابط' }}</span>
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
                                        @click="copyToClipboard(metaVerifyToken, 'meta_token')"
                                        class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300"
                                    >
                                        <Check v-if="copiedKey === 'meta_token'" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        <span>{{ copiedKey === 'meta_token' ? 'تم النسخ' : 'نسخ الرمز' }}</span>
                                    </button>
                                </div>
                                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-[11px] font-mono text-emerald-400 select-all">
                                    {{ metaVerifyToken }}
                                </div>
                            </div>

                            <p class="text-[11px] text-slate-500">الحقول المشترك بها في Meta Webhook: <code class="text-slate-300">messages, messaging_postbacks, message_reads, feed</code></p>
                        </div>
                    </div>
                </div>

                <!-- ================== INSTAGRAM GUIDE ================== -->
                <div v-else-if="selectedChannel === 'instagram'" class="space-y-6">
                    <!-- Overview Banner -->
                    <div class="p-4 rounded-xl bg-pink-950/30 border border-pink-800/40 text-pink-200 flex items-start gap-3">
                        <Info class="w-5 h-5 text-pink-400 shrink-0 mt-0.5" />
                        <div class="text-xs space-y-1">
                            <p class="font-bold">شروط ربط إنستجرام بنجاح:</p>
                            <p class="text-pink-300/90 leading-relaxed">
                                يتطلب إنستجرام أن يكون الحساب <span class="text-white font-bold">حساب أعمال (Business Account)</span> ومرتبطاً بنفس صفحة الفيسبوك داخل Meta Business Suite، بالإضافة إلى تفعيل ميزة السماح بالوصول للرسائل من تطبيق إنستجرام على الهاتف.
                            </p>
                        </div>
                    </div>

                    <!-- Step 1: Pre-requisites in Instagram App -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-600 text-white flex items-center justify-center text-xs font-black">1</span>
                            <h3 class="font-bold text-white text-sm">إعداد الحساب في تطبيق إنستجرام على الهاتف (إلزامي)</h3>
                        </div>
                        <ol class="list-decimal list-inside space-y-1.5 text-xs text-slate-300 leading-relaxed">
                            <li>افتح تطبيق إنستجرام &gt; الإعدادات والخصوصية &gt; نوع الحساب والأدوات &gt; قم بالتبديل إلى <span class="text-white font-bold">حساب احترافي / تجاري (Professional/Business)</span>.</li>
                            <li>ادخل إلى <span class="text-white font-bold">الرسائل والردود على القصص (Messages and story replies)</span>.</li>
                            <li>اختر <span class="text-white font-bold">أدوات التحكم في الرسائل (Message controls)</span>.</li>
                            <li>تأكد من تفعيل زر <span class="text-pink-400 font-bold">السماح بالوصول إلى الرسائل (Allow access to messages)</span> تحت الأدوات المتصلة، حتى يستطيع فيرتكس استقبال والرد على الرسائل.</li>
                        </ol>
                    </div>

                    <!-- Step 2: Link IG with FB Page in Meta Business Suite -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-600 text-white flex items-center justify-center text-xs font-black">2</span>
                            <h3 class="font-bold text-white text-sm">ربط حساب إنستجرام بصفحة فيسبوك</h3>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            يجب أن يكون حساب إنستجرام مربوطاً بصفحة الفيسبوك داخل Meta Business Suite حتى تمنح Meta الصلاحيات للتطبيقات:
                        </p>
                        <a
                            href="https://business.facebook.com/latest/settings/instagram_account"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs text-pink-400 hover:text-pink-300 font-bold"
                        >
                            <span>فتح حسابات إنستجرام في Meta Business Suite</span>
                            <ExternalLink class="w-3.5 h-3.5" />
                        </a>
                        <p class="text-[11px] text-slate-500">إذا لم تجد الحساب مربوطاً، اضغط "إضافة (Add)" وسجل دخول بحساب إنستجرام لربطه بصفحتك.</p>
                    </div>

                    <!-- Step 3: Instagram Business Account ID -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-600 text-white flex items-center justify-center text-xs font-black">3</span>
                            <h3 class="font-bold text-white text-sm">الحصول على معرف حساب إنستجرام للأعمال (Account ID)</h3>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            معرف حساب إنستجرام للأعمال هو رقم مكون من 15 إلى 17 خانة. يمكنك الحصول عليه بإحدى طريقتين:
                        </p>

                        <div class="space-y-3">
                            <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 text-xs text-slate-300 space-y-1">
                                <span class="font-bold text-white">الطريقة الأولى (مباشرة من Meta Business Suite):</span>
                                <p class="text-slate-400 text-[11px]">
                                    افتح رابط
                                    <a href="https://business.facebook.com/latest/settings/instagram_account" target="_blank" class="text-pink-400 underline font-bold">حسابات إنستجرام</a>،
                                    واضغط على اسم حسابك، ستجد رقم المعرّف (ID) مكتوباً أسفل اسم الحساب مباشرة (مثال: <code class="text-pink-400 font-mono">17841400123456789</code>).
                                </p>
                            </div>

                            <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 text-xs text-slate-300 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-white">الطريقة الثانية (عبر أداة Graph API Explorer):</span>
                                    <button
                                        type="button"
                                        @click="copyToClipboard(igQuery, 'ig_query')"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold text-pink-400 hover:text-pink-300 transition"
                                    >
                                        <Check v-if="copiedKey === 'ig_query'" class="w-3.5 h-3.5 text-emerald-400" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        <span>{{ copiedKey === 'ig_query' ? 'تم النسخ' : 'نسخ الاستعلام' }}</span>
                                    </button>
                                </div>
                                <p class="text-slate-400 text-[11px]">
                                    افتح أداة <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="text-pink-400 underline font-bold">Graph API Explorer</a>، اختر صفحتك، واكتب هذا الاستعلام ثم اضغط Submit:
                                </p>
                                <div class="p-2 rounded bg-slate-950 font-mono text-[11px] text-pink-300 select-all">
                                    {{ igQuery }}
                                </div>
                                <p class="text-[11px] text-slate-500">سيظهر لك الـ id التابع لحساب إنستجرام للأعمال داخل استجابة الـ API فوراً.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Access Token -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-600 text-white flex items-center justify-center text-xs font-black">4</span>
                            <h3 class="font-bold text-white text-sm">رمز وصول إنستجرام (Instagram Access Token)</h3>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            رمز وصول إنستجرام هو <span class="text-white font-bold">نفس رمز وصول صفحة الفيسبوك (Page Access Token)</span> المربوطة بحساب إنستجرام (الذي تم استخراجه في خطوة فيسبوك أعلاه سواء عبر Graph API Explorer أو System User)، مع التأكد من تضمين صلاحيات إنستجرام التالية:
                        </p>

                        <!-- Required Permissions Box -->
                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-bold text-slate-300">صلاحيات إنستجرام المطلوبة للتوكن:</label>
                                <button
                                    type="button"
                                    @click="copyToClipboard(igPermissions, 'ig_perm')"
                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-pink-400 hover:text-pink-300 transition"
                                >
                                    <Check v-if="copiedKey === 'ig_perm'" class="w-3.5 h-3.5 text-emerald-400" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ copiedKey === 'ig_perm' ? 'تم نسخ الصلاحيات' : 'نسخ الصلاحيات' }}</span>
                                </button>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-[11px] font-mono text-slate-300 break-all select-all">
                                {{ igPermissions }}
                            </div>
                        </div>

                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 text-[11px] text-slate-400 flex items-start gap-2">
                            <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                            <span>بعد وضع المعرف والتوكن في بطاقة إنستجرام والضغط على زر الربط، سيقوم فيرتكس بفحص المعرف وجلب اسم الحساب وتفعيل المحادثات والرد الآلي فوراً.</span>
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
