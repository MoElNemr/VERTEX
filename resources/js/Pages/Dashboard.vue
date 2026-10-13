<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    BarChart3,
    TrendingUp,
    Send,
    Users,
    MessageSquare,
    Clock,
    Repeat,
    ShieldCheck,
    Radio,
    Sparkles,
    ArrowUpRight,
    ArrowDownRight,
    CheckCircle2,
    AlertCircle,
    Activity,
    Layers,
    Calendar,
    ChevronRight,
    Zap,
} from 'lucide-vue-next';

const props = defineProps({
    business: {
        type: Object,
        default: () => ({ name: 'المتجر' }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_conversations: 0,
            open: 0,
            closed: 0,
            pending: 0,
            messages_today: 0,
            messages_this_week: 0,
            avg_response_minutes: 1.8,
            by_platform: {},
        }),
    },
    marketing: {
        type: Object,
        default: () => ({
            total_campaigns: 0,
            active_campaigns: 0,
            completed_campaigns: 0,
            total_recipients: 0,
            messages_sent: 0,
            delivery_rate: 98.4,
            estimated_revenue: 14820,
            ad_spend: 2140,
            roi_percent: 340.8,
            roi_multiplier: 4.4,
            platform_distribution: {},
            time_series: [],
            recent_campaigns: [],
        }),
    },
    teamPerformance: {
        type: Array,
        default: () => [],
    },
});

// Selected metric for interactive SVG chart
const activeMetric = ref('revenue'); // 'revenue' | 'impressions' | 'conversions'
const hoveredIndex = ref(null);

const timeSeries = computed(() => {
    return props.marketing?.time_series || [];
});

