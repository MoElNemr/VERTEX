<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import {
    Send,
    Upload,
    FileSpreadsheet,
    Download,
    CheckCircle2,
    AlertCircle,
    Play,
    Pause,
    Trash2,
    RefreshCw,
    ShieldCheck,
    Users,
    Clock,
    X,
    ExternalLink,
    Zap,
    QrCode,
} from 'lucide-vue-next';

const props = defineProps({
    business: {
        type: Object,
        default: () => ({ name: 'المتجر' }),
    },
    whatsappAccount: {
        type: Object,
        default: () => null,
    },
    campaigns: {
        type: Object,
        default: () => ({ data: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_campaigns: 0,
            active_campaigns: 0,
            total_messages_sent: 0,
        }),
    },
});

// CSV State
const fileInput = ref(null);
const selectedFile = ref(null);
const isUploading = ref(false);
const csvData = ref(null); // { filename, total_rows, valid_count, invalid_count, preview_rows, recipients }

// Campaign Form
const campaignName = ref('');
const messageText = ref('مرحباً {name}، يسعدنا تواصلك معنا دائماً! تفضل بزيارة متجرنا للاطلاع على أحدث العروض.');
const intervalSeconds = ref(10);
const jitterSeconds = ref(2);
const saveToContacts = ref(true);
const autoStart = ref(true);

const isSubmitting = ref(false);
const activeDetailModal = ref(null);
const pollingInterval = ref(null);
const isActionLoading = ref({});

// Handle file selection
const handleFileChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    selectedFile.value = file;
    await uploadAndParseCsv(file);
};

