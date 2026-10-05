<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import {
    Search,
    Send,
    CheckCircle2,
    Clock,
    User,
    Sparkles,
    MessageCircle,
    Paperclip,
    Filter,
    ChevronDown,
    Zap,
    CornerDownLeft,
    CheckCheck,
    Check,
    RotateCcw,
    Settings,
    X,
    Layers,
    MessageSquare,
    Camera
} from 'lucide-vue-next';

const props = defineProps({
    business: Object,
    conversations: Array,
    quickReplies: Array,
    activeConversationId: [String, Number],
});

// State
const conversationsList = ref([...props.conversations]);
const activeConv = ref(null);
const messages = ref([]);
const messageText = ref('');
const searchQuery = ref('');
const selectedPlatform = ref('all'); // all, whatsapp, facebook, instagram, telegram
const selectedStatus = ref('all'); // all, open, closed
const isLoadingMessages = ref(false);
const isSending = ref(false);
const messagesContainer = ref(null);

// Platform Configuration
const platformList = [
    { id: 'all', name: 'جميع المنصات', icon: Layers, color: 'text-blue-400' },
    { id: 'whatsapp', name: 'واتساب', icon: MessageSquare, color: 'text-emerald-400' },
    { id: 'facebook', name: 'فيسبوك', icon: MessageCircle, color: 'text-blue-500' },
    { id: 'instagram', name: 'إنستجرام', icon: Camera, color: 'text-pink-400' },
    { id: 'telegram', name: 'تيليجرام', icon: Send, color: 'text-sky-400' },
];

const statusOptions = [
    { id: 'all', label: 'الكل' },
    { id: 'open', label: 'مفتوحة' },
    { id: 'closed', label: 'مغلقة' },
];

const getPlatformName = (platform) => {
    switch (platform) {
        case 'whatsapp': return 'واتساب';
        case 'facebook': return 'فيسبوك';
        case 'instagram': return 'إنستجرام';
        case 'telegram': return 'تيليجرام';
        default: return platform;
    }
};

const getPlatformBadgeColor = (platform) => {
    switch (platform) {
        case 'whatsapp': return 'bg-emerald-600 text-white';
        case 'facebook': return 'bg-blue-600 text-white';
        case 'instagram': return 'bg-pink-600 text-white';
        case 'telegram': return 'bg-sky-500 text-white';
        default: return 'bg-slate-700 text-white';
    }
};

