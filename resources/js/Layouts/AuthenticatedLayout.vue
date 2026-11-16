<script setup>
import { ref, watch, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Inbox,
    Users,
    Store,
    Radio,
    ShieldCheck,
    Sparkles,
    ChevronDown,
    Check,
    ArrowUpRight,
    Loader2,
    Menu,
    X,
    LogOut,
    User,
    Crown,
    PanelRightClose,
    PanelRightOpen,
    Download,
} from 'lucide-vue-next';

const props = defineProps({
    fullHeight: {
        type: Boolean,
        default: false,
    },
});

const isSidebarCollapsed = ref(
    typeof window !== 'undefined' ? localStorage.getItem('vertex_sidebar_collapsed') === 'true' : false
);

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('vertex_sidebar_collapsed', isSidebarCollapsed.value ? 'true' : 'false');
    }
};

const isMobileNavOpen = ref(false);
const isBusinessDropdownOpen = ref(false);

const deferredInstallPrompt = ref(null);
const canInstallPwa = ref(false);

onMounted(() => {
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt.value = e;
        canInstallPwa.value = true;
    });
});

const installPwa = async () => {
    if (!deferredInstallPrompt.value) return;
    deferredInstallPrompt.value.prompt();
    const { outcome } = await deferredInstallPrompt.value.userChoice;
    if (outcome === 'accepted') {
        canInstallPwa.value = false;
        deferredInstallPrompt.value = null;
    }
};
const switchingBusinessId = ref(null);

const toggleBusinessDropdown = () => {
    isBusinessDropdownOpen.value = !isBusinessDropdownOpen.value;
};

const switchBusiness = (id) => {
    if (switchingBusinessId.value) return;
    switchingBusinessId.value = id;
    isBusinessDropdownOpen.value = false;
    isMobileNavOpen.value = false;

    router.post(route('businesses.switch', id), {}, {
        preserveScroll: true,
        onFinish: () => {
            switchingBusinessId.value = null;
        },
    });
};

const page = usePage();
const flashMessage = ref(null);
const flashType = ref('success');
let flashTimer = null;

const dismissFlash = () => {
    flashMessage.value = null;
    if (flashTimer) clearTimeout(flashTimer);
};

watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash?.success) {
            flashMessage.value = newFlash.success;
            flashType.value = 'success';
            if (flashTimer) clearTimeout(flashTimer);
            flashTimer = setTimeout(() => {
                flashMessage.value = null;
            }, 5000);
        } else if (newFlash?.error) {
            flashMessage.value = newFlash.error;
            flashType.value = 'error';
            if (flashTimer) clearTimeout(flashTimer);
            flashTimer = setTimeout(() => {
                flashMessage.value = null;
            }, 6000);
        }
    },
    { immediate: true, deep: true }
);
</script>

