<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import {
    Repeat,
    Send,
    Users,
    Clock,
    ShieldAlert,
    ShieldCheck,
    CheckCircle2,
    AlertCircle,
    Play,
    Pause,
    Trash2,
    Plus,
    X,
    Filter,
    RefreshCw,
    Sliders,
    MessageSquare,
    Layers,
    ChevronDown,
    Zap,
    ExternalLink,
    Wallet,
    Coins,
} from 'lucide-vue-next';

const props = defineProps({
    business: {
        type: Object,
        default: () => ({ name: 'المتجر' }),
    },
    campaigns: {
        type: Object,
        default: () => ({ data: [] }),
    },
    wallet: {
        type: Object,
        default: () => ({ credits: 0, balance: 0 }),
    },
    messagePricing: {
        type: Object,
        default: () => ({
            retargeting: { price: 0.25, credit_cost: 1, currency: 'EGP' },
            bulk: { price: 0.20, credit_cost: 1, currency: 'EGP' },
        }),
    },
    audience: {
        type: Object,
        default: () => ({
            total: 0,
            by_platform: { whatsapp: 0, facebook: 0, instagram: 0, telegram: 0 },
            available_tags: [],
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_campaigns: 0,
            active_campaigns: 0,
            total_messages_sent: 0,
        }),
    },
});

const isCreateModalOpen = ref(false);
const activeDetailModal = ref(null);
const pollingInterval = ref(null);
const isCustomPerPlatform = ref(false);

// Form for creating a new retargeting campaign
const form = useForm({
    name: '',
    platforms: ['whatsapp', 'facebook', 'instagram', 'telegram'],
    message: 'مرحباً {name}، يسعدنا تواصلك معنا دائماً! لدينا عرض خاص وحصري لك اليوم.',
    platform_messages: {
        whatsapp: '',
        facebook: '',
        instagram: '',
        telegram: '',
    },
    interval_seconds: 10,
    jitter_seconds: 2,
    tags: [],
    auto_start: true,
});

const platformsConfig = [
    { key: 'whatsapp', name: 'واتساب', color: 'text-emerald-400', badge: 'bg-emerald-500/10 border-emerald-500/20' },
    { key: 'facebook', name: 'فيسبوك', color: 'text-blue-400', badge: 'bg-blue-500/10 border-blue-500/20' },
    { key: 'instagram', name: 'إنستجرام', color: 'text-pink-400', badge: 'bg-pink-500/10 border-pink-500/20' },
    { key: 'telegram', name: 'تيليجرام', color: 'text-sky-400', badge: 'bg-sky-500/10 border-sky-500/20' },
];

const togglePlatform = (key) => {
    const idx = form.platforms.indexOf(key);
    if (idx > -1) {
        if (form.platforms.length > 1) {
            form.platforms.splice(idx, 1);
        }
    } else {
        form.platforms.push(key);
    }
};

const selectAllPlatforms = () => {
    form.platforms = ['whatsapp', 'facebook', 'instagram', 'telegram'];
};

const insertTag = (tag) => {
    form.message += ` ${tag}`;
};

const submitCampaign = () => {
    if (!form.name) {
        form.name = `حملة إعادة استهداف - ${new Date().toLocaleDateString('ar-EG')}`;
    }

    form.post(route('retargeting.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
    });
};

// Campaign Actions: Start, Pause, Step, Delete
const isActionLoading = ref({});

const startCampaign = async (campaign) => {
    isActionLoading.value[campaign.id] = true;
    try {
        await axios.post(route('retargeting.start', campaign.id));
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('حدث خطأ أثناء بدء الحملة.');
    } finally {
        isActionLoading.value[campaign.id] = false;
    }
};

const pauseCampaign = async (campaign) => {
    isActionLoading.value[campaign.id] = true;
    try {
        await axios.post(route('retargeting.pause', campaign.id));
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('حدث خطأ أثناء إيقاف الحملة.');
    } finally {
        isActionLoading.value[campaign.id] = false;
    }
};

