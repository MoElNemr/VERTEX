<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import {
    Sparkles,
    ShieldCheck,
    Zap,
    Send,
    Repeat,
    Inbox,
    MessageSquare,
    CheckCircle2,
    ArrowLeft,
    Radio,
    TrendingUp,
    Layers,
    Bot,
    Users,
    Check,
    ExternalLink,
    ChevronDown,
    Flame,
    Lock,
    Store,
    Smartphone,
    Globe
} from 'lucide-vue-next';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});

const faqOpen = ref([0]);

const toggleFaq = (index) => {
    if (faqOpen.value.includes(index)) {
        faqOpen.value = faqOpen.value.filter(i => i !== index);
    } else {
        faqOpen.value.push(index);
    }
};

const faqs = [
    {
        q: 'كيف تضمن منصة Vertex حماية رقم الواتساب من الحظر؟',
        a: 'نستخدم نظام جدولة متقدم يطبق فترات زمنية متغيرة (Jitter) وتأخير تدريجي ذكي بين كل رسالة وأخرى، مما يحاكي السلوك البشري الطبيعي تماماً ويمنع خوارزميات واتساب من رصد النشاط كإرسال آلي مكثف.'
    },
    {
        q: 'هل يمكن ربط أكثر من منصة معاً لنفس المتجر؟',
        a: 'نعم! يمكنك ربط واتساب وتيليجرام وفيسبوك ماسنجر وإنستجرام في متجر واحد، والرد على جميع الرسائل الواردة من صندوق وارد موحد دون الحاجة لفتح كل تطبيق على حدة.'
    },
    {
        q: 'هل يمكن لفريق العمل العمل معي على نفس الحساب؟',
        a: 'بالتأكيد، يمكنك إضافة أعضاء فريق بصلاحيات محددة (مسؤول، مشرف، وكيل دعم) وتعيين المحادثات لهم وتتبع أدائهم بدقة.'
    },
    {
        q: 'كيف يعمل الرد الآلي بالذكاء الاصطناعي؟',
        a: 'يمكنك تفعيل الرد الآلي خارج ساعات العمل أو على مدار الساعة، حيث يقوم المساعد الذكي بالرد على أسئلة العملاء واستفسارات المنتجات بأسلوب طبيعي ومخصص لمتجرك.'
    }
];
</script>

