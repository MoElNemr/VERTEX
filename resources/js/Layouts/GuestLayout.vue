<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Sparkles, ShieldCheck, Zap } from 'lucide-vue-next';
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-slate-950 text-slate-100 relative overflow-hidden p-4 sm:p-6 selection:bg-blue-600 selection:text-white font-sans">

        <!-- Subtle Background Glow Effects & Grid Pattern -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b15_1px,transparent_1px),linear-gradient(to_bottom,#1e293b15_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none"></div>
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-96 h-96 bg-blue-600/15 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute -bottom-32 right-10 w-96 h-96 bg-purple-600/10 rounded-full blur-[140px] pointer-events-none"></div>

        <!-- Back to Home Link -->
        <div class="w-full max-w-md flex items-center justify-between mb-4 z-10">
            <Link
                href="/"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition group"
            >
                <ArrowRight class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition" />
                <span>العودة للرئيسية</span>
            </Link>

            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Vertex Cloud</span>
            </span>
        </div>

        <!-- Brand Logo Header -->
        <div class="mb-5 text-center z-10">
            <Link href="/" class="inline-flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 shadow-lg shadow-blue-500/10 group-hover:scale-105 transition">
                    <ApplicationLogo class="h-6 w-auto fill-current" />
                </div>
                <div class="text-start">
                    <span class="font-extrabold text-lg tracking-tight text-white block">VERTEX</span>
                    <span class="text-[10px] font-mono text-slate-400 block -mt-1">Marketing & Automation Platform</span>
                </div>
            </Link>
        </div>

        <!-- Main Card Container -->
        <div
            class="w-full max-w-md rounded-2xl bg-slate-900/90 border border-slate-800 shadow-2xl shadow-black/80 backdrop-blur-xl p-6 sm:p-8 z-10 relative overflow-hidden"
        >
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>
            <slot />
        </div>

        <!-- Trust / Security Footer -->
        <div class="mt-6 text-center text-slate-500 text-xs z-10 flex items-center justify-center gap-4">
            <span class="flex items-center gap-1">
                <ShieldCheck class="w-3.5 h-3.5 text-emerald-400" />
                <span>تشفير آمن للبيانات</span>
            </span>
            <span>•</span>
            <span class="flex items-center gap-1">
                <Zap class="w-3.5 h-3.5 text-amber-400" />
                <span>سرعة استجابة فورية</span>
            </span>
        </div>

    </div>
</template>
