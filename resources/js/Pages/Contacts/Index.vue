<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import {
    Users,
    Search,
    Download,
    MessageCircle,
    Phone,
    Mail,
    Tag,
    FileText,
    ExternalLink,
    X,
    Save,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Edit2,
} from 'lucide-vue-next';

const props = defineProps({
    business: Object,
    contacts: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
let searchTimeout = null;

const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('contacts.index'),
            { search: searchQuery.value },
            { preserveState: true, replace: true }
        );
    }, 350);
};

// Selected Contact Modal
const selectedContact = ref(null);
const isDetailModalOpen = ref(false);
const isSaving = ref(false);

const editForm = ref({
    name: '',
    phone: '',
    email: '',
    notes: '',
    tags: '',
});

const openContactDetail = async (contact) => {
    selectedContact.value = contact;
    editForm.value = {
        name: contact.name || '',
        phone: contact.phone || '',
        email: contact.email || '',
        notes: contact.notes || '',
        tags: Array.isArray(contact.tags) ? contact.tags.join(', ') : (contact.tags || ''),
    };
    isDetailModalOpen.value = true;

    // Fetch fresh details with conversations
    try {
        const res = await axios.get(route('contacts.show', contact.id));
        if (res.data?.contact) {
            selectedContact.value = res.data.contact;
        }
    } catch (e) {
        console.error('Error fetching contact details', e);
    }
};

const saveContact = async () => {
    if (!selectedContact.value || isSaving.value) return;

    isSaving.value = true;
    try {
        const tagsArray = editForm.value.tags
            ? editForm.value.tags.split(',').map(t => t.trim()).filter(Boolean)
            : [];

        const res = await axios.patch(route('contacts.update', selectedContact.value.id), {
            name: editForm.value.name,
            phone: editForm.value.phone,
            email: editForm.value.email,
            notes: editForm.value.notes,
            tags: tagsArray,
        });

        if (res.data?.contact) {
            // Update in local contacts list
            const idx = props.contacts.data.findIndex(c => c.id === selectedContact.value.id);
            if (idx !== -1) {
                props.contacts.data[idx] = {
                    ...props.contacts.data[idx],
                    ...res.data.contact,
                };
            }
            selectedContact.value = {
                ...selectedContact.value,
                ...res.data.contact,
            };
        }
        isDetailModalOpen.value = false;
    } catch (e) {
        console.error('Failed to update contact', e);
        alert(e.response?.data?.message || 'حدث خطأ أثناء حفظ جهة الاتصال.');
    } finally {
        isSaving.value = false;
    }
};