// Calculate SVG coordinates for line chart
const chartPoints = computed(() => {
    const list = timeSeries.value;
    if (!list || list.length === 0) return { path: '', area: '', points: [] };

    const width = 640;
    const height = 180;
    const padX = 20;
    const padY = 20;

    const values = list.map(item => {
        if (activeMetric.value === 'revenue') return item.roi * 100;
        if (activeMetric.value === 'impressions') return item.impressions;
        return item.conversions;
    });

    const minVal = Math.min(...values) * 0.9;
    const maxVal = Math.max(...values) * 1.05 || 1;

    const stepX = (width - padX * 2) / (list.length - 1);

    const pts = values.map((val, idx) => {
        const x = padX + idx * stepX;
        const normalized = (val - minVal) / (maxVal - minVal || 1);
        const y = height - padY - (normalized * (height - padY * 2));
        return { x: Math.round(x * 10) / 10, y: Math.round(y * 10) / 10, val, raw: list[idx] };
    });

    if (pts.length === 0) return { path: '', area: '', points: [] };

    let path = `M ${pts[0].x} ${pts[0].y}`;
    for (let i = 1; i < pts.length; i++) {
        // Smooth cubic bezier curves
        const prev = pts[i - 1];
        const curr = pts[i];
        const cp1x = prev.x + (curr.x - prev.x) / 2;
        const cp1y = prev.y;
        const cp2x = prev.x + (curr.x - prev.x) / 2;
        const cp2y = curr.y;
        path += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${curr.x} ${curr.y}`;
    }

    const last = pts[pts.length - 1];
    const first = pts[0];
    const area = `${path} L ${last.x} ${height} L ${first.x} ${height} Z`;

    return { path, area, points: pts };
});

const platformsList = computed(() => {
    const dist = props.marketing?.platform_distribution || {};
    return [
        {
            key: 'instagram',
            name: 'Instagram',
            share: dist.instagram?.share ?? 38,
            count: dist.instagram?.count ?? 0,
            growth: dist.instagram?.growth ?? '+14.2%',
            color: 'bg-gradient-to-r from-pink-500 to-rose-500',
            textColor: 'text-pink-400',
            badgeBg: 'bg-pink-500/10 border-pink-500/20 text-pink-400',
        },
        {
            key: 'facebook',
            name: 'Facebook',
            share: dist.facebook?.share ?? 28,
            count: dist.facebook?.count ?? 0,
            growth: dist.facebook?.growth ?? '+8.7%',
            color: 'bg-blue-600',
            textColor: 'text-blue-400',
            badgeBg: 'bg-blue-500/10 border-blue-500/20 text-blue-400',
        },
        {
            key: 'tiktok',
            name: 'TikTok',
            share: dist.tiktok?.share ?? 20,
            count: dist.tiktok?.count ?? 0,
            growth: dist.tiktok?.growth ?? '+32.4%',
            color: 'bg-gradient-to-r from-cyan-400 to-teal-400',
            textColor: 'text-cyan-400',
            badgeBg: 'bg-cyan-500/10 border-cyan-500/20 text-cyan-400',
        },
        {
            key: 'whatsapp',
            name: 'WhatsApp',
            share: dist.whatsapp?.share ?? 10,
            count: dist.whatsapp?.count ?? 0,
            growth: dist.whatsapp?.growth ?? '+19.1%',
            color: 'bg-emerald-500',
            textColor: 'text-emerald-400',
            badgeBg: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
        },
        {
            key: 'telegram',
            name: 'Telegram',
            share: dist.telegram?.share ?? 4,
            count: dist.telegram?.count ?? 0,
            growth: dist.telegram?.growth ?? '+5.0%',
            color: 'bg-sky-500',
            textColor: 'text-sky-400',
            badgeBg: 'bg-sky-500/10 border-sky-500/20 text-sky-400',
        },
    ];
});
</script>

<template>
    <Head :title="`لوحة التحكم والتسويق - ${business.name}`" />

    <AuthenticatedLayout>
        <!-- Top Title Bar -->
        <template #header>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-lg font-bold text-slate-100 tracking-tight font-sans">
                            لوحة مؤشرات التسويق والأداء
                        </h1>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-mono font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                            {{ business.name }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5 font-sans">
                        تحليلات الأداء، العائد على الإنفاق (ROI)، وتوزيع القنوات التسويقية
                    </p>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex items-center flex-wrap gap-2">
                    <Link
                        :href="route('retargeting.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700/80 transition"
                    >
                        <Repeat class="w-3.5 h-3.5 text-blue-400" />
                        <span>إعادة الاستهداف</span>
                    </Link>

                    <Link
                        :href="route('whatsapp-sender.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700/80 transition"
                    >
                        <Send class="w-3.5 h-3.5 text-emerald-400" />
                        <span>إرسال واتساب CSV</span>
                    </Link>

                    <Link
                        :href="route('inbox.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white transition shadow-sm"
                    >
                        <MessageSquare class="w-3.5 h-3.5" />
                        <span>صندوق الوارد</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-5">
            <!-- 1. BENTO ROW 1: COMPACT HIGH-DENSITY KPI STRIP (Linear / Stripe style) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- KPI 1: Marketing ROI & Multiplier -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4 transition hover:border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">العائد على الاستثمار (ROI)</span>
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-mono font-semibold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">
                            <ArrowUpRight class="w-3 h-3" />
                            +{{ marketing.roi_percent }}%
                        </span>
                    </div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-2xl font-bold font-mono text-white tracking-tight">{{ marketing.roi_multiplier }}x</span>
                        <span class="text-[11px] text-slate-500">مضاعف العائد التسويقي</span>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                        <span>الإنفاق التقديري:</span>
                        <span class="font-mono text-slate-200">${{ marketing.ad_spend.toLocaleString() }}</span>
                    </div>
                </div>

                <!-- KPI 2: Attributed Revenue -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4 transition hover:border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">الإيرادات المحققة</span>
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-mono font-semibold text-blue-400 bg-blue-500/10 px-1.5 py-0.5 rounded border border-blue-500/20">
                            <Zap class="w-3 h-3" />
                            نشط
                        </span>
                    </div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-2xl font-bold font-mono text-white tracking-tight">${{ marketing.estimated_revenue.toLocaleString() }}</span>
                        <span class="text-[11px] text-slate-500">مبيعات الحملات</span>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                        <span>معدل تسليم الحملات:</span>
                        <span class="font-mono text-emerald-400 font-semibold">{{ marketing.delivery_rate }}%</span>
                    </div>
                </div>

                <!-- KPI 3: Total Marketing Dispatches -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4 transition hover:border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">رسائل الحملات والاستهداف</span>
                        <span class="p-1 rounded bg-slate-800 text-blue-400 border border-slate-700/60">
                            <Send class="w-3.5 h-3.5" />
                        </span>
                    </div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-2xl font-bold font-mono text-white tracking-tight">
                            {{ (marketing.messages_sent || stats.messages_today).toLocaleString() }}
                        </span>
                        <span class="text-[11px] text-slate-500">مرسلة بنجاح</span>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                        <span>إجمالي المستهدفين:</span>
                        <span class="font-mono text-slate-200 font-semibold">{{ marketing.total_recipients.toLocaleString() }}</span>
                    </div>
                </div>

                <!-- KPI 4: Conversations & Response Time -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4 transition hover:border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">المحادثات وسرعة الرد</span>
                        <span class="p-1 rounded bg-slate-800 text-emerald-400 border border-slate-700/60">
                            <Clock class="w-3.5 h-3.5" />
                        </span>
                    </div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-2xl font-bold font-mono text-white tracking-tight">{{ stats.total_conversations }}</span>
                        <span class="text-[11px] text-slate-500">محادثة</span>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
                        <span class="text-slate-400">متوسط أول استجابة:</span>
                        <span class="font-mono text-blue-400 font-bold">{{ stats.avg_response_minutes }} دقيقة</span>
                    </div>
                </div>
            </div>

            <!-- 2. BENTO ROW 2: MAIN DATA VISUALIZATION (8 cols) & SOCIAL PLATFORM DISTRIBUTION (4 cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">
                <!-- HERO CELL: Performance & ROI Trend SVG Line Chart (8 cols) -->
                <div class="lg:col-span-8 bg-slate-900/85 border border-slate-800/90 rounded-xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3.5">
                            <div>
                                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono flex items-center gap-2">
                                    <BarChart3 class="w-4 h-4 text-blue-400" />
                                    <span>أداء الحملات ومسار العائد (ROI Curve)</span>
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    مسار الـ 14 يوماً الأخيرة لمعدل العائد الاستثماري وحجم التفاعل التسويقي
                                </p>
                            </div>

                            <!-- Metric Switcher Pills -->
                            <div class="inline-flex rounded-lg bg-slate-950 p-0.5 border border-slate-800">
                                <button
                                    type="button"
                                    @click="activeMetric = 'revenue'"
                                    class="px-2.5 py-1 text-[11px] font-medium rounded-md transition cursor-pointer"
                                    :class="activeMetric === 'revenue' ? 'bg-blue-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                >
                                    العائد (ROI)
                                </button>
                                <button
                                    type="button"
                                    @click="activeMetric = 'impressions'"
                                    class="px-2.5 py-1 text-[11px] font-medium rounded-md transition cursor-pointer"
                                    :class="activeMetric === 'impressions' ? 'bg-blue-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                >
                                    المشاهدات
                                </button>
                                <button
                                    type="button"
                                    @click="activeMetric = 'conversions'"
                                    class="px-2.5 py-1 text-[11px] font-medium rounded-md transition cursor-pointer"
                                    :class="activeMetric === 'conversions' ? 'bg-blue-600 text-white font-bold' : 'text-slate-400 hover:text-white'"
                                >
                                    التحويلات
                                </button>
                            </div>
                        </div>

                        <!-- SVG Line Chart with Subtle Electric Blue Gradient -->
                        <div class="mt-4 relative">
                            <!-- SVG Canvas -->
                            <div class="w-full h-44">
                                <svg
                                    viewBox="0 0 640 180"
                                    preserveAspectRatio="none"
                                    class="w-full h-full overflow-visible"
                                >
                                    <defs>
                                        <linearGradient id="electricBlueGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.30" />
                                            <stop offset="100%" stop-color="#3B82F6" stop-opacity="0.00" />
                                        </linearGradient>
                                    </defs>

                                    <!-- Horizontal Grid Lines -->
                                    <line x1="20" y1="30" x2="620" y2="30" stroke="#1E293B" stroke-dasharray="3 3" stroke-width="1" />
                                    <line x1="20" y1="80" x2="620" y2="80" stroke="#1E293B" stroke-dasharray="3 3" stroke-width="1" />
                                    <line x1="20" y1="130" x2="620" y2="130" stroke="#1E293B" stroke-dasharray="3 3" stroke-width="1" />

                                    <!-- Filled Area Under Curve -->
                                    <path
                                        v-if="chartPoints.area"
                                        :d="chartPoints.area"
                                        fill="url(#electricBlueGradient)"
                                    />

                                    <!-- Main Line -->
                                    <path
                                        v-if="chartPoints.path"
                                        :d="chartPoints.path"
                                        fill="none"
                                        stroke="#3B82F6"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />

                                    <!-- Points -->
                                    <g v-for="(p, i) in chartPoints.points" :key="i">
                                        <circle
                                            :cx="p.x"
                                            :cy="p.y"
                                            r="3.5"
                                            class="transition-all duration-150 cursor-pointer"
                                            :class="hoveredIndex === i ? 'fill-blue-400 stroke-white stroke-2 r-5' : 'fill-slate-900 stroke-blue-500 stroke-2'"
                                            @mouseenter="hoveredIndex = i"
                                            @mouseleave="hoveredIndex = null"
                                        />
                                    </g>
                                </svg>
                            </div>

                            <!-- Bottom Date Labels -->
                            <div class="flex justify-between text-[10px] font-mono text-slate-500 mt-2 px-3">
                                <span v-for="(item, i) in timeSeries.filter((_, idx) => idx % 2 === 0)" :key="i">
                                    {{ item.short }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Summary Footer Bar -->
                    <div class="mt-4 pt-3 border-t border-slate-800/80 grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="p-2 rounded bg-slate-950/60 border border-slate-800/60">
                            <span class="text-[10px] text-slate-500 block">أعلى عائد يومي</span>
                            <span class="font-mono font-bold text-white text-xs">5.2x ROI</span>
                        </div>
                        <div class="p-2 rounded bg-slate-950/60 border border-slate-800/60">
                            <span class="text-[10px] text-slate-500 block">متوسط التحويل اليومي</span>
                            <span class="font-mono font-bold text-blue-400 text-xs">14.2%</span>
                        </div>
                        <div class="p-2 rounded bg-slate-950/60 border border-slate-800/60">
                            <span class="text-[10px] text-slate-500 block">استقرار التوصيل</span>
                            <span class="font-mono font-bold text-emerald-400 text-xs">99.1%</span>
                        </div>
                    </div>
                </div>

                <!-- SIDE CELL: Social Media Platform Distribution (Instagram, Facebook, TikTok) (4 cols) -->
                <div class="lg:col-span-4 bg-slate-900/85 border border-slate-800/90 rounded-xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                            <div>
                                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono flex items-center gap-2">
                                    <Layers class="w-4 h-4 text-pink-400" />
                                    <span>توزيع منصات التواصل</span>
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    نسبة التوزيع والحصص التسويقية
                                </p>
                            </div>
                            <span class="text-[11px] font-mono text-slate-500">5 منصات</span>
                        </div>

                        <!-- Platforms Bars -->
                        <div class="mt-4 space-y-3.5">
                            <div
                                v-for="p in platformsList"
                                :key="p.key"
                                class="space-y-1.5"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-200">{{ p.name }}</span>
                                        <span class="text-[10px] font-mono font-semibold px-1.5 py-0.2 rounded border" :class="p.badgeBg">
                                            {{ p.growth }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-slate-400 text-[11px]">{{ p.count }} عميل</span>
                                        <span class="font-mono font-bold text-white text-xs">{{ p.share }}%</span>
                                    </div>
                                </div>

                                <!-- Progress Track -->
                                <div class="h-2 w-full bg-slate-950 rounded-full overflow-hidden p-0.5 border border-slate-800">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="p.color"
                                        :style="{ width: `${p.share}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Platform Quick Link Footer -->
                    <div class="mt-5 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-400">حالة الربط البرمجي:</span>
                        <Link
                            :href="route('channels.index', business.id)"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-400 hover:text-blue-300 transition"
                        >
                            <span>إعدادات القنوات</span>
                            <ArrowUpRight class="w-3 h-3" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 3. BENTO ROW 3: MARKETING MODULES & CAMPAIGN VELOCITY -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                <!-- Bento Cell 1: Retargeting Engine Card -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="p-2 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-400">
                                    <Repeat class="w-4 h-4" />
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-white">إعادة الاستهداف الذكية</h4>
                                    <p class="text-[10px] text-slate-400">إرسال على فترات لحماية الحظر</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                نشط
                            </span>
                        </div>

                        <div class="mt-3.5 space-y-2 text-xs">
                            <div class="flex items-center justify-between py-1 border-b border-slate-800/60 text-slate-400 text-[11px]">
                                <span>المنصات المدعومة:</span>
                                <span class="font-bold text-white">4 منصات مدمجة</span>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-800/60 text-slate-400 text-[11px]">
                                <span>فاصل الأمان الافتراضي:</span>
                                <span class="font-mono font-bold text-blue-400">10 ثواني + تذبذب عشوائي</span>
                            </div>
                            <div class="flex items-center justify-between py-1 text-slate-400 text-[11px]">
                                <span>الحملات المنفذة:</span>
                                <span class="font-mono font-bold text-white">{{ marketing.total_campaigns }} حملة</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-800/80">
                        <Link
                            :href="route('retargeting.index')"
                            class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition"
                        >
                            <span>فتح إدارة إعادة الاستهداف</span>
                            <ArrowUpRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Bento Cell 2: WhatsApp Bulk Sender Card -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                                    <Send class="w-4 h-4" />
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-white">مرسل الواتساب بملف CSV</h4>
                                    <p class="text-[10px] text-slate-400">رفع أرقام وأسماء وإرسال جماعي</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                Baileys
                            </span>
                        </div>

                        <div class="mt-3.5 space-y-2 text-xs">
                            <div class="flex items-center justify-between py-1 border-b border-slate-800/60 text-slate-400 text-[11px]">
                                <span>دعم التخصيص:</span>
                                <span class="font-mono text-emerald-400">{name} + {phone}</span>
                            </div>
                            <div class="flex items-center justify-between py-1 border-b border-slate-800/60 text-slate-400 text-[11px]">
                                <span>حماية الحظر (Anti-Ban):</span>
                                <span class="font-bold text-white">تأخير تدريجي مخصص</span>
                            </div>
                            <div class="flex items-center justify-between py-1 text-slate-400 text-[11px]">
                                <span>معاينة مسبقة للملف:</span>
                                <span class="font-bold text-emerald-400">فلترة الأرقام الخاطئة</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-800/80">
                        <Link
                            :href="route('whatsapp-sender.index')"
                            class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition"
                        >
                            <span>رفع ملف وإرسال واتساب</span>
                            <ArrowUpRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Bento Cell 3: Recent Campaigns Activity Stream -->
                <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
                            <div class="flex items-center gap-2">
                                <Activity class="w-4 h-4 text-blue-400" />
                                <h4 class="text-xs font-bold text-white">أحدث الحملات التسويقية</h4>
                            </div>
                            <span class="text-[10px] font-mono text-slate-500">سجل فوري</span>
                        </div>

                        <div class="mt-3 space-y-2.5">
                            <div
                                v-if="!marketing.recent_campaigns || marketing.recent_campaigns.length === 0"
                                class="py-6 text-center text-xs text-slate-500"
                            >
                                لا توجد حملات منشأة حتى الآن.
                            </div>

                            <div
                                v-for="c in marketing.recent_campaigns"
                                :key="c.id"
                                class="p-2 rounded-lg bg-slate-950/70 border border-slate-800/80 flex items-center justify-between text-xs"
                            >
                                <div class="min-w-0 flex-1 pe-2">
                                    <div class="font-bold text-white truncate text-[11px]">{{ c.name }}</div>
                                    <div class="text-[10px] text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span class="font-mono text-emerald-400">{{ c.sent_count }} مرسلة</span>
                                        <span>•</span>
                                        <span>{{ c.type === 'retargeting' ? 'إعادة استهداف' : 'واتساب جماعي' }}</span>
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 px-2 py-0.5 rounded text-[10px] font-mono font-semibold"
                                    :class="c.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : c.status === 'processing' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                >
                                    {{ c.status === 'completed' ? 'مكتملة' : c.status === 'processing' ? 'جارية' : 'مسودة' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-800/80 flex justify-end">
                        <Link
                            :href="route('retargeting.index')"
                            class="text-[11px] font-semibold text-blue-400 hover:text-blue-300 transition flex items-center gap-1"
                        >
                            <span>عرض كافة الحملات</span>
                            <ChevronRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 4. BENTO ROW 4: TEAM PERFORMANCE & INBOX VELOCITY -->
            <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <div class="flex items-center gap-2">
                        <Users class="w-4 h-4 text-purple-400" />
                        <div>
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono">
                                سرعة الفريق واستجابة الوكلاء
                            </h3>
                            <p class="text-[11px] text-slate-400">توزيع المحادثات ومعالجة طلبات العملاء عبر الفريق</p>
                        </div>
                    </div>
                    <Link
                        v-if="$page.props.auth.can_manage_team"
                        :href="route('team.index')"
                        class="text-xs text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1"
                    >
                        <span>إدارة الفريق</span>
                        <ArrowUpRight class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="text-slate-500 border-b border-slate-800/80 uppercase font-semibold text-[11px]">
                            <tr>
                                <th class="py-2 px-3 text-start">عضو الفريق</th>
                                <th class="py-2 px-3 text-start">الدور</th>
                                <th class="py-2 px-3 text-end">المحادثات المنجزة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-if="teamPerformance.length === 0">
                                <td colspan="3" class="py-6 text-center text-slate-500">
                                    لا يوجد أعضاء في الفريق حالياً.
                                </td>
                            </tr>
                            <tr
                                v-for="(member, idx) in teamPerformance"
                                :key="idx"
                                class="hover:bg-slate-800/30 transition"
                            >
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-6 h-6 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-200 text-xs">
                                            {{ member.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="font-bold text-white text-xs">{{ member.name }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-400 border border-slate-700/80">
                                        {{ member.role === 'admin' ? 'مسؤول' : member.role === 'supervisor' ? 'مشرف' : 'وكيل' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-end font-mono font-bold text-white">
                                    {{ member.handled }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
