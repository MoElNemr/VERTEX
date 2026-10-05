<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    fullHeight: {
        type: Boolean,
        default: false,
    },
});

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="h-full flex flex-col bg-slate-950 text-slate-100 overflow-hidden">
        <nav class="shrink-0 h-16 border-b border-slate-800 bg-slate-900/95 backdrop-blur z-30">
            <!-- Primary Navigation Menu -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <div class="flex items-center">
                        <!-- Logo -->
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo
                                    class="block h-8 w-auto fill-current text-blue-500"
                                />
                            </Link>
                        </div>

                        <!-- Navigation Links -->
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
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <!-- Business Switcher Dropdown -->
                        <div class="relative ms-3" v-if="$page.props.businesses && $page.props.businesses.length > 0">
                            <Dropdown align="left" width="60">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/90 px-3.5 py-1.5 text-xs font-semibold text-slate-200 shadow-sm transition hover:bg-slate-750 hover:border-slate-600 focus:outline-none cursor-pointer"
                                    >
                                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></span>
                                        <span class="max-w-[140px] truncate">
                                            {{ $page.props.currentBusiness ? $page.props.currentBusiness.name : 'اختر المتجر' }}
                                        </span>
                                        <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="px-4 py-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-700/80 text-right">
                                        تبديل المتجر
                                    </div>
                                    <div class="max-h-56 overflow-y-auto divide-y divide-slate-800/60">
                                        <DropdownLink
                                            v-for="b in $page.props.businesses"
                                            :key="b.id"
                                            :href="route('businesses.switch', b.id)"
                                            method="post"
                                            as="button"
                                            class="flex items-center justify-between w-full text-right cursor-pointer text-xs"
                                        >
                                            <span>{{ b.name }}</span>
                                            <span v-if="$page.props.currentBusiness && $page.props.currentBusiness.id === b.id" class="text-xs text-blue-400 font-bold">✓</span>
                                        </DropdownLink>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="relative ms-3">
                            <Dropdown align="left" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs font-medium text-slate-300 transition duration-150 ease-in-out hover:bg-slate-800 hover:text-white focus:outline-none cursor-pointer"
                                        >
                                            <span>{{ $page.props.auth.user.name }}</span>

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

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-slate-200 focus:outline-none"
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

            <!-- Responsive Navigation Menu -->
            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="sm:hidden border-b border-slate-800 bg-slate-900"
            >
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
                </div>

                <!-- Responsive Settings Options -->
                <div class="border-t border-slate-800 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-semibold text-slate-200">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-xs text-slate-400">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            الملف الشخصي
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('logout')"
                            method="post"
                            as="button"
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

        <!-- Page Content -->
        <main
            class="flex-1 min-h-0 flex flex-col"
            :class="fullHeight ? 'overflow-hidden' : 'overflow-y-auto'"
        >
            <slot />
        </main>
    </div>
</template>
