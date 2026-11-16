<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    BookOpen,
    Radio,
    Sparkles,
    MessageSquare,
    CreditCard,
    Users,
    HelpCircle,
    ChevronDown,
    ChevronLeft,
    CheckCircle2,
    Zap,
    ExternalLink,
    Smartphone,
    Globe,
    Layers,
    ShieldCheck,
    MessageCircle,
    Volume2,
    ImageIcon,
} from 'lucide-vue-next';

const props = defineProps({
    business: Object,
});

const activeSection = ref('quickstart');

const sections = [
    { id: 'quickstart', title: 'البداية السريعة', icon: Zap },
    { id: 'channels', title: 'ربط القنوات (5 منصات)', icon: Radio },
    { id: 'ai', title: 'الذكاء الاصطناعي والأتمتة', icon: Sparkles },
    { id: 'inbox', title: 'صندوق الوارد والوسائط', icon: MessageSquare },
    { id: 'billing', title: 'الباقات والدفع عبر فوري', icon: CreditCard },
    { id: 'faq', title: 'الأسئلة الشائعة وحل المشاكل', icon: HelpCircle },
];
</script>

<template>
    <Head title="دليل الاستخدام والتشغيل - Vertex" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 shrink-0">
                        <BookOpen class="w-5 h-5" />
                    </div>
                    <div>
                        <h1 class="text-base sm:text-lg font-bold text-white tracking-tight">
                            دليل الاستخدام والتشغيل الشامل
                        </h1>
                        <p class="text-xs text-slate-400">
                            تعلم كيفية إدارة متاجرك، ربط القنوات، تشغيل الردود الذكية، وإتقان صندوق الوارد الموحد
                        </p>
                    </div>
                </div>

                <div v-if="business" class="flex items-center gap-2">
                    <Link
                        :href="route('channels.index', business.id)"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-500 text-white transition shadow-sm"
                    >
                        الذهاب لربط القنوات &larr;
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Mobile & Desktop Navigation Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-b border-slate-800">
                <button
                    v-for="sec in sections"
                    :key="sec.id"
                    type="button"
                    @click="activeSection = sec.id"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition shrink-0 flex items-center gap-2 border select-none cursor-pointer"
                    :class="activeSection === sec.id
                        ? 'bg-blue-600 text-white border-blue-500 shadow-lg shadow-blue-600/30'
                        : 'bg-slate-900/90 text-slate-400 hover:text-white border-slate-800 hover:border-slate-700'"
                >
                    <component :is="sec.icon" class="w-4 h-4 shrink-0" />
                    <span>{{ sec.title }}</span>
                </button>
            </div>

            <!-- SECTION 1: QUICKSTART -->
            <div v-if="activeSection === 'quickstart'" class="space-y-6 text-start">
                <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                    <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                        <Zap class="w-5 h-5 text-amber-400" />
                        <span>ما هي منصة VERTEX؟ وكيف تبدأ في 5 دقائق؟</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        منصة <strong class="text-white">VERTEX</strong> هي نظام تشغيل موحد للتجارة عبر المحادثات (Conversational Commerce). 
                        تجمع لك كافة رسائل عملائك من 5 منصات (واتساب، فيسبوك، إنستجرام، تيك توك، تيليجرام) في شاشة واحدة متجاوبة على هاتفك، مع مساعد ذكي يعمل على مدار الساعة.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                            <span class="w-7 h-7 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center font-bold text-xs">1</span>
                            <h3 class="text-xs font-bold text-white">أنشئ متجرك أو تنقل بين متاجرك</h3>
                            <p class="text-[11px] text-slate-400">
                                يمكنك إدارة عدة متاجر لحسابك والتبديل بينها بنقرة واحدة من القائمة العلوية للهاتف.
                            </p>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                            <span class="w-7 h-7 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-xs">2</span>
                            <h3 class="text-xs font-bold text-white">اربط قنوات التواصل</h3>
                            <p class="text-[11px] text-slate-400">
                                امسح كود واتساب واربط فيسبوك وإنستجرام بضغطة زر واحدة.
                            </p>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                            <span class="w-7 h-7 rounded-lg bg-purple-600/20 text-purple-400 flex items-center justify-center font-bold text-xs">3</span>
                            <h3 class="text-xs font-bold text-white">ابدأ الرد التلقائي والمبيعات</h3>
                            <p class="text-[11px] text-slate-400">
                                استقبل جميع الرسائل، الصور، والتسجيلات الصوتية في صندوق الوارد الموحد فورياً.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: CHANNELS INTEGRATION -->
            <div v-if="activeSection === 'channels'" class="space-y-4 text-start">
                <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-6">
                    <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                        <Radio class="w-5 h-5 text-blue-400" />
                        <span>دليل ربط القنوات خطوة بخطوة</span>
                    </h2>

                    <!-- WhatsApp -->
                    <div class="p-4 rounded-xl bg-slate-950/70 border border-emerald-900/40 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
                            <MessageCircle class="w-4 h-4" />
                            <span>1. واتساب (WhatsApp Web / Baileys):</span>
                        </div>
                        <ul class="list-disc list-inside text-xs text-slate-300 space-y-1 leading-relaxed">
                            <li>توجه لصفحة <strong>القنوات</strong> ثم اختر تبويب واتساب.</li>
                            <li><strong>طريقة الـ QR:</strong> اضغط "توليد رمز QR"، ثم افتح واتساب على هاتفك &larr; الأجهزة المرتبطة &larr; امسح الرمز بكاميرا الهاتف.</li>
                            <li><strong>طريقة كود الاقتران (Pairing Code):</strong> اكتب رقم هاتفك مع كود الدولة (مثال: <code class="font-mono text-emerald-400">201012345678</code>)، وسيظهر لك كود من 8 أرقام تدخله في إشعار الواتساب على هاتفك.</li>
                        </ul>
                    </div>

                    <!-- Meta Facebook & Instagram -->
                    <div class="p-4 rounded-xl bg-slate-950/70 border border-blue-900/40 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-blue-400">
                            <Globe class="w-4 h-4" />
                            <span>2. فيسبوك ماسنجر وإنستجرام (Meta OAuth 2.0):</span>
                        </div>
                        <ul class="list-disc list-inside text-xs text-slate-300 space-y-1 leading-relaxed">
                            <li>اضغط زر <strong>"تسجيل الدخول وربط الحساب عبر فيسبوك"</strong>.</li>
                            <li>حدد الصفحات وحسابات إنستجرام للأعمال (Instagram Professional/Creator) التي ترغب بربطها.</li>
                            <li>وافق على الصلاحيات، وسيتم ربط استقبال الرسائل والتعليقات فورياً دون الحاجة لكتابة أكواد أو Webhooks يدوية.</li>
                        </ul>
                    </div>

                    <!-- TikTok & Telegram -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-slate-950/70 border border-cyan-900/40 space-y-2">
                            <div class="text-xs font-bold text-cyan-400">3. تيك توك للأعمال (TikTok):</div>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                اضغط "ربط تيك توك بنقرة واحدة" للموافقة على مزامنة الرسائل المباشرة والتعليقات على فيديوهاتك.
                            </p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-950/70 border border-sky-900/40 space-y-2">
                            <div class="text-xs font-bold text-sky-400">4. تليجرام (Telegram Bot):</div>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                أنشئ بوتاً عبر BotFather على تليجرام، وانسخ الـ Token وضعه في خانة تليجرام واضغط "حفظ وتفعيل".
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: AI & AUTO-REPLY -->
            <div v-if="activeSection === 'ai'" class="space-y-4 text-start">
                <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-5">
                    <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                        <Sparkles class="w-5 h-5 text-purple-400" />
                        <span>الذكاء الاصطناعي المركزي (Groq AI) والأتمتة</span>
                    </h2>

                    <div class="p-4 rounded-xl bg-purple-950/30 border border-purple-800/40 text-xs text-purple-200 leading-relaxed">
                        🌟 <strong>مفتاح الذكاء الاصطناعي مزود مركزياً من المنصة:</strong> لا تحتاج لشراء أي مفاتيح خارجية. كل ما عليك هو تفعيل المساعد وتحديد شخصية متجرك.
                    </div>

                    <div class="space-y-3">
                        <h3 class="text-xs font-bold text-white">كيف تضبط مساعد الذكاء الاصطناعي لمتجرك؟</h3>
                        <ol class="list-decimal list-inside text-xs text-slate-300 space-y-1.5 leading-relaxed">
                            <li>انتقل لتبويب <strong>"المساعد الذكي (AI)"</strong> في صفحة القنوات.</li>
                            <li>فعل خيار <strong>"تفعيل المساعد الذكي"</strong>.</li>
                            <li><strong>نبرة المتجر:</strong> اكتب أسلوب التحدث المفضل لعملائك (مثال: "ودود، مصري لطيف، يستخدم إيموجيز").</li>
                            <li><strong>تعليمات وسياسات المتجر:</strong> اكتب مواعيد العمل، مدة الشحن، شروط الاسترجاع والاستبدال، والأسعار.</li>
                            <li>حدد القنوات التي ترغب أن يرد عليها الـ AI تلقائياً (مثلاً واتساب وإنستجرام فقط).</li>
                        </ol>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2">
                        <h3 class="text-xs font-bold text-white">الرد على التعليقات (Comment-to-DM):</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            يمكنك اختيار الرد على تعليقات فيسبوك وإنستجرام وتيك توك بـ:
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-xs">
                            <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                                <span class="font-bold text-blue-400 block mb-1">📝 الرد بنص ثابت:</span>
                                <span>قالب نصي محدد مع إرسال تفاصيل العرض في الخاص فوراً.</span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-900 border border-purple-800/40">
                                <span class="font-bold text-purple-400 block mb-1">🤖 الرد بذكاء اصطناعي (AI):</span>
                                <span>يقرأ الـ AI تعليق العميل ويرد عليه برد فريد وجذاب يدعوه للخاص.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: UNIFIED INBOX & MEDIA -->
            <div v-if="activeSection === 'inbox'" class="space-y-4 text-start">
                <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-5">
                    <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                        <MessageSquare class="w-5 h-5 text-blue-400" />
                        <span>إتقان صندوق الوارد الموحد ودعم الوسائط (Images & Voice Notes)</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                            <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
                                <Volume2 class="w-4 h-4" />
                                <span>الرسائل والتسجيلات الصوتية (Voice Notes)</span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                عندما يرسل العميل تسجيلاً صوتياً على واتساب أو إنستجرام، يظهر مشغل صوتي سريع ومباشر داخل الشات للتشغيل الفوري بنقرة واحدة.
                            </p>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                            <div class="flex items-center gap-2 text-xs font-bold text-blue-400">
                                <ImageIcon class="w-4 h-4" />
                                <span>الصور وعارض الصور المدمج (Image Lightbox)</span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                الصور المعروضة تفتح بملء الشاشة باللمس، وتعتمد المنصة على روابط البث المباشر (Direct CDN) لحماية مساحة هاتفك وسيرفرك من الامتلاء.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2">
                        <h3 class="text-xs font-bold text-white flex items-center gap-1.5">
                            <Sparkles class="w-4 h-4 text-purple-400" />
                            <span>تلخيص المحادثة بالذكاء الاصطناعي (AI Summary)</span>
                        </h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            إذا دخل موظف جديد على محادثة طويلة مع عميل، يضغط على أيقونة النجوم 
                            <strong class="text-purple-300">"تلخيص بالذكاء الاصطناعي"</strong> 
                            ليحصل في ثانيتين على ملخص شامل لطلب العميل وموقفه دون إضاعة الوقت في قراءة مئات الرسائل.
                        </p>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: BILLING & FAWRY -->
            <div v-if="activeSection === 'billing'" class="space-y-4 text-start">
                <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-5">
                    <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                        <CreditCard class="w-5 h-5 text-amber-400" />
                        <span>الباقات، الحصص، وطرق الدفع عبر فوري (Fawry)</span>
                    </h2>

                    <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3">
                        <h3 class="text-xs font-bold text-white">الفرق بين حصة الرسائل وحصة الـ AI:</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 space-y-1">
                                <div class="font-bold text-emerald-400">1. حصة الرسائل الاعتيادية:</div>
                                <p class="text-slate-300">
                                    تُخصم فقط عند إرسال رسالة من موظف دعم بشري أو رد بقاعدة نصية ثابتة.
                                </p>
                            </div>
                            <div class="p-3 rounded-lg bg-slate-900 border border-purple-800/40 space-y-1">
                                <div class="font-bold text-purple-400">2. حصة الذكاء الاصطناعي (AI):</div>
                                <p class="text-slate-300">
                                    تُخصم حصرياً عند قيام محرك Groq AI بتوليد رد ذكي تلقائي على رسالة أو تعليق.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs text-slate-300">
                        <h3 class="font-bold text-white">طرق السداد المعتمدة عبر فوري:</h3>
                        <ul class="list-disc list-inside space-y-1 leading-relaxed">
                            <li><strong>كود فوري (Fawry Ref):</strong> احصل على كود دفع صالح لمدة 48 ساعة وسدد في أي كشك أو سوبرماركت به ماكينة فوري.</li>
                            <li><strong>المحافظ الإلكترونية:</strong> (فودافون كاش، أورنج كاش، اتصالات كاش، وي باي، إنستاباي).</li>
                            <li><strong>البطاقات البنكية (Visa / MasterCard):</strong> سداد فوري بالجنيه المصري دون أي عمولات بالدولار.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- SECTION 6: FAQS -->
            <div v-if="activeSection === 'faq'" class="space-y-4 text-start">
                <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                    <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                        <HelpCircle class="w-5 h-5 text-sky-400" />
                        <span>الأسئلة الشائعة واستكشاف الأخطاء</span>
                    </h2>

                    <div class="space-y-3 text-xs">
                        <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                            <div class="font-bold text-white">س: هل يمكنني تشغيل المنصة واستقبال الرسائل بالكامل من هاتفي؟</div>
                            <p class="text-slate-400 leading-relaxed">
                                ج: نعم 100%! تم تصميم واجهة VERTEX بالكامل بمبدأ "الموبايل أولاً"، وتعمل على أي هاتف بسلاسة وسرعة فائقة دون الحاجة لجهاز كمبيوتر.
                            </p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                            <div class="font-bold text-white">س: ماذا يحدث إذا نفدت حصة رسائل الذكاء الاصطناعي؟</div>
                            <p class="text-slate-400 leading-relaxed">
                                ج: لا يتوقف الشات مع عملائك نهائياً! يتم تحويل المحادثات فورياً لموظفي الدعم البشريين مع إشعارك بإمكانية ترقية الباقة لزيادة رصيد الـ AI.
                            </p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                            <div class="font-bold text-white">س: ما سبب فصل جلسة واتساب أحياناً؟</div>
                            <p class="text-slate-400 leading-relaxed">
                                ج: مثل WhatsApp Web تماماً، إذا تم إيقاف اتصال هاتفك بالإنترنت لفترة طويلة أو قمت بتسجيل الخروج من هاتفك، يكفيك فقط إعادة مسح الرمز QR لاستعادة الاتصال بلحظة.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
