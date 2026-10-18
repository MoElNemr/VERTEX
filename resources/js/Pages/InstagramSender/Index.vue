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
    ShieldAlert,
    Users,
    Clock,
    X,
    ExternalLink,
    Zap,
    Wallet,
    Coins,
} from 'lucide-vue-next';

const props = defineProps({
    business: {
        type: Object,
        default: () => ({ name: 'المتجر' }),
    },
    instagramAccount: {
        type: Object,
        default: () => null,
    },
    campaigns: {
        type: Object,
        default: () => ({ data: [] }),
    },
    existingContactsCount: {
        type: Number,
        default: 0,
    },
    active24hCount: {
        type: Number,
        default: 0,
    },
    optedInCount: {
        type: Number,
        default: 0,
    },
    totalContactsCount: {
        type: Number,
        default: 0,
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

const fileInput = ref(null);
const selectedFile = ref(null);
const isUploading = ref(false);
const csvData = ref(null);
const audienceMode = ref('existing_contacts');

const campaignName = ref('');
const messageText = ref('مرحباً {name}! تفضل أحدث العروض الحصرية لمتجرنا على إنستجرام.');
const intervalSeconds = ref(6);
const jitterSeconds = ref(2);
const saveToContacts = ref(true);
const autoStart = ref(true);

const isSubmitting = ref(false);
const pollingInterval = ref(null);
const isActionLoading = ref({});

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
        const res = await axios.post(route('instagram-sender.preview-csv'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        csvData.value = res.data;
        if (!campaignName.value) {
            campaignName.value = `إرسال إنستجرام - ${file.name.replace(/\.[^/.]+$/, '')}`;
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

const previewMessage = computed(() => {
    const sample = csvData.value?.preview_rows?.[0] || { name: 'ياسمين صبري', recipient_id: '1784140510294' };
    return messageText.value
        .replace(/{name}|{{name}}/g, sample.name || 'عميلنا')
        .replace(/{id}|{{id}}/g, sample.recipient_id || '');
});

const submitBulkSend = async () => {
    if (audienceMode.value === 'csv' && (!csvData.value || !csvData.value.recipients?.length)) {
        alert('يرجى رفع ملف CSV يحتوي على معرفات حسابات إنستجرام (IGSIDs).');
        return;
    }

    if (audienceMode.value === 'existing_contacts' && props.existingContactsCount === 0) {
        alert('لا توجد جهات اتصال إنستجرام مسجلة حالياً في هذا المتجر.');
        return;
    }

    if (!messageText.value.trim()) {
        alert('يرجى إدخال نص الرسالة.');
        return;
    }

    isSubmitting.value = true;
    try {
        const payload = {
            name: campaignName.value || `حملة إنستجرام جماعية - ${new Date().toLocaleDateString('ar-EG')}`,
            message: messageText.value,
            target_audience: audienceMode.value,
            recipients: audienceMode.value === 'csv' ? csvData.value.recipients : [],
            interval_seconds: intervalSeconds.value,
            jitter_seconds: jitterSeconds.value,
            save_to_contacts: saveToContacts.value,
            auto_start: autoStart.value,
            filename: csvData.value?.filename,
        };

        await axios.post(route('instagram-sender.send'), payload);
        alert('تم إنشاء حملة إنستجرام بنجاح وبدء جدولتها.');

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

const startCampaign = async (campaign) => {
    isActionLoading.value[campaign.id] = true;
    try {
        await axios.post(route('instagram-sender.start', campaign.id));
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
        await axios.post(route('instagram-sender.pause', campaign.id));
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('حدث خطأ أثناء إيقاف الحملة.');
    } finally {
        isActionLoading.value[campaign.id] = false;
    }
};

const deleteCampaign = async (campaign) => {
    if (!confirm(`هل أنت متأكد من حذف حملة "${campaign.name}"؟`)) return;
    try {
        await axios.delete(route('instagram-sender.destroy', campaign.id));
        router.reload({ preserveScroll: true });
    } catch (e) {
        alert('حدث خطأ أثناء حذف الحملة.');
    }
};

onMounted(() => {
    pollingInterval.value = setInterval(() => {
        const hasProcessing = props.campaigns.data?.some(c => c.status === 'processing');
        if (hasProcessing) {
            router.reload({ only: ['campaigns', 'stats'], preserveScroll: true });
        }
    }, 6000);
});

onUnmounted(() => {
    if (pollingInterval.value) clearInterval(pollingInterval.value);
});
</script>

<template>
    <Head title="مرسل إنستجرام المعتمد (Instagram Meta Sender)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <Send class="w-5 h-5 text-pink-500" />
                            <span>مرسل إنستجرام المعتمد (Instagram Meta Sender)</span>
                        </h2>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <ShieldCheck class="w-3 h-3" />
                            <span>نظام آمن 100% ضد الحظر</span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        إرسال رسائل خاصة آمنة للعملاء المؤهلين وفق سياسات ميتا الرسمية (نافذة الـ 24 ساعة + المشتركون في الإشعارات المتكررة) لمنع حظر الحساب نهائياً
                    </p>
                </div>

                <!-- Instagram Status Badge -->
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                        :class="instagramAccount?.status === 'connected' ? 'bg-pink-500/10 text-pink-400 border border-pink-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                    >
                        <span class="w-2 h-2 rounded-full" :class="instagramAccount?.status === 'connected' ? 'bg-pink-400 animate-pulse' : 'bg-rose-400'"></span>
                        <span>{{ instagramAccount?.status === 'connected' ? `@${instagramAccount.username || instagramAccount.instagram_business_id}` : 'الحساب غير متصل' }}</span>
                    </span>
                    <Link
                        v-if="instagramAccount?.status !== 'connected'"
                        :href="route('channels.index', { business: business.id })"
                        class="text-xs text-pink-400 hover:underline flex items-center gap-1"
                    >
                        <span>ربط الحساب</span>
                        <ExternalLink class="w-3 h-3" />
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- 1. STATS OVERVIEW -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 block">إجمالي الحملات</span>
                        <span class="text-2xl font-black text-white font-mono">{{ stats.total_campaigns }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-800 text-pink-400">
                        <FileSpreadsheet class="w-5 h-5" />
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 block">الحملات النشطة</span>
                        <span class="text-2xl font-black text-pink-400 font-mono">{{ stats.active_campaigns }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-800 text-pink-400">
                        <Zap class="w-5 h-5" />
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 block">إجمالي الرسائل المرسلة</span>
                        <span class="text-2xl font-black text-emerald-400 font-mono">{{ stats.total_messages_sent }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-800 text-emerald-400">
                        <CheckCircle2 class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- 2. CAMPAIGN CREATOR -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- LEFT BOX: Target Audience & Compliant Contacts (6 cols) -->
                <div class="lg:col-span-6 bg-slate-900/85 border border-slate-800/90 rounded-xl p-5 space-y-4">
                    <div class="border-b border-slate-800/80 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono flex items-center gap-2">
                                <Users class="w-4 h-4 text-pink-400" />
                                <span>1. تحديد الجمهور المستهدف</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                إرسال شرعي معتمد من ميتا لحماية حسابك من الحظر نهائياً
                            </p>
                        </div>

                        <a
                            v-if="audienceMode === 'csv'"
                            :href="route('instagram-sender.template')"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition"
                        >
                            <Download class="w-3.5 h-3.5" />
                            <span>نموذج CSV</span>
                        </a>
                    </div>

                    <!-- Audience Toggle -->
                    <div class="flex items-center gap-2 bg-slate-950 p-1 rounded-lg border border-slate-800 text-xs">
                        <button
                            type="button"
                            @click="audienceMode = 'existing_contacts'"
                            class="flex-1 py-2 px-3 rounded font-bold transition text-center flex items-center justify-center gap-1.5 cursor-pointer"
                            :class="audienceMode === 'existing_contacts' ? 'bg-pink-600 text-white shadow' : 'text-slate-400 hover:text-white'"
                        >
                            <ShieldCheck class="w-3.5 h-3.5 text-emerald-400" />
                            <span>العملاء المؤهلون بالمتجر ({{ existingContactsCount }})</span>
                        </button>
                        <button
                            type="button"
                            @click="audienceMode = 'csv'"
                            class="flex-1 py-2 px-3 rounded font-bold transition text-center text-slate-400 hover:text-white cursor-pointer"
                            :class="audienceMode === 'csv' ? 'bg-slate-800 text-white shadow' : 'text-slate-400 hover:text-white'"
                        >
                            <span>رفع ملف CSV خارجي</span>
                        </button>
                    </div>

                    <!-- Mode A: Meta Compliant Existing Contacts (PRIMARY) -->
                    <div v-if="audienceMode === 'existing_contacts'" class="space-y-3">
                        <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <ShieldCheck class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-white">الجمهور المؤهل للإرسال المباشر</h4>
                                        <p class="text-[10px] text-slate-400">متوافق 100% مع شروط وسياسات منصة إنستجرام وميتا</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    آمن 100%
                                </span>
                            </div>

                            <!-- Count Display -->
                            <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-slate-400 block font-sans">إجمالي العملاء المتاح مراسلتهم:</span>
                                    <span class="text-2xl font-black font-mono text-white tracking-tight">{{ existingContactsCount }}</span>
                                    <span class="text-xs text-slate-400 ms-1.5">عميل</span>
                                </div>
                                <div class="text-end text-[11px] space-y-1">
                                    <div class="flex items-center gap-1.5 text-emerald-400 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span>{{ active24hCount }} نشط (24 ساعة)</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-amber-400 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        <span>{{ optedInCount }} وافق على الإشعارات</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Anti-Ban Rule explanation -->
                            <div class="text-[11px] text-slate-400 space-y-1.5 bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                                <div class="flex items-start gap-1.5 text-slate-300 font-medium">
                                    <ShieldCheck class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5" />
                                    <span>ضمان حماية الحساب من الحظر (Anti-Ban Guarantee):</span>
                                </div>
                                <p class="text-[10px] text-slate-400 leading-relaxed ps-5">
                                    إنستجرام يمنع الرسائل الباردة العشوائية. نظامنا يرسل فقط لمن تواصل معك في الإنبوكس خلال آخر 24 ساعة، أو من وافق صراحةً على تلقي إشعارات العروض المتكررة (Recurring Notifications).
                                </p>
                            </div>

                            <!-- Tips to grow audience -->
                            <div v-if="existingContactsCount === 0" class="p-3 rounded-lg bg-pink-950/30 border border-pink-500/20 space-y-1.5">
                                <span class="text-xs font-bold text-pink-300 block">💡 كيف تزيد عدد العملاء المؤهلين بدون أي حظر؟</span>
                                <ul class="text-[10px] text-slate-300 space-y-1 list-disc list-inside ps-1 leading-relaxed">
                                    <li><strong>التعليقات التلقائية (Comment-to-DM):</strong> انشر بوست أو ريلز واطلب تعليقاً ليرسل لهم النظام رسالة بالخاص فوراً.</li>
                                    <li><strong>طلب الموافقة التلقائي:</strong> يرسل نظامنا بطاقة ميتا الرسمية للموافقة على الإشعارات المتكررة لكل من يراسل الحساب.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Mode B: CSV File Upload (WITH STRICT POLICY WARNING) -->
                    <div v-else class="space-y-3">
                        <div class="p-3 rounded-xl bg-amber-950/40 border border-amber-500/40 text-xs text-amber-300 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold">
                                <ShieldAlert class="w-4 h-4 text-amber-400 shrink-0" />
                                <span>تنبيه هام لسياسات ميتا وإنستجرام:</span>
                            </div>
                            <p class="text-[10px] text-amber-200/90 leading-relaxed">
                                إنستجرام لا يقبل أرقام الهواتف أو روابط الحسابات العادية بالإنبوكس. يجب أن يحتوي الملف حصراً على معرّفات (IGSIDs) سابقة لحسابك. للإرسال الآمن المضمون، استخدم تبويب <strong>العملاء المؤهلون بالمتجر</strong>.
                            </p>
                        </div>

                        <input
                            ref="fileInput"
                            type="file"
                            accept=".csv,text/csv"
                            class="hidden"
                            @change="handleFileChange"
                        />

                        <div
                            @click="$refs.fileInput.click()"
                            class="border-2 border-dashed border-slate-800 hover:border-pink-500/50 rounded-xl p-6 text-center cursor-pointer bg-slate-950/40 hover:bg-slate-950/80 transition group"
                        >
                            <Upload class="w-8 h-8 text-slate-500 group-hover:text-pink-400 mx-auto mb-2 transition" />
                            <p class="text-xs font-bold text-slate-200">
                                {{ selectedFile ? selectedFile.name : 'اضغط لاختيار ملف CSV بمعرفات حسابات إنستجرام (IGSIDs)' }}
                            </p>
                            <p class="text-[11px] text-slate-500 mt-1">يجب أن يحتوي الملف على عمود igsid وعمود name (اختياري)</p>
                        </div>

                        <!-- CSV Preview Table -->
                        <div v-if="csvData" class="space-y-3">
                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="p-2 rounded bg-slate-950 border border-slate-800">
                                    <span class="text-[10px] text-slate-500 block">إجمالي السطور</span>
                                    <span class="font-mono font-bold text-white">{{ csvData.total_rows }}</span>
                                </div>
                                <div class="p-2 rounded bg-slate-950 border border-pink-500/20">
                                    <span class="text-[10px] text-pink-400 block">معرفات صالحة</span>
                                    <span class="font-mono font-bold text-pink-400">{{ csvData.valid_count }}</span>
                                </div>
                                <div class="p-2 rounded bg-slate-950 border border-slate-800">
                                    <span class="text-[10px] text-slate-500 block">غير صالحة</span>
                                    <span class="font-mono font-bold text-slate-400">{{ csvData.invalid_count }}</span>
                                </div>
                            </div>

                            <!-- WALLET BALANCE CHECK CARD -->
                            <div v-if="csvData.wallet" class="p-3 rounded-xl border transition" :class="csvData.wallet.can_afford ? 'bg-pink-950/40 border-pink-500/30' : 'bg-rose-950/50 border-rose-500/50'">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <Wallet class="w-4 h-4" :class="csvData.wallet.can_afford ? 'text-pink-400' : 'text-rose-400'" />
                                        <span class="font-bold text-white">فحص رصيد محفظة الرسائل:</span>
                                    </div>
                                    <div class="font-mono font-bold" :class="csvData.wallet.can_afford ? 'text-pink-400' : 'text-rose-400'">
                                        {{ csvData.wallet.current_credits }} رسالة متاحة
                                    </div>
                                </div>

                                <div v-if="!csvData.wallet.can_afford" class="mt-2 text-[11px] text-rose-300 flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-rose-800/40">
                                    <div>
                                        <span class="font-bold">⚠️ الرصيد لا يكفي لإطلاق الحملة!</span>
                                        <span class="block text-slate-400 text-[10px]">مطلوب {{ csvData.wallet.required_credits }} رسالة، ينقصك {{ csvData.wallet.missing_credits }} رسالة.</span>
                                    </div>
                                    <Link
                                        :href="route('wallet.index')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition self-start sm:self-auto shadow"
                                    >
                                        <Coins class="w-3.5 h-3.5" />
                                        <span>شحن المحفظة</span>
                                    </Link>
                                </div>

                                <div v-else class="mt-1 text-[11px] text-pink-300">
                                    ✓ رصيدك كافٍ تماماً لإطلاق هذه الحملة (سيتم استهلاك {{ csvData.wallet.required_credits }} رسالة).
                                </div>
                            </div>

                            <div class="border border-slate-800 rounded-lg overflow-hidden max-h-40 overflow-y-auto">
                                <table class="w-full text-start text-[11px]">
                                    <thead class="bg-slate-950 text-slate-500 border-b border-slate-800 uppercase font-mono">
                                        <tr>
                                            <th class="py-1.5 px-2.5 text-start">الاسم</th>
                                            <th class="py-1.5 px-2.5 text-start">معرف الحساب / IGSID</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/80 text-slate-300 font-mono">
                                        <tr v-for="(row, idx) in csvData.preview_rows" :key="idx" class="hover:bg-slate-950/50">
                                            <td class="py-1.5 px-2.5">{{ row.name }}</td>
                                            <td class="py-1.5 px-2.5 text-pink-400">{{ row.recipient_id }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT BOX: Message Composer & Safe Throttling (6 cols) -->
                <div class="lg:col-span-6 bg-slate-900/85 border border-slate-800/90 rounded-xl p-5 space-y-4">
                    <div class="border-b border-slate-800/80 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono flex items-center gap-2">
                                <Send class="w-4 h-4 text-pink-400" />
                                <span>2. صياغة الرسالة والجدولة</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                تخصيص نص الرسالة بالمتغيرات وفترات التأخير لضمان وصول الرسائل
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">اسم الحملة</label>
                        <input
                            v-model="campaignName"
                            type="text"
                            placeholder="مثال: رسائل خصومات لعملاء إنستجرام"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-pink-500 font-sans"
                        />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-slate-300">نص الرسالة</label>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="insertTag('{name}')"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-800 hover:bg-slate-750 text-pink-400 border border-slate-700 cursor-pointer"
                                >
                                    + {name} الاسم
                                </button>
                            </div>
                        </div>
                        <textarea
                            v-model="messageText"
                            rows="4"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-xs text-white focus:outline-none focus:border-pink-500 font-sans"
                            placeholder="اكتب رسالة إنستجرام دايركت هنا..."
                        ></textarea>
                    </div>

                    <div class="bg-slate-950/80 rounded-lg p-3 border border-slate-800/80">
                        <span class="text-[10px] font-mono text-slate-500 block mb-1">معاينة الرسالة:</span>
                        <p class="text-xs text-pink-300 whitespace-pre-line font-sans">{{ previewMessage }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 bg-slate-950/70 border border-slate-800 rounded-xl p-3">
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">الفاصل الزمني (ثواني)</label>
                            <input
                                v-model.number="intervalSeconds"
                                type="number"
                                min="2"
                                max="300"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white font-mono focus:outline-none focus:border-pink-500"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">التغيير العشوائي (ثواني)</label>
                            <input
                                v-model.number="jitterSeconds"
                                type="number"
                                min="0"
                                max="60"
                                class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white font-mono focus:outline-none focus:border-pink-500"
                            />
                        </div>
                    </div>

                    <div v-if="csvData && csvData.wallet && !csvData.wallet.can_afford" class="space-y-2">
                        <Link
                            :href="route('wallet.index')"
                            class="w-full py-3 px-4 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white transition cursor-pointer shadow-lg flex items-center justify-center gap-2"
                        >
                            <Coins class="w-4 h-4" />
                            <span>رصيدك غير كافٍ • اشحن محفظة الرسائل أولاً</span>
                        </Link>
                    </div>

                    <button
                        v-else
                        type="button"
                        @click="submitBulkSend"
                        :disabled="isSubmitting || (instagramAccount?.status !== 'connected')"
                        class="w-full py-3 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-white shadow-lg cursor-pointer"
                        :class="instagramAccount?.status === 'connected' ? 'bg-pink-600 hover:bg-pink-500 active:bg-pink-700' : 'bg-slate-800 text-slate-500 cursor-not-allowed'"
                    >
                        <Send class="w-4 h-4" />
                        <span>{{ isSubmitting ? 'جاري بدء الحملة...' : 'بدء إرسال حملة إنستجرام' }}</span>
                    </button>
                </div>
            </div>

            <!-- 3. CAMPAIGN HISTORY & LOGS TABLE -->
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono">
                        سجل حملات إنستجرام السابقة
                    </h3>
                </div>

                <div v-if="campaigns.data?.length === 0" class="py-8 text-center text-slate-500 text-xs">
                    لم يتم إنشاء أي حملات إنستجرام بعد.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="bg-slate-950 text-slate-400 uppercase font-mono text-[11px] border-b border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 text-start">اسم الحملة</th>
                                <th class="py-2.5 px-3 text-start">الحالة</th>
                                <th class="py-2.5 px-3 text-start">المرسل / الإجمالي</th>
                                <th class="py-2.5 px-3 text-start">نسبة الإنجاز</th>
                                <th class="py-2.5 px-3 text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80 text-slate-300">
                            <tr v-for="c in campaigns.data" :key="c.id" class="hover:bg-slate-950/40">
                                <td class="py-3 px-3 font-medium text-white">{{ c.name }}</td>
                                <td class="py-3 px-3">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                                        :class="{
                                            'bg-emerald-500/10 text-emerald-400': c.status === 'completed',
                                            'bg-pink-500/10 text-pink-400 animate-pulse': c.status === 'processing',
                                            'bg-amber-500/10 text-amber-400': c.status === 'paused',
                                            'bg-slate-800 text-slate-400': c.status === 'draft',
                                        }"
                                    >
                                        {{ c.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-mono">
                                    {{ c.sent_count }} / {{ c.total_recipients }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="w-32 bg-slate-800 rounded-full h-2 overflow-hidden">
                                        <div
                                            class="bg-pink-500 h-full rounded-full transition-all duration-300"
                                            :style="{ width: (c.total_recipients > 0 ? (c.sent_count / c.total_recipients) * 100 : 0) + '%' }"
                                        ></div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-end space-x-1.5 rtl:space-x-reverse">
                                    <button
                                        v-if="c.status === 'paused' || c.status === 'draft'"
                                        @click="startCampaign(c)"
                                        class="p-1.5 rounded hover:bg-slate-800 text-emerald-400"
                                        title="تشغيل"
                                    >
                                        <Play class="w-4 h-4" />
                                    </button>
                                    <button
                                        v-else-if="c.status === 'processing'"
                                        @click="pauseCampaign(c)"
                                        class="p-1.5 rounded hover:bg-slate-800 text-amber-400"
                                        title="إيقاف مؤقت"
                                    >
                                        <Pause class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="deleteCampaign(c)"
                                        class="p-1.5 rounded hover:bg-slate-800 text-rose-400"
                                        title="حذف"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