<template>
    <div
        class="flex bg-slate-950 text-slate-100 font-sans"
        :class="fullHeight ? 'h-screen h-[100dvh] overflow-hidden' : 'min-h-screen'"
    >
        <!-- ======================================================== -->
        <!-- 1. DESKTOP SIDE NAVIGATION PANEL (Linear / Stripe Style) -->
        <!-- ======================================================== -->
        <aside
            class="hidden md:flex flex-col shrink-0 bg-slate-900/95 border-e border-slate-800/90 h-screen sticky top-0 z-30 select-none transition-all duration-300 ease-in-out"
            :class="isSidebarCollapsed ? 'w-0 border-transparent opacity-0 overflow-hidden pointer-events-none' : 'w-64'"
        >
            <!-- Brand & Workspace Header -->
            <div class="p-4 border-b border-slate-800/80">
                <div class="flex items-center justify-between">
                    <Link :href="route('dashboard')" class="flex items-center gap-2.5 group">
                        <ApplicationLogo class="h-7 w-auto fill-current text-blue-500 transition group-hover:scale-105" />
                        <div>
                            <span class="font-extrabold text-sm tracking-tight text-white block">VERTEX</span>
                            <span class="text-[10px] font-mono text-slate-400 block -mt-0.5">Marketing Cloud</span>
                        </div>
                    </Link>

                    <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            v2.4
                        </span>
                        <button
                            type="button"
                            @click="toggleSidebar"
                            class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                            title="إخفاء القائمة الجانبية"
                        >
                            <PanelRightClose class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Business / Store Switcher Selector in Sidebar -->
                <div class="mt-3 relative" v-if="$page.props.businesses && $page.props.businesses.length > 0">
                    <button
                        type="button"
                        @click="toggleBusinessDropdown"
                        class="w-full flex items-center justify-between p-2 rounded-xl bg-slate-950/80 border border-slate-800 hover:border-slate-700 transition cursor-pointer text-start"
                        :class="{ 'ring-1 ring-blue-500/50 border-blue-500/80': isBusinessDropdownOpen }"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                            <span class="text-xs font-bold text-slate-200 truncate">
                                {{ $page.props.currentBusiness ? $page.props.currentBusiness.name : 'اختر المتجر' }}
                            </span>
                        </div>
                        <Loader2 v-if="switchingBusinessId" class="w-3.5 h-3.5 text-blue-400 animate-spin shrink-0" />
                        <ChevronDown v-else class="w-3.5 h-3.5 text-slate-400 transition shrink-0" :class="{ 'rotate-180': isBusinessDropdownOpen }" />
                    </button>

                    <!-- Dropdown Backdrop -->
                    <div
                        v-if="isBusinessDropdownOpen"
                        class="fixed inset-0 z-40"
                        @click="isBusinessDropdownOpen = false"
                    ></div>

                    <!-- Dropdown Options -->
                    <Transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="isBusinessDropdownOpen"
                            class="absolute z-50 mt-1.5 w-full rounded-xl bg-slate-900 border border-slate-750 shadow-2xl p-1.5 start-0 overflow-hidden"
                        >
                            <div class="px-2 py-1 text-[10px] font-mono font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                                المتاجر التابعة لك
                            </div>
                            <div class="max-h-48 overflow-y-auto p-1 space-y-1">
                                <button
                                    v-for="b in $page.props.businesses"
                                    :key="b.id"
                                    type="button"
                                    @click="switchBusiness(b.id)"
                                    class="w-full flex items-center justify-between p-1.5 rounded-lg text-xs transition cursor-pointer text-start"
                                    :class="[
                                        $page.props.currentBusiness?.id === b.id
                                            ? 'bg-blue-600 text-white font-bold'
                                            : 'text-slate-300 hover:bg-slate-800'
                                    ]"
                                >
                                    <span class="truncate">{{ b.name }}</span>
                                    <Check v-if="$page.props.currentBusiness?.id === b.id" class="w-3.5 h-3.5 shrink-0" />
                                </button>
                            </div>
                            <div v-if="$page.props.auth.is_owner" class="pt-1 mt-1 border-t border-slate-800">
                                <Link
                                    :href="route('businesses.index')"
                                    @click="isBusinessDropdownOpen = false"
                                    class="w-full flex items-center justify-between px-2 py-1 text-[11px] font-semibold text-blue-400 hover:text-blue-300"
                                >
                                    <span>إدارة كافة المتاجر</span>
                                    <ArrowUpRight class="w-3 h-3" />
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Navigation Sections -->
            <div class="flex-1 overflow-y-auto p-3 space-y-5 text-xs">
                <!-- Group 1: Marketing & Growth -->
                <div class="space-y-1">
                    <span class="px-2.5 text-[10px] font-mono font-semibold text-slate-500 uppercase tracking-wider block mb-1">
                        التسويق والأداء
                    </span>

                    <Link
                        :href="route('dashboard')"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition"
                        :class="route().current('dashboard') ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
                    >
                        <LayoutDashboard class="w-4 h-4 shrink-0" />
                        <span>لوحة المؤشرات</span>
                    </Link>
                </div>

                <!-- Group 2: Unified Communications -->
                <div class="space-y-1">
                    <span class="px-2.5 text-[10px] font-mono font-semibold text-slate-500 uppercase tracking-wider block mb-1">
                        صندوق المحادثات
                    </span>

                    <Link
                        :href="route('inbox.index')"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition"
                        :class="route().current('inbox.*') ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
                    >
                        <Inbox class="w-4 h-4 shrink-0" />
                        <span>صندوق الوارد الموحد</span>
                    </Link>

                    <Link
                        :href="route('contacts.index')"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition"
                        :class="route().current('contacts.*') ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
                    >
                        <Users class="w-4 h-4 shrink-0" />
                        <span>سجل جهات الاتصال</span>
                    </Link>
                </div>

                <!-- Group 3: Setup & Management -->
                <div class="space-y-1">
                    <span class="px-2.5 text-[10px] font-mono font-semibold text-slate-500 uppercase tracking-wider block mb-1">
                        الإعدادات والربط
                    </span>

                    <Link
                        v-if="$page.props.currentBusiness"
                        :href="route('channels.index', $page.props.currentBusiness.id)"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition"
                        :class="route().current('channels.*') ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
                    >
                        <Radio class="w-4 h-4 shrink-0" />
                        <span>قنوات التواصل</span>
                    </Link>

                    <Link
                        v-if="$page.props.auth.is_owner"
                        :href="route('businesses.index')"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition"
                        :class="route().current('businesses.*') ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
                    >
                        <Store class="w-4 h-4 shrink-0" />
                        <span>إدارة المتاجر</span>
                    </Link>

                    <Link
                        v-if="$page.props.auth.can_manage_team"
                        :href="route('team.index')"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition"
                        :class="route().current('team.*') ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
                    >
                        <ShieldCheck class="w-4 h-4 shrink-0" />
                        <span>فريق العمل</span>
                    </Link>

                    <Link
                        v-if="$page.props.currentBusiness"
                        :href="route('ai-settings.show', $page.props.currentBusiness.id)"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition"
                        :class="route().current('ai-settings.*') ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
                    >
                        <Sparkles class="w-4 h-4 shrink-0 text-amber-400" />
                        <span>المساعد الذكي (AI)</span>
                    </Link>

                    <Link
                        v-if="$page.props.auth.is_owner"
                        :href="route('subscription.index')"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium transition"
                        :class="route().current('subscription.*') ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
                    >
                        <Crown class="w-4 h-4 shrink-0 text-amber-400" />
                        <span>الباقة والاشتراك</span>
                    </Link>
                </div>
            </div>

            <!-- PWA Install Prompt (if available) -->
            <div v-if="canInstallPwa" class="px-3 pt-2">
                <button
                    type="button"
                    @click="installPwa"
                    class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg transition cursor-pointer"
                >
                    <Download class="w-4 h-4" />
                    <span>تثبيت التطبيق على الهاتف</span>
                </button>
            </div>

            <!-- Message Quota Progress Bar -->
            <div v-if="$page.props.message_quota" class="p-3 border-t border-slate-800/80 bg-slate-950/40 text-[11px]">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-slate-400 font-medium">كوتة الرسائل الشهرية</span>
                    <span class="font-mono font-bold text-white">
                        {{ $page.props.message_quota.is_unlimited ? 'غير محدودة' : `${$page.props.message_quota.used} / ${$page.props.message_quota.limit || 0}` }}
                    </span>
                </div>
                <div v-if="!$page.props.message_quota.is_unlimited" class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="($page.props.message_quota.used / ($page.props.message_quota.limit || 1)) >= 0.9 ? 'bg-rose-500' : 'bg-blue-500'"
                        :style="{ width: Math.min(100, Math.round(($page.props.message_quota.used / ($page.props.message_quota.limit || 1)) * 100)) + '%' }"
                    ></div>
                </div>
                <div v-if="!$page.props.message_quota.is_unlimited && $page.props.message_quota.remaining !== null" class="flex justify-between text-[9px] text-slate-500 mt-1">
                    <span>متبقي: {{ $page.props.message_quota.remaining }} رسالة</span>
                    <span v-if="$page.props.message_quota.resets_at">تتجدد: {{ $page.props.message_quota.resets_at }}</span>
                </div>
            </div>

            <!-- Footer: User Profile & Logout -->
            <div class="p-3 border-t border-slate-800/80 bg-slate-900/60">
                <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-blue-600/30 border border-blue-500/40 text-blue-400 flex items-center justify-center font-bold text-xs shrink-0">
                            {{ $page.props.auth.user?.name?.charAt(0) || 'U' }}
                        </div>
                        <div class="min-w-0 text-start">
                            <span class="font-bold text-xs text-white block truncate">{{ $page.props.auth.user?.name }}</span>
                            <span class="text-[10px] text-slate-400 block truncate">{{ $page.props.auth.is_owner ? 'المالك' : 'عضو فريق' }}</span>
                        </div>
                    </div>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition cursor-pointer"
                        title="تسجيل الخروج"
                    >
                        <LogOut class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Floating Button to Re-open Desktop Sidebar When Collapsed -->
        <button
            v-if="isSidebarCollapsed"
            type="button"
            @click="toggleSidebar"
            class="hidden md:flex fixed top-3 start-3 z-40 items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900/95 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700/80 shadow-2xl backdrop-blur-md transition cursor-pointer"
            title="إظهار القائمة الجانبية"
        >
            <PanelRightOpen class="w-4 h-4 text-blue-400" />
            <span class="text-xs font-bold">القائمة</span>
        </button>

        <!-- ======================================================== -->
        <!-- 2. MOBILE TOP BAR & SLIDE-OVER DRAWER -->
        <!-- ======================================================== -->
        <div class="md:hidden fixed top-0 inset-x-0 z-40 bg-slate-900 border-b border-slate-800 h-14 flex items-center justify-between px-3">
            <button
                type="button"
                @click="isMobileNavOpen = !isMobileNavOpen"
                class="p-2 -ms-1 text-slate-300 hover:text-white rounded-lg active:bg-slate-800 cursor-pointer"
                aria-label="القائمة الرئيسية"
            >
                <Menu class="w-6 h-6" />
            </button>

            <Link :href="route('dashboard')" class="flex items-center gap-2">
                <ApplicationLogo class="h-6 w-auto text-blue-500" />
                <span class="font-black text-sm tracking-tight text-white">VERTEX</span>
            </Link>

            <div class="flex items-center gap-1.5 min-w-0 max-w-[120px]">
                <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                <span class="text-xs font-bold text-slate-300 truncate">
                    {{ $page.props.currentBusiness?.name || 'اختر المتجر' }}
                </span>
            </div>
        </div>

        <!-- Mobile Drawer Backdrop & Menu -->
        <div
            v-if="isMobileNavOpen"
            class="md:hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex"
        >
            <div class="w-72 max-w-[80vw] bg-slate-900 border-e border-slate-800 h-full flex flex-col p-4 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <ApplicationLogo class="h-6 w-auto text-blue-500" />
                        <span class="font-black text-sm text-white">VERTEX</span>
                    </div>
                    <button
                        type="button"
                        @click="isMobileNavOpen = false"
                        class="p-1.5 text-slate-400 hover:text-white rounded-lg active:bg-slate-800"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Mobile Business Switcher -->
                <div v-if="$page.props.businesses && $page.props.businesses.length > 1" class="border-b border-slate-800 pb-3">
                    <label class="text-[10px] font-mono text-slate-400 uppercase tracking-wider block mb-1.5">تبديل المتجر</label>
                    <div class="max-h-32 overflow-y-auto space-y-1">
                        <button
                            v-for="b in $page.props.businesses"
                            :key="b.id"
                            type="button"
                            @click="switchBusiness(b.id)"
                            class="w-full flex items-center justify-between p-2 rounded-lg text-xs transition text-start"
                            :class="$page.props.currentBusiness?.id === b.id ? 'bg-blue-600 text-white font-bold' : 'text-slate-300 hover:bg-slate-800'"
                        >
                            <span class="truncate">{{ b.name }}</span>
                            <Check v-if="$page.props.currentBusiness?.id === b.id" class="w-3.5 h-3.5 shrink-0" />
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto space-y-1.5 text-xs">
                    <Link
                        :href="route('dashboard')"
                        @click="isMobileNavOpen = false"
                        class="block p-2.5 rounded-lg text-slate-300 hover:bg-slate-800 font-bold"
                    >
                        لوحة المؤشرات
                    </Link>
                    <Link
                        :href="route('inbox.index')"
                        @click="isMobileNavOpen = false"
                        class="block p-2.5 rounded-lg text-slate-300 hover:bg-slate-800 font-bold"
                    >
                        صندوق الوارد
                    </Link>
                    <Link
                        :href="route('contacts.index')"
                        @click="isMobileNavOpen = false"
                        class="block p-2.5 rounded-lg text-slate-300 hover:bg-slate-800 font-bold"
                    >
                        جهات الاتصال
                    </Link>
                    <Link
                        v-if="$page.props.currentBusiness"
                        :href="route('channels.index', $page.props.currentBusiness.id)"
                        @click="isMobileNavOpen = false"
                        class="block p-2.5 rounded-lg text-slate-300 hover:bg-slate-800 font-bold"
                    >
                        قنوات التواصل
                    </Link>
                    <Link
                        v-if="$page.props.auth.is_owner"
                        :href="route('businesses.index')"
                        @click="isMobileNavOpen = false"
                        class="block p-2.5 rounded-lg text-slate-300 hover:bg-slate-800 font-bold"
                    >
                        إدارة المتاجر
                    </Link>
                    <Link
                        v-if="$page.props.auth.can_manage_team"
                        :href="route('team.index')"
                        @click="isMobileNavOpen = false"
                        class="block p-2.5 rounded-lg text-slate-300 hover:bg-slate-800 font-bold"
                    >
                        فريق العمل
                    </Link>
                    <Link
                        v-if="$page.props.currentBusiness"
                        :href="route('ai-settings.show', $page.props.currentBusiness.id)"
                        @click="isMobileNavOpen = false"
                        class="block p-2.5 rounded-lg text-slate-300 hover:bg-slate-800 font-bold text-amber-300"
                    >
                        المساعد الذكي (AI)
                    </Link>
                    <Link
                        v-if="$page.props.auth.is_owner"
                        :href="route('subscription.index')"
                        @click="isMobileNavOpen = false"
                        class="block p-2.5 rounded-lg text-slate-300 hover:bg-slate-800 font-bold"
                    >
                        الاشتراك والباقات
                    </Link>
                    <Link
                        v-if="$page.props.auth.is_super_admin"
                        :href="route('superadmin.index')"
                        @click="isMobileNavOpen = false"
                        class="block p-2.5 rounded-lg text-amber-400 hover:bg-amber-500/10 font-bold border border-amber-500/30"
                    >
                        لوحة الإدارة العليا (Super Admin)
                    </Link>
                </div>

                <div class="border-t border-slate-800 pt-3">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-full py-2 text-center text-xs font-bold text-rose-400 hover:bg-rose-500/10 rounded-lg"
                    >
                        تسجيل الخروج
                    </Link>
                </div>
            </div>

            <div class="flex-1" @click="isMobileNavOpen = false"></div>
        </div>

        <!-- ======================================================== -->
        <!-- 3. MAIN CONTENT WRAPPER -->
        <!-- ======================================================== -->
        <div
            class="flex-1 flex flex-col min-w-0"
            :class="[
                fullHeight ? 'h-full overflow-hidden' : 'min-h-screen',
                'md:mt-0 mt-14'
            ]"
        >
            <!-- Optional Sub-Header Slot -->
            <header
                class="shrink-0 bg-slate-900/60 border-b border-slate-800/80 backdrop-blur-xs"
                v-if="$slots.header"
            >
                <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Floating Toast Notifications -->
            <div class="fixed bottom-5 end-5 z-50 flex flex-col gap-2 max-w-sm pointer-events-none">
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 -translate-y-2 scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 -translate-y-2 scale-95"
                >
                    <div
                        v-if="flashMessage"
                        class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl border text-xs font-semibold backdrop-blur-md"
                        :class="flashType === 'success'
                            ? 'bg-emerald-950/90 border-emerald-500/50 text-emerald-200 shadow-emerald-950/50'
                            : 'bg-rose-950/90 border-rose-500/50 text-rose-200 shadow-rose-950/50'"
                    >
                        <span class="w-2 h-2 rounded-full shrink-0" :class="flashType === 'success' ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                        <span class="flex-1">{{ flashMessage }}</span>
                        <button type="button" @click="dismissFlash" class="p-1 hover:opacity-75 transition cursor-pointer">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </Transition>
            </div>

            <!-- Page Main Content Slot -->
            <main
                class="flex-1 flex flex-col min-w-0"
                :class="fullHeight ? 'min-h-0 overflow-hidden' : 'overflow-y-auto pb-16 md:pb-0'"
            >
                <slot />
            </main>
        </div>

        <!-- ======================================================== -->
        <!-- 4. MOBILE BOTTOM NAVIGATION BAR (md:hidden) -->
        <!-- ======================================================== -->
        <nav
            class="md:hidden fixed bottom-0 inset-x-0 h-16 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 z-40 flex items-center justify-around px-2 pb-safe"
        >
            <Link
                :href="route('dashboard')"
                class="flex flex-col items-center justify-center gap-1 flex-1 py-1 transition"
                :class="route().current('dashboard') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
            >
                <LayoutDashboard class="w-5 h-5" />
                <span class="text-[10px]">الرئيسية</span>
            </Link>

            <Link
                :href="route('inbox.index')"
                class="flex flex-col items-center justify-center gap-1 flex-1 py-1 transition relative"
                :class="route().current('inbox.*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
            >
                <Inbox class="w-5 h-5" />
                <span class="text-[10px]">الوارد</span>
            </Link>

            <Link
                v-if="$page.props.currentBusiness"
                :href="route('channels.index', $page.props.currentBusiness.id)"
                class="flex flex-col items-center justify-center gap-1 flex-1 py-1 transition"
                :class="route().current('channels.*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
            >
                <Radio class="w-5 h-5" />
                <span class="text-[10px]">القنوات</span>
            </Link>

            <Link
                :href="route('contacts.index')"
                class="flex flex-col items-center justify-center gap-1 flex-1 py-1 transition"
                :class="route().current('contacts.*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
            >
                <Users class="w-5 h-5" />
                <span class="text-[10px]">العملاء</span>
            </Link>

            <button
                type="button"
                @click="isMobileNavOpen = true"
                class="flex flex-col items-center justify-center gap-1 flex-1 py-1 text-slate-400 hover:text-slate-200 transition cursor-pointer"
            >
                <Menu class="w-5 h-5" />
                <span class="text-[10px]">المزيد</span>
            </button>
        </nav>
    </div>
</template>
