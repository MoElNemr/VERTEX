<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Sparkles,
    Bot,
    Clock,
    Save,
    CheckCircle2,
    Sliders,
    ArrowRight,
    MessageSquare,
    Zap,
} from 'lucide-vue-next';

const props = defineProps({
    business: Object,
    setting: Object,
});

const form = useForm({
    ai_enabled: Boolean(props.setting?.ai_enabled),
    ai_model: props.setting?.ai_model || 'gpt-4o-mini',
    ai_personality: props.setting?.ai_personality || '',
    ai_instructions: props.setting?.ai_instructions || '',
    working_hours_only: Boolean(props.setting?.working_hours_only),
    work_start_hour: props.setting?.work_start_hour ?? 9,
    work_end_hour: props.setting?.work_end_hour ?? 18,
});

const submit = () => {
    form.post(route('ai-settings.update', props.business.id));
};
</script>

<template>
    <Head :title="`إعدادات المساعد الذكي - ${business.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('inbox.index')"
                        class="p-2 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl border border-slate-800 transition"
                        title="العودة لصندوق الوارد"
                    >
                        <ArrowRight class="w-5 h-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-white tracking-wide">
                                إعدادات المساعد الذكي
                            </h2>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-violet-500/10 text-violet-400 border border-violet-500/20">
                                <Sparkles class="w-3.5 h-3.5" />
                                الذكاء الاصطناعي
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            تخصيص سلوك الذكاء الاصطناعي، الرد التلقائي، واقتراحات المحادثات لمتجر {{ business.name }}
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Status Banner Card -->
                <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl relative overflow-hidden backdrop-blur-sm">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-violet-600/20 border border-violet-500/30 rounded-2xl text-violet-400 shrink-0">
                                <Bot class="w-8 h-8" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">تفعيل المساعد الذكي للردود</h3>
                                <p class="text-xs text-slate-400 mt-1 max-w-xl leading-relaxed">
                                    عند تفعيل المساعد الذكي، يمكن للوكلاء طلب اقتراحات ردود وتلخيص المحادثات، بالإضافة لإمكانية الرد التلقائي الفوري على استفسارات العملاء.
                                </p>
                            </div>
                        </div>

                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.ai_enabled"
                                class="sr-only peer"
                            />
                            <div class="w-14 h-7 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-violet-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Model & Behavior Configuration -->
                <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-6 backdrop-blur-sm">
                    <div class="border-b border-slate-800 pb-4">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <Sliders class="w-4 h-4 text-violet-400" />
                            تخصيص النموذج والشخصية
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">حدد نموذج الذكاء الاصطناعي وأسلوب التحدث مع عملائك</p>
                    </div>

                    <!-- AI Model -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-2">
                            نموذج الذكاء الاصطناعي (AI Model)
                        </label>
                        <select
                            v-model="form.ai_model"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition"
                        >
                            <option value="gpt-4o-mini">GPT-4o Mini (سريع، اقتصادي، ومناسب للدعم اليومي)</option>
                            <option value="gpt-4o">GPT-4o (الأعلى دقة وفهمًا للسياقات المعقدة)</option>
                            <option value="gpt-4-turbo">GPT-4 Turbo (قوي للمهام الطويلة)</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.ai_model" />
                    </div>

                    <!-- AI Personality -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-2">
                            شخصية ونبرة المساعد (Personality & Tone)
                        </label>
                        <textarea
                            v-model="form.ai_personality"
                            rows="3"
                            placeholder="مثال: أنت ممثل خدمة عملاء محترف في متجر فيرتكس، تتحدث بلهجة ودودة ومهذبة، وترحب بالعميل دائمًا باسمه..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition leading-relaxed"
                        ></textarea>
                        <p class="text-[11px] text-slate-500 mt-1">تحدد نبرة الخطاب وأسلوب التفاعل العام للمساعد الذكي.</p>
                        <InputError class="mt-1" :message="form.errors.ai_personality" />
                    </div>

                    <!-- AI Instructions -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-2">
                            تعليمات وقواعد العمل (Business Instructions)
                        </label>
                        <textarea
                            v-model="form.ai_instructions"
                            rows="4"
                            placeholder="مثال:
- الشحن متاح لجميع محافظات مصر خلال 48 ساعة بتكلفة 40 جنيه.
- سياسة الاستبدال والاسترجاع خلال 14 يوماً مع الفاتورة.
- إذا طلب العميل خصمًا خاصًا، اقترح كود WELCOME10."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition leading-relaxed font-mono text-xs"
                        ></textarea>
                        <p class="text-[11px] text-slate-500 mt-1">القواعد والبيانات الأساسية التي يعتمد عليها المساعد للرد على الاستفسارات.</p>
                        <InputError class="mt-1" :message="form.errors.ai_instructions" />
                    </div>
                </div>

                <!-- Working Hours Rules -->
                <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-6 backdrop-blur-sm">
                    <div class="border-b border-slate-800 pb-4">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <Clock class="w-4 h-4 text-amber-400" />
                            جدول ساعات العمل والرد التلقائي
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">تحديد متى يُسمح للمساعد الذكي بإرسال ردود تلقائية للعملاء</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-semibold text-slate-200 block">تقييد الردود بساعات العمل فقط</span>
                            <span class="text-xs text-slate-500">إذا تم التعطيل، سيرد المساعد التلقائي على مدار 24 ساعة</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.working_hours_only"
                                class="sr-only peer"
                            />
                            <div class="w-12 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[3px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                        </label>
                    </div>

                    <!-- Hours range -->
                    <div v-if="form.working_hours_only" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">بداية ساعات العمل (بالساعات 0-23)</label>
                            <input
                                type="number"
                                min="0"
                                max="23"
                                v-model.number="form.work_start_hour"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-200 focus:outline-none focus:border-amber-500"
                            />
                            <InputError class="mt-1" :message="form.errors.work_start_hour" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">نهاية ساعات العمل (بالساعات 0-23)</label>
                            <input
                                type="number"
                                min="0"
                                max="23"
                                v-model.number="form.work_end_hour"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-200 focus:outline-none focus:border-amber-500"
                            />
                            <InputError class="mt-1" :message="form.errors.work_end_hour" />
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-bold rounded-xl text-sm transition shadow-lg shadow-violet-600/30 disabled:opacity-50 cursor-pointer"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ form.processing ? 'جاري الحفظ...' : 'حفظ إعدادات المساعد الذكي' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
