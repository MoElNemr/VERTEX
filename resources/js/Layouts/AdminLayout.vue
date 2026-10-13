<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import {
    Crown,
    LayoutDashboard,
    Store,
    Users,
    Radio,
    Flame,
    Cpu,
    LogOut,
    Menu,
    X,
    ExternalLink,
    ShieldAlert
} from 'lucide-vue-next';

defineProps({
    health: {
        type: Object,
        default: () => ({}),
    },
});

const isMobileNavOpen = ref(false);
</script>

<template>
    <div class="min-h-screen flex bg-slate-950 text-slate-100 font-sans selection:bg-purple-600 selection:text-white" dir="rtl">

        <!-- ========================================== -->
        <!-- 1. DESKTOP SUPER ADMIN SIDEBAR             -->
        <!-- ========================================== -->
        <aside class="hidden md:flex flex-col w-64 shrink-0 bg-slate-900/95 border-e border-slate-800/90 h-screen sticky top-0 z-30 select-none">
            <!-- Brand & Root Control Badge -->
            <div class="p-4 border-b border-slate-800/80">
                <div class="flex items-center justify-between">
                    <Link :href="route('superadmin.index')" class="flex items-center gap-2.5 group">
                        <div class="w-8 h-8 rounded-xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center text-purple-400">
                            <Crown class="w-4 h-4 text-amber-400" />
                        </div>
                        <div>
                            <span class="font-extrabold text-sm tracking-tight text-white block">VERTEX ROOT</span>
                            <span class="text-[10px] font-mono text-purple-400 block -mt-0.5">Command Center</span>
                        </div>
                    </Link>

                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        MASTER
                    </span>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto p-3 space-y-4 text-xs">
                <div class="space-y-1">
                    <span class="px-2.5 text-[10px] font-mono font-semibold text-slate-500 uppercase tracking-wider block mb-1">
                        إدارة المنصة
                    </span>

                    <Link
                        :href="route('superadmin.index')"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition"
                        :class="route().current('superadmin.index') ? 'bg-gradient-to-r from-purple-900/80 to-slate-900 text-white font-bold border border-purple-500/40 shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
                    >
                        <LayoutDashboard class="w-4 h-4 shrink-0 text-purple-400" />
                        <span>لوحة التحكم الرئيسية</span>
                    </Link>
                </div>

                <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800/80 text-[11px] space-y-2">
                    <div class="flex items-center gap-1.5 text-slate-400 font-semibold">
                        <ShieldAlert class="w-3.5 h-3.5 text-amber-400" />
                        <span>منطقة سيادية</span>
                    </div>
                    <p class="text-[10px] text-slate-500 leading-relaxed">
                        أنت الآن في لوحة المالك المعزولة. أي تعديل ينعكس مباشرة على قاعدة بيانات وخوادم المنصة.
                    </p>
                </div>
            </div>

            <!-- Footer: Super Admin Profile & Isolated Logout -->
            <div class="p-3 border-t border-slate-800/80 bg-slate-900/60">
                <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800 mb-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-purple-600/30 border border-purple-500/40 text-purple-300 flex items-center justify-center font-bold text-xs shrink-0">
                            👑
                        </div>
                        <div class="min-w-0 text-start">
                            <span class="font-bold text-xs text-white block truncate">{{ $page.props.auth.user?.name }}</span>
                            <span class="text-[10px] text-amber-400 font-mono block truncate">Super Admin (مالك)</span>
                        </div>
                    </div>
                </div>

                <Link
                    :href="route('superadmin.logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-bold text-rose-400 hover:bg-rose-500/10 border border-rose-500/20 transition"
                >
                    <LogOut class="w-3.5 h-3.5" />
                    <span>تسجيل خروج الأدمن</span>
                </Link>
            </div>
        </aside>

        <!-- ========================================== -->
        <!-- 2. MAIN CONTENT AREA                       -->
        <!-- ========================================== -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Mobile Top Bar -->
            <header class="md:hidden flex items-center justify-between p-4 bg-slate-900 border-b border-slate-800 sticky top-0 z-40">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="isMobileNavOpen = !isMobileNavOpen"
                        class="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:text-white"
                    >
                        <Menu v-if="!isMobileNavOpen" class="w-5 h-5" />
                        <X v-else class="w-5 h-5" />
                    </button>
                    <span class="font-extrabold text-sm text-white">VERTEX ROOT</span>
                </div>

                <Link
                    :href="route('superadmin.logout')"
                    method="post"
                    as="button"
                    class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-500/10"
                >
                    <LogOut class="w-4 h-4" />
                </Link>
            </header>

            <!-- Main Page Slot -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>

    </div>
</template>