const getPlatformBadge = (platform) => {
    switch (platform) {
        case 'whatsapp':
            return { name: 'واتساب', color: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' };
        case 'facebook':
            return { name: 'فيسبوك', color: 'bg-blue-500/10 text-blue-400 border-blue-500/20' };
        case 'instagram':
            return { name: 'إنستجرام', color: 'bg-pink-500/10 text-pink-400 border-pink-500/20' };
        case 'telegram':
            return { name: 'تيليجرام', color: 'bg-sky-500/10 text-sky-400 border-sky-500/20' };
        default:
            return { name: platform, color: 'bg-slate-500/10 text-slate-400 border-slate-500/20' };
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`إدارة جهات الاتصال - ${business.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-blue-600/10 border border-blue-500/20 rounded-xl text-blue-400">
                        <Users class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white tracking-wide">
                            جهات الاتصال والعملاء
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            إدارة العملاء، سجل المحادثات، الوسوم والملاحظات الخاصة بمتجر {{ business.name }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <!-- Export CSV Button -->
                    <a
                        :href="route('contacts.export')"
                        target="_blank"
                        class="cursor-pointer flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 text-xs font-bold transition shadow-sm"
                    >
                        <Download class="w-4 h-4 text-slate-400" />
                        <span>تصدير CSV</span>
                    </a>
                </div>
            </div>
        </template>

        <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Search & Filter Bar -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-96">
                    <input
                        v-model="searchQuery"
                        @input="onSearchInput"
                        type="text"
                        placeholder="بحث بالاسم، رقم الهاتف، أو البريد الإلكتروني..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl pe-4 ps-10 py-2.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                    />
                    <Search class="w-4 h-4 text-slate-500 absolute start-3.5 top-3 pointer-events-none" />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''; onSearchInput()"
                        class="cursor-pointer absolute end-3 top-2.5 text-slate-500 hover:text-slate-300 p-0.5"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="text-xs text-slate-400 font-mono">
                    إجمالي النتائج: <span class="text-white font-bold">{{ contacts.total }}</span> عميل
                </div>
            </div>

            <!-- Contacts Table Card -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-xl overflow-hidden backdrop-blur-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase font-semibold">
                            <tr>
                                <th class="py-3.5 px-4 text-start">العميل</th>
                                <th class="py-3.5 px-4 text-start">رقم الهاتف</th>
                                <th class="py-3.5 px-4 text-start">المنصة</th>
                                <th class="py-3.5 px-4 text-start">الوسوم</th>
                                <th class="py-3.5 px-4 text-center">المحادثات</th>
                                <th class="py-3.5 px-4 text-start">آخر تفاعل</th>
                                <th class="py-3.5 px-4 text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80 text-slate-300">
                            <tr
                                v-if="contacts.data.length === 0"
                                class="hover:bg-transparent"
                            >
                                <td colspan="7" class="py-12 text-center text-slate-500">
                                    <Users class="w-10 h-10 mx-auto mb-2 opacity-40 text-slate-600" />
                                    <span>لا توجد جهات اتصال مطابقة لبحثك.</span>
                                </td>
                            </tr>

                            <tr
                                v-for="contact in contacts.data"
                                :key="contact.id"
                                @click="openContactDetail(contact)"
                                class="hover:bg-slate-800/50 cursor-pointer transition"
                            >
                                <!-- Name & Avatar -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-200 shrink-0">
                                            {{ contact.name ? contact.name.charAt(0).toUpperCase() : 'ع' }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-white truncate max-w-[150px] sm:max-w-xs">
                                                {{ contact.name || 'عميل بدون اسم' }}
                                            </p>
                                            <p v-if="contact.email" class="text-[11px] text-slate-500 truncate max-w-[150px]">
                                                {{ contact.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Phone -->
                                <td class="py-3.5 px-4 font-mono text-slate-400">
                                    {{ contact.phone || '—' }}
                                </td>

                                <!-- Platform -->
                                <td class="py-3.5 px-4">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border"
                                        :class="getPlatformBadge(contact.platform).color"
                                    >
                                        {{ getPlatformBadge(contact.platform).name }}
                                    </span>
                                </td>

                                <!-- Tags -->
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1 max-w-[180px]">
                                        <template v-if="Array.isArray(contact.tags) && contact.tags.length > 0">
                                            <span
                                                v-for="(tag, i) in contact.tags.slice(0, 2)"
                                                :key="i"
                                                class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 text-[10px] font-medium border border-slate-700"
                                            >
                                                {{ tag }}
                                            </span>
                                            <span
                                                v-if="contact.tags.length > 2"
                                                class="px-1.5 py-0.5 rounded-md bg-slate-800/60 text-slate-500 text-[10px]"
                                            >
                                                +{{ contact.tags.length - 2 }}
                                            </span>
                                        </template>
                                        <span v-else class="text-slate-600">—</span>
                                    </div>
                                </td>

                                <!-- Conversations Count -->
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 font-mono font-bold px-2 py-0.5 rounded-full bg-slate-800 text-slate-300">
                                        <MessageCircle class="w-3 h-3 text-slate-400" />
                                        {{ contact.conversations_count ?? 0 }}
                                    </span>
                                </td>

                                <!-- Last Interaction -->
                                <td class="py-3.5 px-4 text-slate-400 text-[11px]">
                                    {{ formatDate(contact.conversations?.[0]?.last_message_at || contact.updated_at) }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <button
                                        @click.stop="openContactDetail(contact)"
                                        class="cursor-pointer p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition"
                                        title="تعديل وعرض التفاصيل"
                                    >
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="contacts.links && contacts.links.length > 3"
                    class="p-4 border-t border-slate-800 flex items-center justify-between"
                >
                    <div class="text-xs text-slate-500">
                        عرض {{ contacts.from ?? 0 }} إلى {{ contacts.to ?? 0 }} من أصل {{ contacts.total }}
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in contacts.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-semibold transition',
                                link.active ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Detail & Edit Modal -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isDetailModalOpen && selectedContact"
                class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
                @click.self="isDetailModalOpen = false"
            >
                <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl p-6 shadow-2xl space-y-6 my-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-white text-base">
                                {{ selectedContact.name ? selectedContact.name.charAt(0).toUpperCase() : 'ع' }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">{{ selectedContact.name || 'عميل بدون اسم' }}</h3>
                                <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                                    <span
                                        class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-semibold border"
                                        :class="getPlatformBadge(selectedContact.platform).color"
                                    >
                                        {{ getPlatformBadge(selectedContact.platform).name }}
                                    </span>
                                    <span class="font-mono text-slate-500">{{ selectedContact.platform_sender_id }}</span>
                                </div>
                            </div>
                        </div>

                        <button
                            @click="isDetailModalOpen = false"
                            class="cursor-pointer p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Editable Fields Form -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">الاسم</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                placeholder="اسم العميل"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">رقم الهاتف</label>
                            <input
                                v-model="editForm.phone"
                                type="text"
                                placeholder="+20..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-blue-500"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">البريد الإلكتروني</label>
                            <input
                                v-model="editForm.email"
                                type="email"
                                placeholder="client@example.com"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">الوسوم (مفصولة بفواصل)</label>
                            <input
                                v-model="editForm.tags"
                                type="text"
                                placeholder="عميل مميز, طلب جديد, استفسار شحن"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">الملاحظات الداخلية</label>
                            <textarea
                                v-model="editForm.notes"
                                rows="3"
                                placeholder="سجل ملاحظاتك الخاصة بهذا العميل لمشاركتها مع فريق الدعم..."
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-blue-500 leading-relaxed"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Conversation History Section -->
                    <div class="pt-3 border-t border-slate-800">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                            <MessageCircle class="w-3.5 h-3.5 text-blue-400" />
                            سجل المحادثات
                        </h4>

                        <div v-if="selectedContact.conversations && selectedContact.conversations.length > 0" class="space-y-2 max-h-48 overflow-y-auto">
                            <div
                                v-for="conv in selectedContact.conversations"
                                :key="conv.id"
                                class="p-3 bg-slate-950/70 border border-slate-800/80 rounded-xl flex items-center justify-between gap-3 text-xs"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-white capitalize">{{ conv.platform }}</span>
                                        <span
                                            class="px-1.5 py-0.2 rounded text-[10px] font-semibold"
                                            :class="conv.status === 'open' ? 'bg-emerald-950 text-emerald-400' : 'bg-slate-800 text-slate-400'"
                                        >
                                            {{ conv.status === 'open' ? 'مفتوحة' : 'مغلقة' }}
                                        </span>
                                    </div>
                                    <p class="text-slate-400 truncate text-[11px] mt-0.5">
                                        {{ conv.latest_message?.body || 'لا توجد رسائل سابقة' }}
                                    </p>
                                </div>

                                <Link
                                    :href="route('inbox.index', { conversation_id: conv.id })"
                                    class="cursor-pointer shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition"
                                >
                                    <span>فتح في الوارد</span>
                                    <ExternalLink class="w-3 h-3" />
                                </Link>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-500 py-2">لا توجد محادثات سابقة لهذا العميل.</p>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="isDetailModalOpen = false"
                            class="cursor-pointer px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition"
                        >
                            إلغاء
                        </button>
                        <button
                            type="button"
                            @click="saveContact"
                            :disabled="isSaving"
                            class="cursor-pointer px-5 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white transition flex items-center gap-2 shadow-lg shadow-blue-600/20 disabled:opacity-50"
                        >
                            <Save class="w-4 h-4" />
                            <span>{{ isSaving ? 'جاري الحفظ...' : 'حفظ التعديلات' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>
