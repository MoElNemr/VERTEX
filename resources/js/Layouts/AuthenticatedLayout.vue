<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router } from '@inertiajs/vue3';
import {
    Store,
    Check,
    ChevronDown,
    ArrowUpRight,
    Loader2,
} from 'lucide-vue-next';

const props = defineProps({
    fullHeight: {
        type: Boolean,
        default: false,
    },
});

const showingNavigationDropdown = ref(false);
const isBusinessDropdownOpen = ref(false);
const switchingBusinessId = ref(null);

const toggleBusinessDropdown = () => {
    isBusinessDropdownOpen.value = !isBusinessDropdownOpen.value;
};

const switchBusiness = (id) => {
    if (switchingBusinessId.value) return;
    switchingBusinessId.value = id;
    isBusinessDropdownOpen.value = false;
    showingNavigationDropdown.value = false;

    router.post(route('businesses.switch', id), {}, {
        preserveScroll: true,
        onFinish: () => {
            switchingBusinessId.value = null;
        },
    });
};
</script>

<template>
    <div
        class="flex flex-col bg-slate-950 text-slate-100"
        :class="fullHeight ? 'h-full overflow-hidden' : 'min-h-screen'"
    >
        <nav class="shrink-0 h-16 border-b border-slate-800 bg-slate-900/95 backdrop-blur z-30 sticky top-0">
            <!-- Primary Navigation Menu -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <div class="flex items-center gap-3">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo
                                    class="block h-8 w-auto fill-current text-blue-500"
                                />
                            </Link>
                        </div>

                        <!-- Mobile Active Business Pill -->
                        <div
                            v-if="$page.props.currentBusiness"
                            class="sm:hidden relative"
                        >
                            <button
                                type="button"
                                @click="toggleBusinessDropdown"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-850 border border-slate-800 text-[11px] font-semibold text-slate-300 max-w-[140px] truncate hover:border-slate-700 cursor-pointer"
                                :class="{ 'ring-2 ring-blue-500/40 border-blue-500/80': isBusinessDropdownOpen }"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                <span class="truncate">{{ $page.props.currentBusiness.name }}</span>
                                <ChevronDown class="w-3 h-3 text-slate-400 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': isBusinessDropdownOpen }" />
                            </button>

                            <!-- Mobile Backdrop -->
                            <div
                                v-if="isBusinessDropdownOpen"
                                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-xs"
                                @click="isBusinessDropdownOpen = false"
                            ></div>

                            <!-- Mobile Dropdown Menu -->
                            <Transition
                                enter-active-class="transition ease-out duration-150"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <div
                                    v-if="isBusinessDropdownOpen"
                                    class="absolute z-50 mt-2 w-64 max-w-[calc(100vw-2rem)] rounded-2xl bg-slate-900 border border-slate-750 shadow-2xl p-2 start-0 ltr:origin-top-left rtl:origin-top-right overflow-hidden"
                                >
                                    <div class="px-2.5 py-1.5 flex items-center justify-between border-b border-slate-800/85">
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-300">
                                            <Store class="w-3.5 h-3.5 text-blue-400" />
                                            <span>تبديل المتجر</span>
                                        </div>
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700/60">
                                            {{ $page.props.businesses.length }}
                                        </span>
                                    </div>

                                    <div class="max-h-56 overflow-y-auto p-1 space-y-1">
                                        <button
                                            v-for="b in $page.props.businesses"
                                            :key="b.id"
                                            type="button"
                                            @click="switchBusiness(b.id)"
                                            class="w-full flex items-center justify-between p-2 rounded-xl text-xs font-medium transition cursor-pointer text-start"
                                            :class="[
                                                $page.props.currentBusiness?.id === b.id
                                                    ? 'bg-blue-950/40 border border-blue-600/60 text-white shadow-sm'
                                                    : 'text-slate-300 hover:bg-slate-800/80 hover:text-white border border-transparent'
                                            ]"
                                        >
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div
                                                    class="w-6 h-6 rounded-lg flex items-center justify-center font-bold text-[10px] shrink-0"
                                                    :class="[
                                                        $page.props.currentBusiness?.id === b.id
                                                            ? 'bg-blue-600 text-white'
                                                            : 'bg-slate-800 text-slate-300'
                                                    ]"
                                                >
                                                    {{ b.name.charAt(0).toUpperCase() }}
                                                </div>
                                                <div class="truncate text-start">
                                                    <div class="font-bold text-xs truncate">{{ b.name }}</div>
                                                </div>
                                            </div>

                                            <div class="shrink-0 ps-2">
                                                <Loader2
                                                    v-if="switchingBusinessId === b.id"
                                                    class="w-3.5 h-3.5 text-blue-400 animate-spin"
                                                />
                                                <Check
                                                    v-else-if="$page.props.currentBusiness?.id === b.id"
                                                    class="w-3.5 h-3.5 text-emerald-400"
                                                />
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Desktop Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                            >
                                الرئيسية
                            </NavLink>
                            <NavLink
                                :href="route('inbox.index')"
                                :active="route().current('inbox.*')"
                            >
                                صندوق الوارد
                            </NavLink>
                            <NavLink
                                v-if="$page.props.auth.is_owner"
                                :href="route('businesses.index')"
                                :active="route().current('businesses.*')"
                            >
                                المتاجر
                            </NavLink>
                            <NavLink
                                v-if="$page.props.auth.can_manage_team"
                                :href="route('team.index')"
                                :active="route().current('team.*')"
                            >
                                فريق العمل
                            </NavLink>
                        </div>
                    </div>

                    <!-- Desktop Actions -->
                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <!-- Business Switcher Dropdown -->
                        <div
                            class="relative ms-3"
                            v-if="$page.props.businesses && $page.props.businesses.length > 0"
                        >
                            <button
                                type="button"
                                @click="toggleBusinessDropdown"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/90 px-3.5 py-1.5 text-xs font-semibold text-slate-200 shadow-sm transition hover:bg-slate-750 hover:border-slate-600 focus:outline-none cursor-pointer"
                                :class="{ 'ring-2 ring-blue-500/40 border-blue-500/80': isBusinessDropdownOpen }"
                            >
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span class="max-w-[150px] truncate font-bold text-slate-100">
                                    {{ $page.props.currentBusiness ? $page.props.currentBusiness.name : 'اختر المتجر' }}
                                </span>
                                <Loader2 v-if="switchingBusinessId" class="w-3.5 h-3.5 text-blue-400 animate-spin" />
                                <ChevronDown
                                    v-else
                                    class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': isBusinessDropdownOpen }"
                                />
                            </button>

                            <!-- Backdrop for Outside Click -->
                            <div
                                v-if="isBusinessDropdownOpen"
                                class="fixed inset-0 z-40"
                                @click="isBusinessDropdownOpen = false"
                            ></div>

                            <!-- Dropdown Menu -->
                            <Transition
                                enter-active-class="transition ease-out duration-150"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <div
                                    v-if="isBusinessDropdownOpen"
                                    class="absolute z-50 mt-2 w-72 rounded-2xl bg-slate-900 border border-slate-750 shadow-2xl shadow-black/80 p-2 start-0 ltr:origin-top-left rtl:origin-top-right overflow-hidden"
                                >
                                    <!-- Header -->
                                    <div class="px-3 py-2 flex items-center justify-between border-b border-slate-800/85">
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-300">
                                            <Store class="w-4 h-4 text-blue-400" />
                                            <span>المتاجر وبيئات العمل</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700/60">
                                            {{ $page.props.businesses.length }}
                                        </span>
                                    </div>

                                    <!-- Stores List -->
                                    <div class="max-h-64 overflow-y-auto p-1 space-y-1">
                                        <button
                                            v-for="b in $page.props.businesses"
                                            :key="b.id"
                                            type="button"
                                            @click="switchBusiness(b.id)"
                                            class="w-full flex items-center justify-between p-2 rounded-xl text-xs font-medium transition cursor-pointer text-start group"
                                            :class="[
                                                $page.props.currentBusiness?.id === b.id
                                                    ? 'bg-blue-950/40 border border-blue-600/60 text-white shadow-sm'
                                                    : 'text-slate-300 hover:bg-slate-800/80 hover:text-white border border-transparent'
                                            ]"
                                        >
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div
                                                    class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 transition"
                                                    :class="[
                                                        $page.props.currentBusiness?.id === b.id
                                                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/50'
                                                            : 'bg-slate-850 text-slate-300 group-hover:bg-slate-750 group-hover:text-white'
                                                    ]"
                                                >
                                                    {{ b.name.charAt(0).toUpperCase() }}
                                                </div>
                                                <div class="truncate text-start">
                                                    <div class="font-bold text-xs truncate">{{ b.name }}</div>
                                                    <div v-if="$page.props.currentBusiness?.id === b.id" class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1 mt-0.5">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                        <span>المتجر النشط</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="shrink-0 ps-2">
                                                <Loader2
                                                    v-if="switchingBusinessId === b.id"
                                                    class="w-4 h-4 text-blue-400 animate-spin"
                                                />
                                                <Check
                                                    v-else-if="$page.props.currentBusiness?.id === b.id"
                                                    class="w-4 h-4 text-emerald-400"
                                                />
                                            </div>
                                        </button>
                                    </div>

                                    <!-- Footer link if owner -->
                                    <div
                                        v-if="$page.props.auth.is_owner"
                                        class="pt-2 mt-1 border-t border-slate-800/80 px-1"
                                    >
                                        <Link
                                            :href="route('businesses.index')"
                                            @click="isBusinessDropdownOpen = false"
                                            class="w-full flex items-center justify-between p-2 rounded-xl text-[11px] font-bold text-blue-400 hover:bg-blue-950/30 hover:text-blue-300 transition"
                                        >
                                            <span>إدارة كافة المتاجر</span>
                                            <ArrowUpRight class="w-3.5 h-3.5" />
                                        </Link>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="relative ms-3">
                            <Dropdown align="left" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs font-medium text-slate-300 transition duration-150 ease-in-out hover:bg-slate-800 hover:text-white focus:outline-none cursor-pointer"
                                        >
                                            <span>{{ $page.props.auth.user.name }}</span>
                                            <span
                                                class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                                :class="{
                                                    'bg-blue-900/60 text-blue-300 border border-blue-700/50': $page.props.auth.role === 'owner',
                                                    'bg-purple-900/60 text-purple-300 border border-purple-700/50': $page.props.auth.role === 'admin',
                                                    'bg-amber-900/60 text-amber-300 border border-amber-700/50': $page.props.auth.role === 'supervisor',
                                                    'bg-emerald-900/60 text-emerald-300 border border-emerald-700/50': $page.props.auth.role === 'agent',
                                                }"
                                            >
                                                {{
                                                    $page.props.auth.role === 'owner' ? 'مالك' :
                                                    $page.props.auth.role === 'admin' ? 'مسؤول' :
                                                    $page.props.auth.role === 'supervisor' ? 'مشرف' : 'وكيل'
                                                }}
                                            </span>

                                            <svg
                                                class="h-3.5 w-3.5 text-slate-400"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink
                                        :href="route('profile.edit')"
                                        class="text-right"
                                    >
                                        الملف الشخصي
                                    </DropdownLink>
                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="text-right text-rose-400 hover:text-rose-300"
                                    >
                                        تسجيل الخروج
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Mobile Hamburger Button -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-xl p-2.5 text-slate-400 transition hover:bg-slate-800 hover:text-slate-200 focus:outline-none"
                            aria-label="القائمة الرئيسية"
                        >
                            <svg
                                class="h-6 w-6"
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex': !showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex': showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Mobile Navigation Menu -->
            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="sm:hidden border-b border-slate-800 bg-slate-900/98 backdrop-blur max-h-[calc(100vh-4rem)] overflow-y-auto"
            >
                <!-- Navigation Links -->
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink
                        :href="route('dashboard')"
                        :active="route().current('dashboard')"
                    >
                        الرئيسية
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        :href="route('inbox.index')"
                        :active="route().current('inbox.*')"
                    >
                        صندوق الوارد
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        v-if="$page.props.auth.is_owner"
                        :href="route('businesses.index')"
                        :active="route().current('businesses.*')"
                    >
                        المتاجر
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        v-if="$page.props.auth.can_manage_team"
                        :href="route('team.index')"
                        :active="route().current('team.*')"
                    >
                        فريق العمل
                    </ResponsiveNavLink>
                </div>

                <!-- Business Switcher for Mobile -->
                <div
                    v-if="$page.props.businesses && $page.props.businesses.length > 0"
                    class="border-t border-slate-800 px-4 py-3 bg-slate-950/40"
                >
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">
                        المتاجر وبيئات العمل
                    </span>
                    <div class="space-y-1 max-h-40 overflow-y-auto divide-y divide-slate-800/40">
                        <button
                            v-for="b in $page.props.businesses"
                            :key="b.id"
                            @click="switchBusiness(b.id)"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition cursor-pointer text-right"
                            :class="$page.props.currentBusiness?.id === b.id ? 'bg-blue-600 text-white font-bold' : 'text-slate-300 hover:bg-slate-800'"
                        >
                            <span>{{ b.name }}</span>
                            <span v-if="$page.props.currentBusiness?.id === b.id" class="text-xs font-bold">✓</span>
                        </button>
                    </div>
                </div>

                <!-- Responsive User Settings Options -->
                <div class="border-t border-slate-800 pb-3 pt-4 bg-slate-900">
                    <div class="px-4 mb-2">
                        <div class="text-sm font-bold text-slate-200">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-xs text-slate-400">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            الملف الشخصي
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-rose-400 hover:text-rose-300"
                        >
                            تسجيل الخروج
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header
            class="shrink-0 bg-slate-900/60 border-b border-slate-800"
            v-if="$slots.header"
        >
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Flash Notifications -->
        <div v-if="$page.props.flash?.success" class="shrink-0 bg-emerald-600/90 text-white px-4 py-2 text-xs text-center font-bold shadow">
            {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="shrink-0 bg-rose-600/90 text-white px-4 py-2 text-xs text-center font-bold shadow">
            {{ $page.props.flash.error }}
        </div>

        <!-- Page Content -->
        <main
            class="flex-1 flex flex-col"
            :class="fullHeight ? 'min-h-0 overflow-hidden' : ''"
        >
            <slot />
        </main>
    </div>
</template>