const stepCampaign = async (campaign) => {
    isActionLoading.value[campaign.id] = true;
    try {
        const res = await axios.post(route('retargeting.step', campaign.id));
        if (activeDetailModal.value && activeDetailModal.value.id === campaign.id) {
            activeDetailModal.value = res.data.campaign;
        }
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('حدث خطأ أثناء معالجة الخطوة.');
    } finally {
        isActionLoading.value[campaign.id] = false;
    }
};

const deleteCampaign = (campaign) => {
    if (!confirm(`هل أنت متأكد من حذف حملة "${campaign.name}"؟`)) return;
    router.delete(route('retargeting.destroy', campaign.id), { preserveScroll: true });
};

// Open details modal
const openDetails = async (campaign) => {
    activeDetailModal.value = campaign;
    try {
        const res = await axios.get(route('retargeting.show', campaign.id));
        activeDetailModal.value = res.data.campaign;
        activeDetailModal.value.recent_recipients = res.data.recent_recipients;
        activeDetailModal.value.progress_percent = res.data.progress_percent;
    } catch (e) {}
};

// Auto poll active campaigns every 5s
onMounted(() => {
    pollingInterval.value = setInterval(() => {
        const hasProcessing = props.campaigns.data?.some(c => c.status === 'processing');
        if (hasProcessing) {
            router.reload({ preserveScroll: true, only: ['campaigns', 'stats'] });
        }
    }, 5000);
});

onUnmounted(() => {
    if (pollingInterval.value) clearInterval(pollingInterval.value);
});
</script>