const uploadAndParseCsv = async (file) => {
    isUploading.value = true;
    const formData = new FormData();
    formData.append('file', file);

    try {
        const res = await axios.post(route('whatsapp-sender.preview-csv'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        csvData.value = res.data;
        if (!campaignName.value) {
            campaignName.value = `إرسال واتساب - ${file.name.replace(/\.[^/.]+$/, '')}`;
        }
    } catch (err) {
        alert(err.response?.data?.error || 'حدث خطأ أثناء معالجة ملف CSV.');
        csvData.value = null;
    } finally {
        isUploading.value = false;
    }
};

const insertTag = (tag) => {
    messageText.value += ` ${tag}`;
};

// Message preview with first valid contact
const previewMessage = computed(() => {
    const sample = csvData.value?.preview_rows?.find(r => r.is_valid) || { name: 'أحمد علي', phone: '01012345678' };
    return messageText.value
        .replace(/{name}|{{name}}/g, sample.name || 'عميلنا')
        .replace(/{phone}|{{phone}}/g, sample.phone || '');
});

// Submit Bulk Campaign
const submitBulkSend = async () => {
    if (!csvData.value || !csvData.value.recipients || csvData.value.recipients.length === 0) {
        alert('يرجى رفع ملف CSV يحتوي على أرقام هواتف صالحة.');
        return;
    }

    if (!messageText.value.trim()) {
        alert('يرجى إدخال نص الرسالة.');
        return;
    }

    isSubmitting.value = true;
    try {
        const payload = {
            name: campaignName.value || `حملة واتساب جماعية - ${new Date().toLocaleDateString('ar-EG')}`,
            message: messageText.value,
            recipients: csvData.value.recipients,
            interval_seconds: intervalSeconds.value,
            jitter_seconds: jitterSeconds.value,
            save_to_contacts: saveToContacts.value,
            auto_start: autoStart.value,
            filename: csvData.value.filename,
        };

        const res = await axios.post(route('whatsapp-sender.send'), payload);
        alert('تم إنشاء حملة الواتساب بنجاح وبدء جدولتها.');

        // Reset CSV
        csvData.value = null;
        selectedFile.value = null;
        if (fileInput.value) fileInput.value.value = '';

        router.reload({ preserveScroll: true });
    } catch (err) {
        alert(err.response?.data?.message || 'حدث خطأ أثناء إنشاء الحملة.');
    } finally {
        isSubmitting.value = false;
    }
};

// Campaign Actions
const startCampaign = async (campaign) => {
    isActionLoading.value[campaign.id] = true;
    try {
        await axios.post(route('whatsapp-sender.start', campaign.id));
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('حدث خطأ أثناء بدء الحملة.');
    } finally {
        isActionLoading.value[campaign.id] = false;
    }
};

const pauseCampaign = async (campaign) => {
    isActionLoading.value[campaign.id] = true;
    try {
        await axios.post(route('whatsapp-sender.pause', campaign.id));
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('حدث خطأ أثناء إيقاف الحملة.');
    } finally {
        isActionLoading.value[campaign.id] = false;
    }
};

const stepCampaign = async (campaign) => {
    isActionLoading.value[campaign.id] = true;
    try {
        const res = await axios.post(route('whatsapp-sender.step', campaign.id));
        if (activeDetailModal.value && activeDetailModal.value.id === campaign.id) {
            activeDetailModal.value = res.data.campaign;
        }
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('حدث خطأ أثناء إرسال الخطوة.');
    } finally {
        isActionLoading.value[campaign.id] = false;
    }
};

const deleteCampaign = (campaign) => {
    if (!confirm(`هل أنت متأكد من حذف حملة "${campaign.name}"؟`)) return;
    router.delete(route('whatsapp-sender.destroy', campaign.id), { preserveScroll: true });
};

const openDetails = async (campaign) => {
    activeDetailModal.value = campaign;
    try {
        const res = await axios.get(route('whatsapp-sender.status', campaign.id));
        activeDetailModal.value = res.data.campaign;
        activeDetailModal.value.recent_recipients = res.data.recent_recipients;
        activeDetailModal.value.progress_percent = res.data.progress_percent;
    } catch (e) {}
};

// Polling
onMounted(() => {
    pollingInterval.value = setInterval(() => {
        const hasProcessing = props.campaigns.data?.some(c => c.status === 'processing');
        if (hasProcessing) {
            router.reload({ preserveScroll: true, only: ['campaigns', 'stats'] });
        }
    }, 5000);
});

onUnmounted(() => {
    if (pollingInterval.value) clearInterval(pollingInterval.value);
});
</script>

<template>
    <Head :title="`مرسل واتساب CSV - ${business.name}`" />

    <AuthenticatedLayout>
        <!-- Header -->
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-bold text-slate-100 tracking-tight font-sans">
                            مرسل الواتساب الجماعي (WhatsApp Bulk CSV Sender)
                        </h1>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-mono font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            {{ business.name }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5 font-sans">
                        رفع ملفات CSV تحتوي على أسماء وأرقام العملاء وإرسال رسائل مخصصة مع حماية كاملة من الحظر
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="route('whatsapp-sender.template')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700/80 transition cursor-pointer"
                    >
                        <Download class="w-3.5 h-3.5 text-blue-400" />
                        <span>تحميل قالب CSV تجريبي</span>
                    </a>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-5">
            <!-- 1. WHATSAPP ACCOUNT STATUS BANNER -->
            <div
                class="rounded-xl p-4 border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                :class="whatsappAccount?.status === 'connected' ? 'bg-emerald-950/20 border-emerald-500/30 text-emerald-200' : 'bg-amber-950/20 border-amber-500/30 text-amber-200'"
            >
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg" :class="whatsappAccount?.status === 'connected' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400'">
                        <Send class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="font-bold flex items-center gap-2">
                            <span>حالة رقم الواتساب:</span>
                            <span class="font-mono">{{ whatsappAccount?.status === 'connected' ? 'متصل وجاهز للإرسال' : 'غير متصل أو يتطلب مسح الرمز' }}</span>
                            <span v-if="whatsappAccount?.phone_number" class="text-[11px] font-mono text-slate-300">
                                ({{ whatsappAccount.phone_number }})
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            يتم إرسال الرسائل مباشرة من خلال اتصال Baileys السحابي مع دعم وضع المحاكاة الاحتياطي
                        </p>
                    </div>
                </div>

                <Link
                    v-if="whatsappAccount?.status !== 'connected'"
                    :href="route('channels.index', business.id)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white transition self-start sm:self-auto"
                >
                    <QrCode class="w-3.5 h-3.5" />
                    <span>ربط الواتساب عبر QR</span>
                </Link>
            </div>

            <!-- 2. MAIN BENTO GRID: UPLOADER & COMPOSER -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- LEFT BOX: CSV Upload & Contacts Preview (6 cols) -->
                <div class="lg:col-span-6 bg-slate-900/85 border border-slate-800/90 rounded-xl p-5 space-y-4">
                    <div class="border-b border-slate-800/80 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono flex items-center gap-2">
                                <FileSpreadsheet class="w-4 h-4 text-emerald-400" />
                                <span>1. رفع ملف جهات الاتصال (CSV)</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                يدعم ملفات CSV أو نصية تحتوي على أعمدة (الاسم، ورقم الهاتف)
                            </p>
                        </div>
                    </div>

                    <!-- Dropzone -->
                    <div
                        @click="$refs.fileInput.click()"
                        class="border-2 border-dashed border-slate-800 hover:border-emerald-500/60 rounded-xl p-6 text-center cursor-pointer transition bg-slate-950/60 group"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".csv,text/csv,text/plain"
                            class="hidden"
                            @change="handleFileChange"
                        />
                        <div class="flex flex-col items-center justify-center gap-2">
                            <div class="p-3 rounded-full bg-slate-900 text-slate-400 group-hover:text-emerald-400 group-hover:bg-emerald-500/10 transition">
                                <Upload class="w-6 h-6" />
                            </div>
                            <span class="text-xs font-bold text-white">انقر هنا لاختيار ملف CSV أو اسحبه وأفلته</span>
                            <span class="text-[11px] text-slate-500">حجم أقصى 10 ميجابايت • التنسيق: name, phone</span>
                        </div>
                    </div>

                    <!-- Parse Results Card -->
                    <div v-if="isUploading" class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-center text-xs text-blue-400">
                        جاري قراءة وتحليل ملف CSV والتحقق من الأرقام...
                    </div>

                    <div v-else-if="csvData" class="space-y-3">
                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="p-2 rounded bg-slate-950 border border-slate-800">
                                <span class="text-[10px] text-slate-500 block">إجمالي السطور</span>
                                <span class="font-mono font-bold text-white">{{ csvData.total_rows }}</span>
                            </div>
                            <div class="p-2 rounded bg-slate-950 border border-emerald-500/20">
                                <span class="text-[10px] text-emerald-400 block">أرقام صالحة للإرسال</span>
                                <span class="font-mono font-bold text-emerald-400">{{ csvData.valid_count }}</span>
                            </div>
                            <div class="p-2 rounded bg-slate-950 border border-slate-800">
                                <span class="text-[10px] text-slate-500 block">أرقام غير صالحة</span>
                                <span class="font-mono font-bold text-slate-400">{{ csvData.invalid_count }}</span>
                            </div>
                        </div>

                        <!-- Preview Table -->
                        <div class="border border-slate-800 rounded-lg overflow-hidden max-h-48 overflow-y-auto">
                            <table class="w-full text-start text-[11px]">
                                <thead class="bg-slate-950 text-slate-500 border-b border-slate-800 uppercase font-mono">
                                    <tr>
                                        <th class="py-1.5 px-2.5 text-start">الاسم</th>
                                        <th class="py-1.5 px-2.5 text-start">الرقم</th>
                                        <th class="py-1.5 px-2.5 text-start">رابط wa.me</th>
                                        <th class="py-1.5 px-2.5 text-end">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/80 text-slate-300">
                                    <tr v-for="(row, idx) in csvData.preview_rows" :key="idx" class="hover:bg-slate-950/50">
                                        <td class="py-1.5 px-2.5">{{ row.name || 'بدون اسم' }}</td>
                                        <td class="py-1.5 px-2.5 font-mono text-slate-200">{{ row.phone }}</td>
                                        <td class="py-1.5 px-2.5 font-mono">
                                            <a
                                                v-if="row.is_valid"
                                                :href="'https://wa.me/' + row.phone"
                                                target="_blank"
                                                class="text-emerald-400 hover:text-emerald-300 hover:underline flex items-center gap-1 text-[10px]"
                                            >
                                                <span>wa.me/{{ row.phone }}</span>
                                                <ExternalLink class="w-3 h-3" />
                                            </a>
                                            <span v-else class="text-slate-600">-</span>
                                        </td>
                                        <td class="py-1.5 px-2.5 text-end">
                                            <span
                                                class="px-1.5 py-0.2 rounded text-[10px] font-mono"
                                                :class="row.is_valid ? 'text-emerald-400 bg-emerald-500/10' : 'text-rose-400 bg-rose-500/10'"
                                            >
                                                {{ row.is_valid ? 'صالح' : 'غير صالح' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RIGHT BOX: Message Composer & Anti-Ban Throttling (6 cols) -->
                <div class="lg:col-span-6 bg-slate-900/85 border border-slate-800/90 rounded-xl p-5 space-y-4">
                    <div class="border-b border-slate-800/80 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono flex items-center gap-2">
                                <Send class="w-4 h-4 text-blue-400" />
                                <span>2. صياغة الرسالة وجدولة الأمان</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                تخصيص نص الرسالة بالمتغيرات وضبط فترات التأخير لمنع الحظر
                            </p>
                        </div>
                    </div>

                    <!-- Campaign Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">اسم الحملة</label>
                        <input
                            v-model="campaignName"
                            type="text"
                            placeholder="مثال: رسائل خصم الجمعة البيضاء"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-sans"
                        />
                    </div>

                    <!-- Message Body -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-slate-300">نص رسالة الواتساب</label>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="insertTag('{name}')"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-800 hover:bg-slate-750 text-emerald-400 border border-slate-700 cursor-pointer"
                                >
                                    + {name} اسم العميل
                                </button>
                                <button
                                    type="button"
                                    @click="insertTag('{phone}')"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-800 hover:bg-slate-750 text-emerald-400 border border-slate-700 cursor-pointer"
                                >
                                    + {phone} الرقم
                                </button>
                            </div>
                        </div>
                        <textarea
                            v-model="messageText"
                            rows="4"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white focus:outline-none focus:border-emerald-500 font-sans"
                            placeholder="اكتب رسالة الواتساب هنا..."
                        ></textarea>
                    </div>

                    <!-- Live Message Preview Box -->
                    <div class="bg-slate-950/80 rounded-lg p-3 border border-slate-800/80">
                        <span class="text-[10px] font-mono text-slate-500 block mb-1">معاينة الرسالة للعميل الأول:</span>
                        <p class="text-xs text-emerald-300 whitespace-pre-line font-sans">{{ previewMessage }}</p>
                    </div>

                    <!-- Anti-Ban Throttling Interval -->
                    <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
                            <ShieldCheck class="w-4 h-4" />
                            <span>فواصل الأمان (Anti-Ban Throttling)</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">الفاصل الزمني (ثواني)</label>
                                <input
                                    v-model.number="intervalSeconds"
                                    type="number"
                                    min="2"
                                    max="300"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white font-mono focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">تذبذب عشوائي (Jitter ثواني)</label>
                                <input
                                    v-model.number="jitterSeconds"
                                    type="number"
                                    min="0"
                                    max="30"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white font-mono focus:outline-none focus:border-emerald-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Options & Submit -->
                    <div class="space-y-2 pt-1">
                        <div class="flex items-center gap-2">
                            <input
                                v-model="saveToContacts"
                                type="checkbox"
                                id="save_contacts"
                                class="rounded border-slate-800 bg-slate-950 text-emerald-600 focus:ring-0"
                            />
                            <label for="save_contacts" class="text-xs text-slate-300 cursor-pointer select-none">
                                حفظ الأرقام والأسماء تلقائياً في سجل جهات اتصال المتجر
                            </label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                v-model="autoStart"
                                type="checkbox"
                                id="auto_start_wa"
                                class="rounded border-slate-800 bg-slate-950 text-emerald-600 focus:ring-0"
                            />
                            <label for="auto_start_wa" class="text-xs text-slate-300 cursor-pointer select-none">
                                بدء الإرسال التدريجي فوراً بعد التأكيد
                            </label>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="submitBulkSend"
                        :disabled="!csvData || isSubmitting"
                        class="w-full py-2.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition cursor-pointer shadow-sm disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <Send class="w-4 h-4" />
                        <span>{{ isSubmitting ? 'جاري تهيئة الإرسال...' : 'بدء الإرسال الجماعي عبر الواتساب' }}</span>
                    </button>
                </div>
            </div>

            <!-- 3. CAMPAIGNS LOGS TABLE -->
            <div class="bg-slate-900/85 border border-slate-800/90 rounded-xl p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-3.5">
                    <div>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono flex items-center gap-2">
                            <Clock class="w-4 h-4 text-emerald-400" />
                            <span>سجل حملات الواتساب المنفذة من ملفات CSV</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            متابعة تقدم التسليم والرسائل المرسلة والفاشلة لكل ملف تم رفعه
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="router.reload({ preserveScroll: true })"
                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700/80 transition cursor-pointer self-start sm:self-auto"
                        title="تحديث البيانات"
                    >
                        <RefreshCw class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="text-slate-500 border-b border-slate-800/80 uppercase font-semibold text-[11px]">
                            <tr>
                                <th class="py-2.5 px-3 text-start">الحملة والملف</th>
                                <th class="py-2.5 px-3 text-start">فاصل الأمان</th>
                                <th class="py-2.5 px-3 text-start">الحالة</th>
                                <th class="py-2.5 px-3 text-start">نسبة الإنجاز</th>
                                <th class="py-2.5 px-3 text-end">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-if="campaigns.data.length === 0">
                                <td colspan="5" class="py-10 text-center text-slate-500">
                                    <Send class="w-8 h-8 mx-auto text-slate-600 mb-2" />
                                    <span>لم تقم بإرسال أي حملة واتساب جماعية بعد.</span>
                                </td>
                            </tr>
                            <tr
                                v-for="c in campaigns.data"
                                :key="c.id"
                                class="hover:bg-slate-800/30 transition"
                            >
                                <td class="py-3 px-3">
                                    <div class="font-bold text-white text-xs">{{ c.name }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                                        {{ c.metadata?.csv_filename || 'CSV' }} • {{ new Date(c.created_at).toLocaleDateString('ar-EG') }}
                                    </div>
                                </td>

                                <td class="py-3 px-3 font-mono text-[11px] text-slate-300">
                                    {{ c.interval_seconds }}ث (±{{ c.jitter_seconds }}ث)
                                </td>

                                <td class="py-3 px-3">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono font-semibold border"
                                        :class="c.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : c.status === 'processing' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20 animate-pulse' : c.status === 'paused' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-slate-800 text-slate-400 border-slate-700'"
                                    >
                                        {{ c.status === 'completed' ? 'مكتملة' : c.status === 'processing' ? 'جارية الإرسال' : c.status === 'paused' ? 'متوقفة مؤقتاً' : 'مسودة' }}
                                    </span>
                                </td>

                                <td class="py-3 px-3 min-w-[140px]">
                                    <div class="flex items-center justify-between text-[10px] font-mono text-slate-400 mb-1">
                                        <span class="text-emerald-400">{{ c.sent_count }} تم</span>
                                        <span v-if="c.failed_count > 0" class="text-rose-400">{{ c.failed_count }} فشل</span>
                                        <span>/ {{ c.total_recipients }}</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-slate-950 rounded-full overflow-hidden border border-slate-800">
                                        <div
                                            class="h-full bg-emerald-500 transition-all duration-300"
                                            :style="{ width: `${c.total_recipients > 0 ? Math.round(((c.sent_count + c.failed_count) / c.total_recipients) * 100) : 0}%` }"
                                        ></div>
                                    </div>
                                </td>

                                <td class="py-3 px-3 text-end">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            v-if="c.status === 'draft' || c.status === 'paused'"
                                            type="button"
                                            @click="startCampaign(c)"
                                            :disabled="isActionLoading[c.id]"
                                            class="p-1.5 rounded-lg bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600/30 border border-emerald-500/30 transition cursor-pointer"
                                            title="بدء / استئناف"
                                        >
                                            <Play class="w-3.5 h-3.5" />
                                        </button>

                                        <button
                                            v-if="c.status === 'processing'"
                                            type="button"
                                            @click="pauseCampaign(c)"
                                            :disabled="isActionLoading[c.id]"
                                            class="p-1.5 rounded-lg bg-amber-600/20 text-amber-400 hover:bg-amber-600/30 border border-amber-500/30 transition cursor-pointer"
                                            title="إيقاف مؤقت"
                                        >
                                            <Pause class="w-3.5 h-3.5" />
                                        </button>

                                        <button
                                            v-if="c.status !== 'completed'"
                                            type="button"
                                            @click="stepCampaign(c)"
                                            :disabled="isActionLoading[c.id]"
                                            class="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-750 border border-slate-700 transition cursor-pointer"
                                            title="إرسال رسالة تالية فورية"
                                        >
                                            <Zap class="w-3.5 h-3.5 text-emerald-400" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDetails(c)"
                                            class="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-750 border border-slate-700 transition cursor-pointer"
                                            title="سجل المستلمين"
                                        >
                                            <ExternalLink class="w-3.5 h-3.5" />
                                        </button>

                                        <button
                                            type="button"
                                            @click="deleteCampaign(c)"
                                            class="p-1.5 rounded-lg bg-slate-800 text-rose-400 hover:bg-rose-950/40 border border-slate-700 transition cursor-pointer"
                                            title="حذف"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. MODAL: LOGS & STATUS DETAILS -->
        <div
            v-if="activeDetailModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl max-h-[85vh] overflow-y-auto shadow-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-white">{{ activeDetailModal.name }}</h3>
                        <p class="text-[11px] text-slate-400 font-mono">حملة واتساب #{{ activeDetailModal.id }}</p>
                    </div>
                    <button
                        type="button"
                        @click="activeDetailModal = null"
                        class="p-1 text-slate-400 hover:text-white cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="p-2 rounded bg-slate-950 border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">إجمالي الأرقام</span>
                        <span class="font-mono font-bold text-white">{{ activeDetailModal.total_recipients }}</span>
                    </div>
                    <div class="p-2 rounded bg-slate-950 border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">مرسلة بنجاح</span>
                        <span class="font-mono font-bold text-emerald-400">{{ activeDetailModal.sent_count }}</span>
                    </div>
                    <div class="p-2 rounded bg-slate-950 border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">فشل الإرسال</span>
                        <span class="font-mono font-bold text-rose-400">{{ activeDetailModal.failed_count }}</span>
                    </div>
                </div>

                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800 text-xs">
                    <span class="text-[10px] text-slate-500 block mb-1">نص الرسالة:</span>
                    <p class="text-slate-200 whitespace-pre-line">{{ activeDetailModal.message }}</p>
                </div>

                <div v-if="activeDetailModal.recent_recipients && activeDetailModal.recent_recipients.length > 0">
                    <h4 class="text-xs font-bold text-slate-300 mb-2">سجل آخر الأرقام المستهدفة:</h4>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto">
                        <div
                            v-for="r in activeDetailModal.recent_recipients"
                            :key="r.id"
                            class="p-2 rounded bg-slate-950 border border-slate-800/80 flex items-center justify-between text-xs"
                        >
                            <div class="min-w-0 flex-1">
                                <span class="font-bold text-white text-[11px]">{{ r.recipient_name || 'عميل' }}</span>
                                <span class="font-mono text-slate-400 text-[10px] ms-2">{{ r.recipient_id }}</span>
                            </div>
                            <span
                                class="px-1.5 py-0.2 rounded text-[10px] font-mono font-semibold"
                                :class="r.status === 'sent' ? 'bg-emerald-500/10 text-emerald-400' : r.status === 'failed' ? 'bg-rose-500/10 text-rose-400' : 'bg-slate-800 text-slate-400'"
                            >
                                {{ r.status === 'sent' ? 'تم الإرسال' : r.status === 'failed' ? 'فشل' : 'معلق' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