<template>
    <Head title="Vertex - منصة أتمتة التسويق والمراسلة الذكية للمتاجر" />

    <div class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-blue-600 selection:text-white relative overflow-hidden" dir="rtl">

        <!-- Background Ambient Lights & Grid -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b12_1px,transparent_1px),linear-gradient(to_bottom,#1e293b12_1px,transparent_1px)] bg-[size:3.5rem_3.5rem] pointer-events-none"></div>
        <div class="absolute top-0 right-1/4 w-[500px] h-[500px] bg-blue-600/10 rounded-full blur-[140px] pointer-events-none"></div>
        <div class="absolute top-1/3 left-10 w-[450px] h-[450px] bg-purple-600/10 rounded-full blur-[150px] pointer-events-none"></div>

        <!-- ========================================== -->
        <!-- 1. NAVIGATION BAR                          -->
        <!-- ========================================== -->
        <header class="sticky top-0 z-40 backdrop-blur-xl bg-slate-950/80 border-b border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Brand Logo -->
                <Link href="/" class="flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 group-hover:scale-105 transition shadow-lg shadow-blue-500/10">
                        <ApplicationLogo class="h-5 w-auto fill-current" />
                    </div>
                    <div>
                        <span class="font-black text-base tracking-tight text-white block">VERTEX</span>
                        <span class="text-[9px] font-mono text-slate-400 block -mt-1">Marketing Cloud</span>
                    </div>
                </Link>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden md:flex items-center gap-7 text-xs font-semibold text-slate-400">
                    <a href="#features" class="hover:text-white transition">المميزات</a>
                    <a href="#channels" class="hover:text-white transition">القنوات المدعومة</a>
                    <a href="#how-it-works" class="hover:text-white transition">كيف يعمل؟</a>
                    <a href="#pricing" class="hover:text-white transition">الأسعار</a>
                    <a href="#faq" class="hover:text-white transition">الأسئلة الشائعة</a>
                </nav>

                <!-- Auth Buttons -->
                <div class="flex items-center gap-3">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="route('dashboard')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/25 transition"
                        >
                            <span>لوحة التحكم</span>
                            <ArrowLeft class="w-3.5 h-3.5" />
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-900 transition"
                        >
                            تسجيل الدخول
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/25 transition"
                        >
                            <span>ابدأ مجاناً</span>
                            <ArrowLeft class="w-3.5 h-3.5" />
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- ========================================== -->
        <!-- 2. HERO SECTION                            -->
        <!-- ========================================== -->
        <section class="relative pt-16 pb-20 sm:pt-24 sm:pb-28 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Announcement Pill -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-slate-900 border border-slate-800 text-slate-300 mb-6 shadow-md hover:border-slate-700 transition">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <span>الجيل الجديد لأتمتة التسويق والمراسلة الجماعية</span>
                <span class="px-1.5 py-0.2 rounded text-[10px] font-mono bg-blue-500/20 text-blue-400 font-bold">v2.4</span>
            </div>

            <!-- Main Headline -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white max-w-4xl mx-auto leading-tight sm:leading-tight">
                منصة واحدة لتوحيد قنوات البيع وأتمتة حملات المتاجر الذكية
            </h1>

            <!-- Subtitle -->
            <p class="mt-5 text-sm sm:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed">
                اربط <span class="text-emerald-400 font-semibold">واتساب</span>، <span class="text-sky-400 font-semibold">تيليجرام</span>، <span class="text-blue-400 font-semibold">فيسبوك</span>، و<span class="text-pink-400 font-semibold">إنستجرام</span> في مكان واحد. أرسل حملات إعادة استهداف ذكية، وارفع جهات اتصالك بملف CSV، وتفادى الحظر بتقنية الـ Jitter المتطورة.
            </p>

            <!-- CTA Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3.5">
                <Link
                    :href="route('register')"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-xl shadow-blue-600/30 transition transform hover:-translate-y-0.5"
                >
                    <Sparkles class="w-4 h-4 text-blue-200" />
                    <span>ابدأ تجربتك المجانية (14 يوماً مجاناً)</span>
                    <ArrowLeft class="w-4 h-4" />
                </Link>

                <Link
                    :href="route('login')"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-850 text-slate-200 border border-slate-800 font-bold text-sm transition"
                >
                    <span>دخول أصحاب المتاجر</span>
                </Link>
            </div>

            <!-- Trust Bar -->
            <div class="mt-8 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-500">
                <span class="flex items-center gap-1.5">
                    <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                    <span>بدون بطاقة ائتمان</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                    <span>جاهز للعمل في دقيقتين</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                    <span>حماية مدمجة ضد حظر الواتساب</span>
                </span>
            </div>

            <!-- ========================================== -->
            <!-- 3. INTERACTIVE HERO DASHBOARD PREVIEW      -->
            <!-- ========================================== -->
            <div class="mt-14 relative max-w-5xl mx-auto rounded-2xl bg-gradient-to-b from-slate-900 to-slate-950 border border-slate-800 p-2 sm:p-4 shadow-2xl shadow-blue-950/40">
                <div class="rounded-xl bg-slate-950 border border-slate-850 p-4 sm:p-6 space-y-5 text-start">
                    <!-- Top Browser Bar -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-850">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                            <span class="text-[11px] font-mono text-slate-500 ms-2">app.vertex.cloud/dashboard</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="text-[10px] font-mono font-bold text-emerald-400">All Systems Operational</span>
                        </div>
                    </div>

                    <!-- Bento Preview Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-[11px] text-slate-400 block mb-1">عائد الاستثمار (ROI)</span>
                            <span class="text-xl font-black text-emerald-400 block">+340%</span>
                            <span class="text-[10px] text-slate-500 mt-0.5 block">مقارنة بالإعلانات التقليدية</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-[11px] text-slate-400 block mb-1">الرسائل الموجهة</span>
                            <span class="text-xl font-black text-white block">14,250</span>
                            <span class="text-[10px] text-blue-400 mt-0.5 block">نسبة وصول 99.8%</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-[11px] text-slate-400 block mb-1">القنوات المتصلة</span>
                            <span class="text-xl font-black text-blue-400 block">4 قنوات</span>
                            <span class="text-[10px] text-slate-500 mt-0.5 block">WA • TG • FB • IG</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-[11px] text-slate-400 block mb-1">نسبة الحظر</span>
                            <span class="text-xl font-black text-emerald-400 block">0.0%</span>
                            <span class="text-[10px] text-slate-500 mt-0.5 block">Anti-Ban Queue Engine</span>
                        </div>
                    </div>

                    <!-- Mini Inbox Simulation Preview -->
                    <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                                <Send class="w-4 h-4" />
                            </div>
                            <div>
                                <span class="font-bold text-white block">حملة إعادة الاستهداف: عروض الجمعة البيضاء</span>
                                <span class="text-[11px] text-slate-400">تم إرسال 1,240 رسالة عبر Queue مع فواصل 10s عشوائية لحماية الأرقام</span>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            مكتمل بنجاح (100%)
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- 4. SUPPORTED PLATFORMS SECTION             -->
        <!-- ========================================== -->
        <section id="channels" class="py-16 bg-slate-900/50 border-y border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <span class="text-xs font-mono font-bold text-blue-400 uppercase tracking-wider block mb-2">تكامل شامل</span>
                <h2 class="text-2xl sm:text-3xl font-black text-white">4 منصات رئيسية في شاشة واحدة</h2>
                <p class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto mt-2">
                    تواصل مع عملائك أينما كانوا دون تشتت بين التطبيقات واللوحات المختلفة
                </p>

                <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- WhatsApp -->
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-emerald-500/40 transition text-start space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center">
                            <MessageSquare class="w-5 h-5" />
                        </div>
                        <h3 class="font-bold text-sm text-white">واتساب (WhatsApp)</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            ربط سريع بـ QR Code أو كود الاقتران. إرسال جماعي CSV، وحملات متزامنة، مع خوارزمية ذكية تمنع الحظر.
                        </p>
                    </div>

                    <!-- Telegram -->
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-sky-500/40 transition text-start space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-500/20 border border-sky-500/30 text-sky-400 flex items-center justify-center">
                            <Send class="w-5 h-5" />
                        </div>
                        <h3 class="font-bold text-sm text-white">تيليجرام (Telegram)</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            ربط بوت تيليجرام برمز التوكن في ثوانٍ. استقبال المحادثات، إرسال إشعارات وتحديثات الطلبات للعملاء فورياً.
                        </p>
                    </div>

                    <!-- Facebook Messenger -->
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-blue-500/40 transition text-start space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center">
                            <Globe class="w-5 h-5" />
                        </div>
                        <h3 class="font-bold text-sm text-white">فيسبوك ماسنجر</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            ربط صفحات فيسبوك والرد الفوري على رسائل العملاء، وتوزيع استفسارات الإعلانات الممولة على الوكلاء.
                        </p>
                    </div>

                    <!-- Instagram Direct -->
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-pink-500/40 transition text-start space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-pink-500/20 border border-pink-500/30 text-pink-400 flex items-center justify-center">
                            <Smartphone class="w-5 h-5" />
                        </div>
                        <h3 class="font-bold text-sm text-white">إنستجرام دايركت</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            إدارة الرسائل المباشرة والاستفسارات على المنشورات والقصص، وتحويل المتابعين إلى مبيعات مؤكدة.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- 5. BENTO GRID FEATURES                     -->
        <!-- ========================================== -->
        <section id="features" class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="text-xs font-mono font-bold text-blue-400 uppercase tracking-wider block mb-2">مميزات مصممة للمبيعات</span>
                <h2 class="text-2xl sm:text-4xl font-black text-white">كل ما يحتاجه متجرك لمضاعفة أرباحه</h2>
                <p class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto mt-2">
                    أدوات أتمتة قوية تم بناؤها لتوفير ساعات من العمل اليدوي وحماية أرقامك وقنواتك
                </p>
            </div>

            <!-- Bento Structure -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <!-- Feature 1: Omnichannel Inbox (Col span 2) -->
                <div class="md:col-span-2 p-6 sm:p-8 rounded-3xl bg-slate-900/90 border border-slate-800 space-y-4 relative overflow-hidden">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600/20 border border-blue-500/30 text-blue-400 flex items-center justify-center">
                        <Inbox class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-white">صندوق الوارد الموحد (Omnichannel Inbox)</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed max-w-xl">
                        لا داعي لفتح أربعة تطبيقات ومتابعة الرسائل بشكل مشتت. يجمع لك Vertex كافة المحادثات الواردة من واتساب، تيليجرام، فيسبوك، وإنستجرام في شاشة واحدة منظمة مع تصنيف الحالات، إسناد الوكلاء، وتحديثات Realtime فورية.
                    </p>
                    <div class="pt-2 flex flex-wrap gap-2 text-[11px] font-semibold text-slate-300">
                        <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800">✓ تصنيف المحادثات (مفتوحة، مغلقة، معلقة)</span>
                        <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800">✓ إسناد وتوزيع على الفريق</span>
                        <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800">✓ بحث سريع في سجل الرسائل</span>
                    </div>
                </div>

                <!-- Feature 2: Anti-Ban Queue Engine (Col span 1) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/90 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center">
                        <ShieldCheck class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-white">حماية حظر الواتساب (Anti-Ban)</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        خوارزمية ذكية تعالج الرسائل داخل طوابير مهام (Queue Jobs) مع فترات تأخير عشوائية (Jitter) وتوزيع زمني يحمي خطوطك من أي عقوبات أو حظر من خوادم ميتا.
                    </p>
                    <div class="text-[11px] font-mono text-emerald-400 font-bold">
                        Delay: 10s (±3s Random Jitter)
                    </div>
                </div>

                <!-- Feature 3: Smart Retargeting (Col span 1) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/90 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/20 border border-purple-500/30 text-purple-400 flex items-center justify-center">
                        <Repeat class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-white">إعادة الاستهداف الذكي</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        أعد استهداف العملاء الذين تواصلوا معك سابقاً عبر كل منصة برسائل وعروض ترويجية مخصصة تزيد نسبة التحويل بنسبة تصل إلى 40%.
                    </p>
                </div>

                <!-- Feature 4: CSV WhatsApp Bulk Sender (Col span 1) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/90 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 text-amber-400 flex items-center justify-center">
                        <Send class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-white">مرسل واتساب CSV</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        ارفع قائمة أرقام وأسماء عملائك بملف Excel/CSV بضغطة زر، خصص الرسالة بالاسم <code class="text-amber-300 font-mono">&#123;&#123;name&#125;&#125;</code>، وأطلق الحملة بأمان تام.
                    </p>
                </div>

                <!-- Feature 5: AI Auto-Reply (Col span 1) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/90 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-pink-500/20 border border-pink-500/30 text-pink-400 flex items-center justify-center">
                        <Bot class="w-6 h-6" />
                    </div>
                    <h3 class="text-lg font-bold text-white">الرد الآلي بالذكاء الاصطناعي</h3>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        ردود ذكية على مدار 24 ساعة خارج أوقات العمل تفهم لهجة العميل، تجيب عن الأسئلة الشائعة، وتحفظ الطلبات المحتملة.
                    </p>
                </div>

            </div>
        </section>

        <!-- ========================================== -->
        <!-- 6. HOW IT WORKS (3 SIMPLE STEPS)           -->
        <!-- ========================================== -->
        <section id="how-it-works" class="py-16 bg-slate-900/40 border-y border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <span class="text-xs font-mono font-bold text-blue-400 uppercase tracking-wider block mb-2">سهولة وبساطة</span>
                <h2 class="text-2xl sm:text-3xl font-black text-white">ابدأ العمل في 3 خطوات فقط</h2>

                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 text-start">
                    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-600 text-white font-mono font-bold flex items-center justify-center text-sm">
                            1
                        </div>
                        <h3 class="font-bold text-sm text-white">أنشئ حسابك واربط قنواتك</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            امسح الـ QR Code لربط واتساب وأدخل توكن تيليجرام وميتا في واجهة تحكم موحدة وسهلة.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-600 text-white font-mono font-bold flex items-center justify-center text-sm">
                            2
                        </div>
                        <h3 class="font-bold text-sm text-white">ارفع جهات اتصالك أو جهز رسالتك</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            اختر الجمهور المستهدف من عملاء المتجر أو ارفع ملف CSV يحتوي على أرقام وأسماء عملائك.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-3">
                        <div class="w-8 h-8 rounded-xl bg-blue-600 text-white font-mono font-bold flex items-center justify-center text-sm">
                            3
                        </div>
                        <h3 class="font-bold text-sm text-white">أطلق الحملة وشاهد نمو المبيعات</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            يقوم النظام بجدولة الإرسال تلقائياً، والرد على الرسائل الواردة، وتتبع معدل التفاعل والمبيعات.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- 7. PRICING PLANS                           -->
        <!-- ========================================== -->
        <section id="pricing" class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="text-xs font-mono font-bold text-blue-400 uppercase tracking-wider block mb-2">باقات شفافة</span>
            <h2 class="text-2xl sm:text-4xl font-black text-white">خطط أسعار تناسب حجم متجرك</h2>
            <p class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto mt-2">
                اختر الخطة المناسبة لك مع إمكانية الترقية أو الإلغاء في أي وقت
            </p>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 text-start">
                <!-- Starter Plan -->
                <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-6">
                    <div>
                        <h3 class="font-bold text-base text-white">الباقة الأساسية (Starter)</h3>
                        <p class="text-xs text-slate-400 mt-1">للمتاجر الناشئة وأصحاب المشاريع الفردية</p>
                    </div>
                    <div class="text-3xl font-black text-white">
                        $29 <span class="text-xs font-normal text-slate-400">/ شهرياً</span>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-300">
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>متجر واحد (1 Business)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>واتساب + تيليجرام</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>3,000 رسالة شهرياً</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>مرسل واتساب CSV</span>
                        </li>
                    </ul>
                    <Link
                        :href="route('register')"
                        class="block w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-white font-bold text-xs text-center transition"
                    >
                        ابدأ الآن
                    </Link>
                </div>

                <!-- Pro Plan (Featured) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-b from-blue-950/60 to-slate-900 border-2 border-blue-500 shadow-2xl shadow-blue-500/20 space-y-6 relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full text-[10px] font-mono font-bold bg-blue-500 text-white uppercase tracking-wider">
                        الأكثر طلباً
                    </span>
                    <div>
                        <h3 class="font-bold text-base text-white">باقة النمو (Pro)</h3>
                        <p class="text-xs text-slate-400 mt-1">للمتاجر السريعة النمو التي تريد أقصى نتائج</p>
                    </div>
                    <div class="text-3xl font-black text-white">
                        $79 <span class="text-xs font-normal text-slate-400">/ شهرياً</span>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-200">
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>حتى 5 متاجر (5 Businesses)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>كافة القنوات (WA • TG • FB • IG)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>15,000 رسالة شهرياً</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>إعادة استهداف متقدمة + AI Auto-Reply</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>نظام الحماية من الحظر (Anti-Ban Jitter)</span>
                        </li>
                    </ul>
                    <Link
                        :href="route('register')"
                        class="block w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs text-center shadow-lg shadow-blue-600/30 transition"
                    >
                        ابدأ تجربة الـ 14 يوماً
                    </Link>
                </div>

                <!-- Enterprise Plan -->
                <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-6">
                    <div>
                        <h3 class="font-bold text-base text-white">باقة الشركات (Enterprise)</h3>
                        <p class="text-xs text-slate-400 mt-1">للوكالات الإعلانية وكبار التجار والشركات</p>
                    </div>
                    <div class="text-3xl font-black text-white">
                        $199 <span class="text-xs font-normal text-slate-400">/ شهرياً</span>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-300">
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>متاجر غير محدودة</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>رسائل غير محدودة</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>سيرفرات مخصصة وخوادم Node معزولة</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Check class="w-4 h-4 text-emerald-400 shrink-0" />
                            <span>دعم فني مخصص ومستشار نمو عبر واتساب</span>
                        </li>
                    </ul>
                    <Link
                        :href="route('register')"
                        class="block w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-white font-bold text-xs text-center transition"
                    >
                        تواصل معنا
                    </Link>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- 8. FAQ SECTION                             -->
        <!-- ========================================== -->
        <section id="faq" class="py-16 bg-slate-900/30 border-t border-slate-800/80">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <span class="text-xs font-mono font-bold text-blue-400 uppercase tracking-wider block mb-2">إجابات واضحة</span>
                <h2 class="text-2xl sm:text-3xl font-black text-white mb-8">الأسئلة الشائعة</h2>

                <div class="space-y-3 text-start">
                    <div
                        v-for="(faq, i) in faqs"
                        :key="i"
                        class="p-4 sm:p-5 rounded-2xl bg-slate-900/80 border border-slate-800 cursor-pointer select-none transition"
                        @click="toggleFaq(i)"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-xs sm:text-sm text-slate-200">{{ faq.q }}</span>
                            <ChevronDown
                                class="w-4 h-4 text-slate-400 transition shrink-0"
                                :class="{ 'rotate-180 text-blue-400': faqOpen.includes(i) }"
                            />
                        </div>
                        <div v-if="faqOpen.includes(i)" class="mt-3 pt-3 border-t border-slate-800 text-xs text-slate-400 leading-relaxed">
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- 9. FINAL CTA BANNER                        -->
        <!-- ========================================== -->
        <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="p-8 sm:p-14 rounded-3xl bg-gradient-to-r from-blue-900/50 via-slate-900 to-purple-950/40 border border-blue-500/30 relative overflow-hidden shadow-2xl">
                <div class="relative z-10 max-w-2xl mx-auto space-y-4">
                    <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight">
                        جاهز لمضاعفة مبيعات متجرك وتوفير ساعات العمل؟
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        انضم إلى مئات المتاجر التي تعتمد على Vertex يومياً في إدارة قنواتها والتواصل مع عملائها.
                    </p>
                    <div class="pt-2">
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-xl shadow-blue-600/30 transition transform hover:-translate-y-0.5"
                        >
                            <span>ابدأ الآن مجاناً</span>
                            <ArrowLeft class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- 10. FOOTER                                 -->
        <!-- ========================================== -->
        <footer class="border-t border-slate-800/80 bg-slate-950 py-10 text-xs text-slate-500">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <ApplicationLogo class="h-5 w-auto fill-current text-blue-500" />
                    <span class="font-bold text-white">VERTEX Marketing Cloud</span>
                    <span>&copy; {{ new Date().getFullYear() }} جميع الحقوق محفوظة.</span>
                </div>

                <div class="flex items-center gap-6">
                    <a href="#features" class="hover:text-slate-300 transition">المميزات</a>
                    <a href="#pricing" class="hover:text-slate-300 transition">الأسعار</a>
                    <Link :href="route('login')" class="hover:text-slate-300 transition">تسجيل الدخول</Link>
                    <Link :href="route('register')" class="hover:text-slate-300 transition">إنشاء حساب</Link>
                </div>
            </div>
        </footer>

    </div>
</template>