<template>
    <Head :title="`إعادة الاستهداف - ${business.name}`" />

    <AuthenticatedLayout>
        <!-- Header -->
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-bold text-slate-100 tracking-tight font-sans">
                            محرك إعادة الاستهداف (Retargeting)
                        </h1>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-mono font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            {{ business.name }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5 font-sans">
                        إرسال رسائل مخصصة لعملاء المتجر عبر منصات التواصل الأربعة بجدولة زمنية ذكية لحماية الحسابات من الحظر
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="isCreateModalOpen = true"
                        class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition shadow-sm"
                    >
                        <Plus class="w-4 h-4" />
                        <span>إنشاء حملة إعادة استهداف جديدة</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-5">
            <!-- 0. WALLET BALANCE & PRICING BANNER -->
            <div class="p-4 rounded-xl bg-gradient-to-r from-blue-950/40 via-slate-900/90 to-purple-950/40 border border-blue-500/30 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold shrink-0">
                        <Wallet class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-xs font-bold text-white">رصيد رسائل إعادة الاستهداف (خارج الباقات)</h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                يتم الخصم من المحفظة
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            سعر رسالة إعادة الاستهداف:
                            <span class="font-mono font-bold text-amber-300">{{ messagePricing?.retargeting?.price || 0.25 }} ج.م</span>
                            ({{ messagePricing?.retargeting?.credit_cost || 1 }} رسالة/نقطة لكل عميل)
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                    <div class="text-end">
                        <div class="text-[10px] text-slate-400">الرصيد المتاح</div>
                        <div class="font-mono text-lg font-bold text-emerald-400">{{ wallet?.credits || 0 }} <span class="text-xs text-slate-300 font-sans">رسالة</span></div>
                    </div>
                    <Link
                        :href="route('wallet.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-sm"
                    >
                        <Coins class="w-3.5 h-3.5" />
                        <span>شحن المحفظة</span>
                    </Link>
                </div>
            </div>

            <!-- 1. AUDIENCE & BAN-PREVENTION STATS ROW -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- Stat 1: Total Reachable Contacts -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">إجمالي جمهور الاستهداف</span>
                        <div class="p-1 rounded bg-slate-800 text-blue-400 border border-slate-700/60">
                            <Users class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-2xl font-bold font-mono text-white tracking-tight">{{ audience.total }}</span>
                        <span class="text-[11px] text-slate-500">عميل مسجل</span>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[10px] text-slate-400">
                        <span>جاهزون لإعادة الاستهداف عبر 4 منصات</span>
                    </div>
                </div>

                <!-- Stat 2: WhatsApp & Telegram Audience -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">واتساب وتيليجرام</span>
                        <div class="p-1 rounded bg-slate-800 text-emerald-400 border border-slate-700/60">
                            <Send class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-2xl font-bold font-mono text-white tracking-tight">
                            {{ (audience.by_platform.whatsapp || 0) + (audience.by_platform.telegram || 0) }}
                        </span>
                        <span class="text-[11px] text-slate-500">محادثات مباشرة</span>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[10px]">
                        <span class="text-emerald-400 font-mono">{{ audience.by_platform.whatsapp || 0 }} واتساب</span>
                        <span class="text-slate-600">•</span>
                        <span class="text-sky-400 font-mono">{{ audience.by_platform.telegram || 0 }} تيليجرام</span>
                    </div>
                </div>

                <!-- Stat 3: Meta Messenger & Instagram -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">فيسبوك وإنستجرام</span>
                        <div class="p-1 rounded bg-slate-800 text-pink-400 border border-slate-700/60">
                            <Layers class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-2xl font-bold font-mono text-white tracking-tight">
                            {{ (audience.by_platform.facebook || 0) + (audience.by_platform.instagram || 0) }}
                        </span>
                        <span class="text-[11px] text-slate-500">عميل ميتا</span>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[10px]">
                        <span class="text-blue-400 font-mono">{{ audience.by_platform.facebook || 0 }} فيسبوك</span>
                        <span class="text-slate-600">•</span>
                        <span class="text-pink-400 font-mono">{{ audience.by_platform.instagram || 0 }} إنستجرام</span>
                    </div>
                </div>

                <!-- Stat 4: Ban-Protection Engine Status -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">نظام حماية الحظر (Anti-Ban)</span>
                        <div class="p-1 rounded bg-slate-800 text-emerald-400 border border-slate-700/60">
                            <ShieldCheck class="w-3.5 h-3.5" />
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-2xl font-bold font-mono text-emerald-400 tracking-tight">مفعّل</span>
                        <span class="text-[11px] text-slate-500">بفواصل وتذبذب</span>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[10px] text-slate-400">
                        <span>تأخير ديناميكي تلقائي بين كل رسالة</span>
                    </div>
                </div>
            </div>

            <!-- 2. CAMPAIGNS TABLE CARD -->
            <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3.5">
                    <div>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono flex items-center gap-2">
                            <Repeat class="w-4 h-4 text-blue-400" />
                            <span>سجل حملات إعادة الاستهداف</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            متابعة تقدم الحملات، معدل الإرسال، والتحكم الفوري بالإيقاف والاستئناف
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="router.reload({ preserveScroll: true })"
                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700/80 transition cursor-pointer"
                            title="تحديث البيانات"
                        >
                            <RefreshCw class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="text-slate-500 border-b border-slate-800/80 uppercase font-semibold text-[11px]">
                            <tr>
                                <th class="py-2.5 px-3 text-start">اسم الحملة</th>
                                <th class="py-2.5 px-3 text-start">المنصات</th>
                                <th class="py-2.5 px-3 text-start">فاصل الأمان</th>
                                <th class="py-2.5 px-3 text-start">الحالة</th>
                                <th class="py-2.5 px-3 text-start">نسبة الإنجاز</th>
                                <th class="py-2.5 px-3 text-end">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-if="campaigns.data.length === 0">
                                <td colspan="6" class="py-10 text-center text-slate-500">
                                    <Repeat class="w-8 h-8 mx-auto text-slate-600 mb-2" />
                                    <span>لم تقم بإنشاء أي حملة إعادة استهداف حتى الآن.</span>
                                </td>
                            </tr>
                            <tr
                                v-for="c in campaigns.data"
                                :key="c.id"
                                class="hover:bg-slate-800/30 transition"
                            >
                                <!-- Name -->
                                <td class="py-3 px-3">
                                    <div class="font-bold text-white text-xs">{{ c.name }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                                        {{ new Date(c.created_at).toLocaleDateString('ar-EG') }}
                                    </div>
                                </td>

                                <!-- Platforms -->
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1">
                                        <span
                                            v-for="p in (c.platforms || [])"
                                            :key="p"
                                            class="px-1.5 py-0.5 rounded text-[10px] font-mono font-medium border"
                                            :class="p === 'whatsapp' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : p === 'facebook' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : p === 'instagram' ? 'bg-pink-500/10 text-pink-400 border-pink-500/20' : 'bg-sky-500/10 text-sky-400 border-sky-500/20'"
                                        >
                                            {{ p === 'whatsapp' ? 'واتساب' : p === 'facebook' ? 'فيسبوك' : p === 'instagram' ? 'إنستجرام' : 'تيليجرام' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Interval -->
                                <td class="py-3 px-3 font-mono text-[11px] text-slate-300">
                                    {{ c.interval_seconds }}ث (±{{ c.jitter_seconds }}ث)
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-3">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono font-semibold border"
                                        :class="c.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : c.status === 'processing' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20 animate-pulse' : c.status === 'paused' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-slate-800 text-slate-400 border-slate-700'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="c.status === 'completed' ? 'bg-emerald-400' : c.status === 'processing' ? 'bg-blue-400' : c.status === 'paused' ? 'bg-amber-400' : 'bg-slate-500'"></span>
                                        {{ c.status === 'completed' ? 'مكتملة' : c.status === 'processing' ? 'جارية الإرسال' : c.status === 'paused' ? 'متوقفة مؤقتاً' : 'مسودة' }}
                                    </span>
                                </td>

                                <!-- Progress Bar -->
                                <td class="py-3 px-3 min-w-[140px]">
                                    <div class="flex items-center justify-between text-[10px] font-mono text-slate-400 mb-1">
                                        <span class="text-emerald-400">{{ c.sent_count }} تم</span>
                                        <span v-if="c.failed_count > 0" class="text-rose-400">{{ c.failed_count }} فشل</span>
                                        <span>/ {{ c.total_recipients }}</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-slate-950 rounded-full overflow-hidden border border-slate-800">
                                        <div
                                            class="h-full bg-blue-500 transition-all duration-300"
                                            :style="{ width: `${c.total_recipients > 0 ? Math.round(((c.sent_count + c.failed_count) / c.total_recipients) * 100) : 0}%` }"
                                        ></div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-3 text-end">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="c.status === 'draft' || c.status === 'paused'"
                                            type="button"
                                            @click="startCampaign(c)"
                                            :disabled="isActionLoading[c.id]"
                                            class="p-1.5 rounded-lg bg-blue-600/20 text-blue-400 hover:bg-blue-600/30 border border-blue-500/30 transition cursor-pointer"
                                            title="بدء / استئناف الإرسال"
                                        >
                                            <Play class="w-3.5 h-3.5" />
                                        </button>

                                        <button
                                            v-if="c.status === 'processing'"
                                            type="button"
                                            @click="pauseCampaign(c)"
                                            :disabled="isActionLoading[c.id]"
                                            class="p-1.5 rounded-lg bg-amber-600/20 text-amber-400 hover:bg-amber-600/30 border border-amber-500/30 transition cursor-pointer"
                                            title="إيقاف مؤقت"
                                        >
                                            <Pause class="w-3.5 h-3.5" />
                                        </button>

                                        <button
                                            v-if="c.status !== 'completed'"
                                            type="button"
                                            @click="stepCampaign(c)"
                                            :disabled="isActionLoading[c.id]"
                                            class="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-750 border border-slate-700 transition cursor-pointer"
                                            title="إرسال دفعة تجريبية فورية"
                                        >
                                            <Zap class="w-3.5 h-3.5 text-blue-400" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDetails(c)"
                                            class="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-750 border border-slate-700 transition cursor-pointer"
                                            title="عرض التفاصيل والسجل"
                                        >
                                            <ExternalLink class="w-3.5 h-3.5" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="deleteCampaign(c)"
                                            class="p-1.5 rounded-lg bg-slate-800 text-rose-400 hover:bg-rose-950/40 border border-slate-700 transition cursor-pointer"
                                            title="حذف"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. MODAL: CREATE NEW RETARGETING CAMPAIGN -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <Repeat class="w-5 h-5 text-blue-400" />
                        <h3 class="text-sm font-bold text-white">إعداد حملة إعادة استهداف جديدة</h3>
                    </div>
                    <button
                        type="button"
                        @click="isCreateModalOpen = false"
                        class="p-1 text-slate-400 hover:text-white cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCampaign" class="space-y-4">
                    <!-- Campaign Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">اسم الحملة</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="مثال: استهداف عملاء عطلة الأسبوع"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-sans"
                            required
                        />
                    </div>

                    <!-- Target Platforms Selection (4 platforms) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-slate-300">المنصات المستهدفة (4 قنوات)</label>
                            <button
                                type="button"
                                @click="selectAllPlatforms"
                                class="text-[11px] text-blue-400 hover:underline cursor-pointer"
                            >
                                تحديد كافة المنصات
                            </button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button
                                v-for="p in platformsConfig"
                                :key="p.key"
                                type="button"
                                @click="togglePlatform(p.key)"
                                class="p-2.5 rounded-lg border text-xs font-bold transition flex items-center justify-between cursor-pointer"
                                :class="form.platforms.includes(p.key) ? 'bg-blue-600/20 border-blue-500/60 text-white' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'"
                            >
                                <span>{{ p.name }}</span>
                                <CheckCircle2 v-if="form.platforms.includes(p.key)" class="w-3.5 h-3.5 text-blue-400" />
                            </button>
                        </div>
                    </div>

                    <!-- Anti-Ban Timing Controls (Interval & Jitter) -->
                    <div class="bg-slate-950/70 border border-slate-800/90 rounded-xl p-3.5 space-y-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
                            <ShieldCheck class="w-4 h-4" />
                            <span>إعدادات الفواصل الزمنية لحماية الحساب من الحظر (Anti-Ban)</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">الفاصل الزمني بين كل رسالة (ثواني)</label>
                                <input
                                    v-model.number="form.interval_seconds"
                                    type="number"
                                    min="2"
                                    max="300"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-white font-mono focus:outline-none focus:border-blue-500"
                                />
                                <span class="text-[10px] text-slate-500">موصى به: 8 إلى 15 ثانية</span>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">تذبذب عشوائي (Jitter ± ثواني)</label>
                                <input
                                    v-model.number="form.jitter_seconds"
                                    type="number"
                                    min="0"
                                    max="30"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-xs text-white font-mono focus:outline-none focus:border-blue-500"
                                />
                                <span class="text-[10px] text-slate-500">يمنع رصد النمط الآلي الثابت</span>
                            </div>
                        </div>
                    </div>

                    <!-- Message Template -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-slate-300">نص الرسالة الأساسية</label>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="insertTag('{name}')"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-800 hover:bg-slate-750 text-blue-400 border border-slate-700 cursor-pointer"
                                >
                                    + {name}
                                </button>
                                <button
                                    type="button"
                                    @click="insertTag('{phone}')"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-800 hover:bg-slate-750 text-blue-400 border border-slate-700 cursor-pointer"
                                >
                                    + {phone}
                                </button>
                            </div>
                        </div>
                        <textarea
                            v-model="form.message"
                            rows="4"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white focus:outline-none focus:border-blue-500 font-sans"
                            placeholder="اكتب رسالة إعادة الاستهداف هنا..."
                            required
                        ></textarea>
                    </div>

                    <!-- Auto Start Toggle -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            v-model="form.auto_start"
                            type="checkbox"
                            id="auto_start"
                            class="rounded border-slate-800 bg-slate-950 text-blue-600 focus:ring-0"
                        />
                        <label for="auto_start" class="text-xs text-slate-300 select-none cursor-pointer">
                            بدء إرسال الحملة فوراً بعد الحفظ
                        </label>
                    </div>

                    <!-- Wallet & Credits Cost Estimation in Modal -->
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs space-y-1.5">
                        <div class="flex items-center justify-between text-slate-300">
                            <span>تكلفة الرسائل المقدرة:</span>
                            <span class="font-mono font-bold text-amber-300">
                                {{ audience.total * (messagePricing?.retargeting?.credit_cost || 1) }} رسالة
                                ({{ ((audience.total * (messagePricing?.retargeting?.price || 0.25))).toFixed(2) }} ج.م)
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span>رصيدك المتاح حالياً:</span>
                            <span class="font-mono font-bold" :class="wallet?.credits >= (audience.total * (messagePricing?.retargeting?.credit_cost || 1)) ? 'text-emerald-400' : 'text-rose-400'">
                                {{ wallet?.credits || 0 }} رسالة
                            </span>
                        </div>
                        <div v-if="form.auto_start && wallet?.credits < (audience.total * (messagePricing?.retargeting?.credit_cost || 1))" class="pt-1.5 border-t border-slate-800/80 flex items-center justify-between gap-2 text-[11px] text-amber-400">
                            <span class="flex items-center gap-1">
                                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                الرصيد الحالي غير كافٍ للبدء الفوري. سيتم الحفظ كمسودة.
                            </span>
                            <Link :href="route('wallet.index')" class="text-blue-400 hover:underline font-bold shrink-0">
                                شحن المحفظة
                            </Link>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2.5 border-t border-slate-800 pt-3">
                        <button
                            type="button"
                            @click="isCreateModalOpen = false"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 hover:text-white cursor-pointer"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 rounded-lg text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white transition cursor-pointer shadow-sm disabled:opacity-50"
                        >
                            {{ form.processing ? 'جاري الإنشاء...' : 'إنشاء وجدولة الحملة' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. MODAL: CAMPAIGN DETAILS & LOGS -->
        <div
            v-if="activeDetailModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl max-h-[85vh] overflow-y-auto shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-white">{{ activeDetailModal.name }}</h3>
                        <p class="text-[11px] text-slate-400 font-mono">معرف الحملة: #{{ activeDetailModal.id }}</p>
                    </div>
                    <button
                        type="button"
                        @click="activeDetailModal = null"
                        class="p-1 text-slate-400 hover:text-white cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Stats in details -->
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="p-2 rounded bg-slate-950 border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">إجمالي المستهدفين</span>
                        <span class="font-mono font-bold text-white">{{ activeDetailModal.total_recipients }}</span>
                    </div>
                    <div class="p-2 rounded bg-slate-950 border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">تم الإرسال</span>
                        <span class="font-mono font-bold text-emerald-400">{{ activeDetailModal.sent_count }}</span>
                    </div>
                    <div class="p-2 rounded bg-slate-950 border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">فشل الإرسال</span>
                        <span class="font-mono font-bold text-rose-400">{{ activeDetailModal.failed_count }}</span>
                    </div>
                </div>

                <!-- Message Preview -->
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800 text-xs">
                    <span class="text-[10px] text-slate-500 block mb-1">نص الرسالة:</span>
                    <p class="text-slate-200 whitespace-pre-line">{{ activeDetailModal.message }}</p>
                </div>

                <!-- Recent Recipients Log -->
                <div v-if="activeDetailModal.recent_recipients && activeDetailModal.recent_recipients.length > 0">
                    <h4 class="text-xs font-bold text-slate-300 mb-2">سجل آخر المستلمين:</h4>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto">
                        <div
                            v-for="r in activeDetailModal.recent_recipients"
                            :key="r.id"
                            class="p-2 rounded bg-slate-950 border border-slate-800/80 flex items-center justify-between text-xs"
                        >
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-white text-[11px]">{{ r.recipient_name || 'عميل' }}</span>
                                <span class="font-mono text-slate-500 text-[10px] ms-2">{{ r.recipient_id }}</span>
                            </div>
                            <span
                                class="px-1.5 py-0.2 rounded text-[10px] font-mono font-semibold"
                                :class="r.status === 'sent' ? 'bg-emerald-500/10 text-emerald-400' : r.status === 'failed' ? 'bg-rose-500/10 text-rose-400' : 'bg-slate-800 text-slate-400'"
                            >
                                {{ r.status === 'sent' ? 'تم الإرسال' : r.status === 'failed' ? 'فشل' : 'معلق' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