const getPlatformCount = (platformId) => {
    if (platformId === 'all') return conversationsList.value.length;
    return conversationsList.value.filter(c => c.platform === platformId).length;
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    try {
        return new Date(dateStr).toLocaleTimeString('ar-EG', {
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return '';
    }
};

// Filtered Conversations
const filteredConversations = computed(() => {
    return conversationsList.value.filter(c => {
        const matchesPlatform = selectedPlatform.value === 'all' || c.platform === selectedPlatform.value;
        const matchesStatus = selectedStatus.value === 'all' || c.status === selectedStatus.value;
        const matchesSearch = !searchQuery.value ||
            c.contact?.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            c.contact?.phone?.includes(searchQuery.value) ||
            c.latest_message?.body?.toLowerCase().includes(searchQuery.value.toLowerCase());

        return matchesPlatform && matchesStatus && matchesSearch;
    });
});

// Select a conversation and load messages
const selectConversation = async (conv) => {
    activeConv.value = conv;
    isLoadingMessages.value = true;
    messages.value = [];

    try {
        const res = await axios.get(route('inbox.messages', conv.id));
        messages.value = res.data.messages;
        conv.unread_count = 0;
        await scrollToBottom();
    } catch (e) {
        console.error('Failed to load messages', e);
    } finally {
        isLoadingMessages.value = false;
    }
};

// Send message
const sendMessage = async () => {
    if (!messageText.value.trim() || !activeConv.value || isSending.value) return;

    const text = messageText.value.trim();
    messageText.value = '';
    isSending.value = true;

    try {
        const res = await axios.post(route('inbox.send', activeConv.value.id), {
            body: text,
        });

        if (res.data.status === 'success') {
            messages.value.push(res.data.message);
            activeConv.value.last_message_at = new Date().toISOString();
            if (activeConv.value.latest_message) {
                activeConv.value.latest_message.body = text;
            }
            await scrollToBottom();
        }
    } catch (e) {
        console.error('Failed to send message', e);
        messageText.value = text;
    } finally {
        isSending.value = false;
    }
};

// Insert quick reply
const insertQuickReply = (body) => {
    messageText.value = body;
};

// Toggle status (open / closed)
const updateStatus = async (status) => {
    if (!activeConv.value) return;
    try {
        await axios.patch(route('inbox.status', activeConv.value.id), { status });
        activeConv.value.status = status;
    } catch (e) {
        console.error('Failed to update status', e);
    }
};

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// On Mounted
onMounted(() => {
    if (props.activeConversationId) {
        const found = conversationsList.value.find(c => c.id == props.activeConversationId);
        if (found) selectConversation(found);
    } else if (conversationsList.value.length > 0) {
        selectConversation(conversationsList.value[0]);
    }
});
</script>

<template>
    <Head :title="`صندوق الوارد الموحد - ${business.name}`" />

    <AuthenticatedLayout :fullHeight="true">
        <!-- Main Inbox Wrapper (strictly fills remaining height with no page scrollbar) -->
        <div class="h-full flex flex-1 overflow-hidden bg-slate-950 text-slate-100">

            <!-- 1. Right Column (Sidebar in RTL): Platform & Status Filters -->
            <aside class="w-64 border-l border-slate-800 bg-slate-900/90 flex flex-col justify-between shrink-0 p-4">
                <div class="space-y-6">
                    <!-- Business Title Badge -->
                    <div class="pb-3 border-b border-slate-800/80">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-0.5">صندوق الوارد</span>
                        <h2 class="text-base font-bold text-white truncate">{{ business.name }}</h2>
                    </div>

                    <!-- Platforms Filter -->
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2.5">تصفية المنصات</span>
                        <div class="space-y-1">
                            <button
                                v-for="p in platformList"
                                :key="p.id"
                                @click="selectedPlatform = p.id"
                                class="cursor-pointer w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition"
                                :class="selectedPlatform === p.id ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800/80 hover:text-slate-200'"
                            >
                                <div class="flex items-center gap-2.5">
                                    <component
                                        :is="p.icon"
                                        class="w-4 h-4"
                                        :class="selectedPlatform === p.id ? 'text-white' : p.color"
                                    />
                                    <span>{{ p.name }}</span>
                                </div>
                                <span
                                    class="text-[11px] font-mono font-bold px-2 py-0.5 rounded-full"
                                    :class="selectedPlatform === p.id ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400'"
                                >
                                    {{ getPlatformCount(p.id) }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Status Filter (Segmented control) -->
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2.5">حالة المحادثة</span>
                        <div class="grid grid-cols-3 gap-1 p-1 bg-slate-950/80 rounded-xl border border-slate-800">
                            <button
                                v-for="s in statusOptions"
                                :key="s.id"
                                @click="selectedStatus = s.id"
                                class="cursor-pointer py-1.5 text-center text-xs font-semibold rounded-lg transition"
                                :class="selectedStatus === s.id ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                            >
                                {{ s.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Channels Setting Link at bottom -->
                <div class="pt-4 border-t border-slate-800">
                    <Link
                        :href="route('channels.index', business.id)"
                        class="cursor-pointer w-full flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-750 text-xs font-bold text-slate-300 border border-slate-700/80 transition"
                    >
                        <Settings class="w-3.5 h-3.5 text-slate-400" />
                        <span>إعدادات القنوات</span>
                    </Link>
                </div>
            </aside>

            <!-- 2. Middle Column: Conversations List -->
            <div class="w-80 md:w-96 border-l border-slate-800 bg-slate-900/40 flex flex-col shrink-0">
                <!-- Search Header -->
                <div class="p-4 border-b border-slate-800 shrink-0">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="بحث بالاسم أو نص الرسالة..."
                            class="w-full pe-4 ps-10 py-2.5 rounded-xl text-xs bg-slate-950 border border-slate-800 text-slate-200 placeholder-slate-500 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
                        />
                        <Search class="w-4 h-4 text-slate-500 absolute start-3 top-3 pointer-events-none" />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="cursor-pointer absolute end-3 top-2.5 text-slate-500 hover:text-slate-300 p-0.5"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div class="flex items-center justify-between mt-2.5 text-[11px] text-slate-400 px-1 font-medium">
                        <span>{{ filteredConversations.length }} محادثة</span>
                        <span v-if="selectedPlatform !== 'all'">{{ getPlatformName(selectedPlatform) }}</span>
                    </div>
                </div>

                <!-- Conversations Scrollable List -->
                <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-slate-800/40 p-2 space-y-1">
                    <div
                        v-if="filteredConversations.length === 0"
                        class="p-8 text-center text-slate-500 text-xs"
                    >
                        لا توجد محادثات مطابقة للشروط المحددة.
                    </div>

                    <div
                        v-for="conv in filteredConversations"
                        :key="conv.id"
                        @click="selectConversation(conv)"
                        class="p-3.5 rounded-xl transition cursor-pointer flex items-start gap-3 select-none"
                        :class="[
                            activeConv?.id === conv.id
                                ? 'bg-slate-800/90 border border-blue-500/50 shadow-sm'
                                : 'hover:bg-slate-800/40 border border-transparent'
                        ]"
                    >
                        <!-- Avatar with Platform Badge -->
                        <div class="relative shrink-0">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm"
                                :class="activeConv?.id === conv.id ? 'bg-blue-600/20 text-blue-400 border border-blue-500/40' : 'bg-slate-800 text-slate-300 border border-slate-700'"
                            >
                                {{ conv.contact?.name ? conv.contact.name.charAt(0).toUpperCase() : 'ع' }}
                            </div>
                            <!-- Small platform badge -->
                            <div
                                class="absolute -bottom-0.5 -start-0.5 w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-bold shadow-sm"
                                :class="getPlatformBadgeColor(conv.platform)"
                            >
                                {{ conv.platform.charAt(0).toUpperCase() }}
                            </div>
                        </div>

                        <!-- Content Layout: 2 distinct rows without text collisions -->
                        <div class="flex-1 min-w-0">
                            <!-- Row 1: Name (start) + Time (end) -->
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <h4 class="text-xs font-bold text-white truncate">
                                    {{ conv.contact?.name || conv.contact?.phone || 'عميل غير مسجل' }}
                                </h4>
                                <span class="text-[10px] text-slate-400 font-mono shrink-0">
                                    {{ formatTime(conv.last_message_at) }}
                                </span>
                            </div>

                            <!-- Row 2: Message preview (start) + Unread Badge (end) -->
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs text-slate-400 truncate flex-1 min-w-0">
                                    {{ conv.latest_message?.body || 'لا توجد رسائل سابقة' }}
                                </p>
                                <span
                                    v-if="conv.unread_count > 0"
                                    class="shrink-0 h-4 min-w-[1rem] px-1.5 rounded-full bg-blue-600 text-white text-[10px] font-bold flex items-center justify-center shadow-sm"
                                >
                                    {{ conv.unread_count }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Left Column (Main Chat in RTL): Chat & Message Flow -->
            <div class="flex-1 min-w-0 flex flex-col bg-slate-950">
                <!-- Chat Header -->
                <div
                    v-if="activeConv"
                    class="h-16 px-6 border-b border-slate-800 flex items-center justify-between bg-slate-900/70 backdrop-blur shrink-0"
                >
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-200 text-sm">
                                {{ activeConv.contact?.name ? activeConv.contact.name.charAt(0).toUpperCase() : 'ع' }}
                            </div>
                            <div
                                class="absolute -bottom-0.5 -start-0.5 w-3.5 h-3.5 rounded-full flex items-center justify-center text-[8px] font-bold"
                                :class="getPlatformBadgeColor(activeConv.platform)"
                            >
                                {{ activeConv.platform.charAt(0).toUpperCase() }}
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-white">
                                {{ activeConv.contact?.name || activeConv.contact?.phone || 'محادثة نشطة' }}
                            </h3>
                            <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                <span>عبر {{ getPlatformName(activeConv.platform) }}</span>
                                <span>•</span>
                                <span class="font-mono text-slate-500">{{ activeConv.contact?.platform_sender_id }}</span>
                                <span>•</span>
                                <span
                                    class="px-1.5 py-0.2 rounded text-[10px] font-semibold"
                                    :class="activeConv.status === 'open' ? 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/80' : 'bg-slate-800 text-slate-400'"
                                >
                                    {{ activeConv.status === 'open' ? 'مفتوحة' : 'مغلقة' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions (Status switch) -->
                    <div class="flex items-center gap-2">
                        <button
                            v-if="activeConv.status === 'open'"
                            @click="updateStatus('closed')"
                            class="cursor-pointer px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700/80 transition flex items-center gap-1.5"
                        >
                            <Check class="w-3.5 h-3.5 text-slate-400" />
                            <span>إغلاق المحادثة</span>
                        </button>
                        <button
                            v-else
                            @click="updateStatus('open')"
                            class="cursor-pointer px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 border border-emerald-800 transition flex items-center gap-1.5"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            <span>إعادة فتح</span>
                        </button>
                    </div>
                </div>

                <!-- Messages Scrollable Area -->
                <div
                    ref="messagesContainer"
                    class="flex-1 min-h-0 overflow-y-auto p-6 space-y-4"
                >
                    <div v-if="!activeConv" class="h-full flex items-center justify-center text-slate-500 text-xs">
                        اختر محادثة من القائمة لعرض الرسائل والرد على العميل
                    </div>

                    <div v-else-if="isLoadingMessages" class="h-full flex items-center justify-center text-slate-500 text-xs">
                        جاري تحميل الرسائل...
                    </div>

                    <div v-else-if="messages.length === 0" class="h-full flex items-center justify-center text-slate-500 text-xs">
                        لا توجد رسائل سابقة. أرسل رسالتك للعميل بالأسفل.
                    </div>

                    <!-- Message Bubbles -->
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="flex flex-col"
                        :class="msg.sender_type === 'agent' ? 'items-end' : 'items-start'"
                    >
                        <span class="text-[10px] text-slate-500 mb-1 px-1">
                            {{ msg.sender_type === 'agent' ? 'أنت (فريق الدعم)' : (activeConv.contact?.name || 'العميل') }}
                        </span>

                        <div
                            class="max-w-lg rounded-2xl px-4 py-3 text-xs leading-relaxed shadow-sm"
                            :class="[
                                msg.sender_type === 'agent'
                                    ? 'bg-blue-600 text-white rounded-tl-sm'
                                    : 'bg-slate-800/95 text-slate-100 border border-slate-700/80 rounded-tr-sm'
                            ]"
                        >
                            <p class="whitespace-pre-wrap">{{ msg.body }}</p>
                        </div>

                        <div class="flex items-center gap-1 text-[10px] text-slate-500 mt-1 px-1">
                            <span>{{ formatTime(msg.created_at) }}</span>
                            <CheckCheck v-if="msg.sender_type === 'agent'" class="w-3 h-3 text-blue-400" />
                        </div>
                    </div>
                </div>

                <!-- Message Composer Area (Firmly anchored at bottom, always in view) -->
                <div
                    v-if="activeConv"
                    class="shrink-0 p-4 border-t border-slate-800 bg-slate-900/90 backdrop-blur space-y-3"
                >
                    <!-- Quick Replies Pill Bar -->
                    <div
                        v-if="quickReplies && quickReplies.length > 0"
                        class="flex items-center gap-2 overflow-x-auto pb-1"
                    >
                        <span class="text-[11px] font-bold text-slate-400 shrink-0 flex items-center gap-1">
                            <Zap class="w-3 h-3 text-amber-400" />
                            <span>ردود سريعة:</span>
                        </span>
                        <button
                            v-for="qr in quickReplies"
                            :key="qr.id"
                            @click="insertQuickReply(qr.body)"
                            class="cursor-pointer text-xs px-3 py-1.5 rounded-full bg-slate-800 hover:bg-slate-750 text-slate-300 hover:text-white border border-slate-700/80 whitespace-nowrap transition"
                        >
                            {{ qr.title }}
                        </button>
                    </div>

                    <!-- Textarea & Send Button -->
                    <form @submit.prevent="sendMessage" class="flex items-end gap-3">
                        <textarea
                            v-model="messageText"
                            @keydown.enter.exact.prevent="sendMessage"
                            rows="2"
                            placeholder="اكتب ردك هنا... (اضغط Enter للإرسال، Shift+Enter لسطر جديد)"
                            class="flex-1 rounded-xl bg-slate-950 border border-slate-800 px-4 py-3 text-xs text-white placeholder-slate-500 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none transition"
                        ></textarea>
                        <button
                            type="submit"
                            :disabled="!messageText.trim() || isSending"
                            class="cursor-pointer h-12 px-6 rounded-xl bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-bold text-xs flex items-center justify-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shrink-0"
                        >
                            <span>إرسال</span>
                            <Send class="w-3.5 h-3.5" />
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
