<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    MessageSquare,
    MessageCircle,
    Clock,
    Users,
    TrendingUp,
    Send,
    CheckCircle2,
    AlertCircle,
    ArrowUpRight,
    Store,
    Layers,
    Sparkles,
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
            avg_response_minutes: 0,
            by_platform: {},
        }),
    },
    teamPerformance: {
        type: Array,
        default: () => [],
    },
});

const platforms = [
    { key: 'whatsapp', name: 'واتساب', color: 'bg-emerald-500', barBg: 'bg-emerald-500/20', textColor: 'text-emerald-400' },
    { key: 'facebook', name: 'فيسبوك', color: 'bg-blue-600', barBg: 'bg-blue-600/20', textColor: 'text-blue-400' },
    { key: 'instagram', name: 'إنستجرام', color: 'bg-pink-500', barBg: 'bg-pink-500/20', textColor: 'text-pink-400' },
    { key: 'telegram', name: 'تيليجرام', color: 'bg-sky-500', barBg: 'bg-sky-500/20', textColor: 'text-sky-400' },
];

const platformData = computed(() => {
    const total = props.stats.total_conversations || 1;
    return platforms.map(p => {
        const count = props.stats.by_platform?.[p.key] || 0;
        const percentage = Math.round((count / total) * 100);
        return {
            ...p,
            count,
            percentage,
        };
    });
});
</script>

<template>
    <Head :title="`لوحة التحكم والتحليلات - ${business.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-bold text-white tracking-wide">
                            لوحة التحكم والتحليلات
                        </h2>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            {{ business.name }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        نظرة شاملة على أداء الرسائل وسرعة الردود وتوزيع القنوات وفريق العمل
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('inbox.index')"
                        class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition shadow-lg shadow-blue-600/20"
                    >
                        <MessageSquare class="w-4 h-4" />
                        <span>فتح صندوق الوارد</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- 1. STAT CARDS GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Card 1: Total Conversations -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400">إجمالي المحادثات</span>
                        <div class="p-2.5 bg-blue-600/10 border border-blue-500/20 rounded-xl text-blue-400">
                            <MessageSquare class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-white font-mono">{{ stats.total_conversations }}</span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
                        <span class="text-emerald-400 font-semibold">{{ stats.open }} مفتوحة</span>
                        <span class="text-slate-500">•</span>
                        <span class="text-amber-400 font-semibold">{{ stats.pending }} معلقة</span>
                        <span class="text-slate-500">•</span>
                        <span class="text-slate-400 font-semibold">{{ stats.closed }} مغلقة</span>
                    </div>
                </div>

                <!-- Card 2: Messages Today & Week -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400">رسائل اليوم</span>
                        <div class="p-2.5 bg-emerald-600/10 border border-emerald-500/20 rounded-xl text-emerald-400">
                            <Send class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-white font-mono">{{ stats.messages_today }}</span>
                        <span class="text-xs text-slate-500">رسالة اليوم</span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                        <span>هذا الأسبوع:</span>
                        <span class="font-bold text-white font-mono">{{ stats.messages_this_week }} رسالة</span>
                    </div>
                </div>

                <!-- Card 3: Avg First Response Time -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400">متوسط زمن الاستجابة</span>
                        <div class="p-2.5 bg-amber-600/10 border border-amber-500/20 rounded-xl text-amber-400">
                            <Clock class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-white font-mono">{{ stats.avg_response_minutes }}</span>
                        <span class="text-xs text-slate-400">دقيقة</span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 text-[11px] text-slate-500">
                        الوقت المنقضي حتى أول رد من الوكيل
                    </div>
                </div>

                <!-- Card 4: Team Handled -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-xl relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400">أعضاء الفريق النشطين</span>
                        <div class="p-2.5 bg-purple-600/10 border border-purple-500/20 rounded-xl text-purple-400">
                            <Users class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-white font-mono">{{ teamPerformance.length }}</span>
                        <span class="text-xs text-slate-500">عضو فريق</span>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 text-[11px] text-slate-400 flex items-center justify-between">
                        <span>مسند إليهم محادثات:</span>
                        <span class="font-bold text-white font-mono">
                            {{ teamPerformance.reduce((acc, m) => acc + (m.handled || 0), 0) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. CHARTS & DETAILS GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Platform Breakdown Card -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <Layers class="w-4 h-4 text-blue-400" />
                                توزيع المحادثات حسب القناة
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">نسبة المحادثات الواردة من كل منصة تواصل</p>
                        </div>
                        <span class="text-xs font-mono text-slate-500">{{ stats.total_conversations }} إجمالي</span>
                    </div>

                    <!-- CSS Bar Chart -->
                    <div class="space-y-4">
                        <div
                            v-for="p in platformData"
                            :key="p.key"
                            class="space-y-1.5"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-200">{{ p.name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-slate-400">{{ p.count }} محادثة</span>
                                    <span class="font-mono font-bold" :class="p.textColor">{{ p.percentage }}%</span>
                                </div>
                            </div>
                            <div class="h-3 w-full bg-slate-950 rounded-full overflow-hidden p-0.5 border border-slate-800">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="p.color"
                                    :style="{ width: `${p.percentage}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Performance Table Card -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4 backdrop-blur-sm">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <Users class="w-4 h-4 text-purple-400" />
                                أداء فريق الدعم والمبيعات
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">عدد المحادثات المسندة لكل عضو في الفريق</p>
                        </div>
                        <Link
                            :href="route('team.index')"
                            class="text-xs text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1"
                        >
                            <span>إدارة الفريق</span>
                            <ArrowUpRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-start text-xs">
                            <thead class="text-slate-500 border-b border-slate-800 uppercase font-semibold">
                                <tr>
                                    <th class="py-2.5 px-3 text-start">عضو الفريق</th>
                                    <th class="py-2.5 px-3 text-start">الدور</th>
                                    <th class="py-2.5 px-3 text-end">المحادثات المسندة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80 text-slate-300">
                                <tr v-if="teamPerformance.length === 0">
                                    <td colspan="3" class="py-8 text-center text-slate-500">
                                        لا يوجد أعضاء في الفريق حالياً.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(member, idx) in teamPerformance"
                                    :key="idx"
                                    class="hover:bg-slate-800/40 transition"
                                >
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-200 text-xs">
                                                {{ member.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <span class="font-bold text-white">{{ member.name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400 border border-slate-700">
                                            {{ member.role === 'admin' ? 'مسؤول' : member.role === 'supervisor' ? 'مشرف' : 'وكيل' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-end font-mono font-bold text-white">
                                        {{ member.handled }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
