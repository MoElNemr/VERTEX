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
    Check
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
const selectedStatus = ref('all'); // all, open, closed, pending
const isLoadingMessages = ref(false);
const isSending = ref(false);
const messagesContainer = ref(null);

// Platform Icons & Badges
const getPlatformName = (platform) => {
    switch (platform) {
        case 'whatsapp': return 'واتساب';
        case 'facebook': return 'فيسبوك';
        case 'instagram': return 'إنستجرام';
        case 'telegram': return 'تيليجرام';
        default: return platform;
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

    <AuthenticatedLayout>
        <!-- Main Inbox Wrapper -->
        <div class="h-[calc(100vh-4rem)] flex flex-col bg-slate-950 text-slate-100 overflow-hidden">

            <div class="flex-1 flex overflow-hidden">
                <!-- 1. Left Sidebar: Platform & Status Filters -->
                <aside class="w-64 border-l border-slate-800 bg-slate-900/90 flex flex-col justify-between shrink-0 p-4">
                    <div class="space-y-6">
                        <!-- Business Title -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">صندوق الوارد</span>
                            <h2 class="text-lg font-bold text-white truncate">{{ business.name }}</h2>
                        </div>

                        <!-- Platforms Filter -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-2.5">المنصات</span>
                            <div class="space-y-1">
                                <button
                                    @click="selectedPlatform = 'all'"
                                    class="cursor-pointer w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition"
                                    :class="selectedPlatform === 'all' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'"
                                >
                                    <span>جميع المنصات</span>
                                    <span class="text-[10px] font-mono opacity-80">{{ conversationsList.length }}</span>
                                </button>
                                <button
                                    @click="selectedPlatform = 'whatsapp'"
                                    class="cursor-pointer w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition"
                                    :class="selectedPlatform === 'whatsapp' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'"
                                >
                                    <span>واتساب</span>
                                    <span class="text-[10px] font-mono opacity-80">{{ conversationsList.filter(c => c.platform === 'whatsapp').length }}</span>
                                </button>
                                <button
                                    @click="selectedPlatform = 'facebook'"
                                    class="cursor-pointer w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition"
                                    :class="selectedPlatform === 'facebook' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'"
                                >
                                    <span>فيسبوك</span>
                                    <span class="text-[10px] font-mono opacity-80">{{ conversationsList.filter(c => c.platform === 'facebook').length }}</span>
                                </button>
                                <button
                                    @click="selectedPlatform = 'instagram'"
                                    class="cursor-pointer w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition"
                                    :class="selectedPlatform === 'instagram' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'"
                                >
                                    <span>إنستجرام</span>
                                    <span class="text-[10px] font-mono opacity-80">{{ conversationsList.filter(c => c.platform === 'instagram').length }}</span>
                                </button>
                                <button
                                    @click="selectedPlatform = 'telegram'"
                                    class="cursor-pointer w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition"
                                    :class="selectedPlatform === 'telegram' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'"
                                >
                                    <span>تيليجرام</span>
                                    <span class="text-[10px] font-mono opacity-80">{{ conversationsList.filter(c => c.platform === 'telegram').length }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-2.5">الحالة</span>
                            <div class="space-y-1">
                                <button
                                    @click="selectedStatus = 'all'"
                                    class="cursor-pointer w-full flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition"
                                    :class="selectedStatus === 'all' ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
                                >
                                    <span>الكل</span>
                                </button>
                                <button
                                    @click="selectedStatus = 'open'"
                                    class="cursor-pointer w-full flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition"
                                    :class="selectedStatus === 'open' ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
                                >
                                    <span>مفتوحة</span>
                                </button>
                                <button
                                    @click="selectedStatus = 'closed'"
                                    class="cursor-pointer w-full flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition"
                                    :class="selectedStatus === 'closed' ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
                                >
                                    <span>مغلقة</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Channels Setting Link -->
                    <div class="pt-4 border-t border-slate-800">
                        <Link
                            :href="route('channels.index', business.id)"
                            class="cursor-pointer w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 transition"
                        >
                            <span>إعدادات القنوات</span>
                        </Link>
                    </div>
                </aside>

                <!-- 2. Middle Column: Conversations List -->
                <div class="w-80 md:w-96 border-l border-slate-800 bg-slate-900/40 flex flex-col shrink-0">
                    <!-- Search Header -->
                    <div class="p-4 border-b border-slate-800">
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="بحث بالاسم أو الرسالة..."
                                class="w-full pl-3 pr-9 py-2 rounded-xl text-xs bg-slate-950 border border-slate-800 text-slate-200 placeholder-slate-500 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
                            />
                            <Search class="w-4 h-4 text-slate-500 absolute right-3 top-2.5 pointer-events-none" />
                        </div>
                    </div>

                    <!-- Conversations Scrollable Area -->
                    <div class="flex-1 overflow-y-auto divide-y divide-slate-800/60">
                        <div
                            v-if="filteredConversations.length === 0"
                            class="p-8 text-center text-slate-500 text-xs"
                        >
                            لا توجد محادثات مطابقة.
                        </div>

                        <div
                            v-for="conv in filteredConversations"
                            :key="conv.id"
                            @click="selectConversation(conv)"
                            class="p-4 transition cursor-pointer flex items-start gap-3 select-none"
                            :class="[
                                activeConv?.id === conv.id
                                    ? 'bg-slate-800/90 border-r-2 border-blue-500'
                                    : 'hover:bg-slate-800/40'
                            ]"
                        >
                            <!-- Avatar / Platform Glyph -->
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-200 text-sm">
                                    {{ conv.contact?.name ? conv.contact.name.charAt(0).toUpperCase() : 'ع' }}
                                </div>
                                <span class="absolute -bottom-0.5 -left-0.5 px-1 py-0.2 bg-slate-950 border border-slate-700 rounded-full text-[9px] font-bold text-slate-400">
                                    {{ conv.platform.charAt(0).toUpperCase() }}
                                </span>
                            </div>

                            <!-- Conv Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <h4 class="text-xs font-bold text-white truncate">
                                        {{ conv.contact?.name || conv.contact?.phone || 'عميل غير مسجل' }}
                                    </h4>
                                    <span class="text-[10px] text-slate-500 shrink-0">
                                        {{ conv.last_message_at ? new Date(conv.last_message_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) : '' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 truncate">
                                    {{ conv.latest_message?.body || 'لا توجد رسائل سابقة' }}
                                </p>
                            </div>

                            <!-- Unread Badge -->
                            <span
                                v-if="conv.unread_count > 0"
                                class="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] font-bold flex items-center justify-center shrink-0"
                            >
                                {{ conv.unread_count }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. Right Column: Chat & Message Flow -->
                <div class="flex-1 flex flex-col bg-slate-950">
                    <!-- Chat Header -->
                    <div
                        v-if="activeConv"
                        class="h-16 px-6 border-b border-slate-800 flex items-center justify-between bg-slate-900/60"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-200 text-sm">
                                {{ activeConv.contact?.name ? activeConv.contact.name.charAt(0).toUpperCase() : 'ع' }}
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">
                                    {{ activeConv.contact?.name || activeConv.contact?.phone || 'محادثة نشطة' }}
                                </h3>
                                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                    <span>عبر {{ getPlatformName(activeConv.platform) }}</span>
                                    <span>•</span>
                                    <span class="font-mono text-slate-500">{{ activeConv.contact?.platform_sender_id }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions (Status switch) -->
                        <div class="flex items-center gap-2">
                            <button
                                v-if="activeConv.status === 'open'"
                                @click="updateStatus('closed')"
                                class="cursor-pointer px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition"
                            >
                                إغلاق المحادثة
                            </button>
                            <button
                                v-else
                                @click="updateStatus('open')"
                                class="cursor-pointer px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-950 text-emerald-300 border border-emerald-800 transition"
                            >
                                إعادة فتح
                            </button>
                        </div>
                    </div>

                    <!-- Messages Container -->
                    <div
                        ref="messagesContainer"
                        class="flex-1 overflow-y-auto p-6 space-y-4"
                    >
                        <div v-if="!activeConv" class="h-full flex items-center justify-center text-slate-500 text-sm">
                            اختر محادثة من القائمة لبدء الرد
                        </div>

                        <div v-else-if="isLoadingMessages" class="h-full flex items-center justify-center text-slate-500 text-sm">
                            جاري تحميل الرسائل...
                        </div>

                        <div v-else-if="messages.length === 0" class="h-full flex items-center justify-center text-slate-500 text-sm">
                            لا توجد رسائل حتى الآن. أرسل أول رسالة للعميل.
                        </div>

                        <!-- Message Bubbles -->
                        <div
                            v-for="msg in messages"
                            :key="msg.id"
                            class="flex flex-col"
                            :class="msg.sender_type === 'agent' ? 'items-end' : 'items-start'"
                        >
                            <div
                                class="max-w-md rounded-2xl px-4 py-3 text-xs leading-relaxed"
                                :class="[
                                    msg.sender_type === 'agent'
                                        ? 'bg-blue-600 text-white rounded-bl-sm'
                                        : 'bg-slate-800 text-slate-200 border border-slate-700/80 rounded-br-sm'
                                ]"
                            >
                                <p class="whitespace-pre-wrap">{{ msg.body }}</p>
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1 px-1">
                                {{ new Date(msg.created_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) }}
                            </span>
                        </div>
                    </div>

                    <!-- Message Composer Area -->
                    <div
                        v-if="activeConv"
                        class="p-4 border-t border-slate-800 bg-slate-900/60 space-y-3"
                    >
                        <!-- Quick Replies Pill Bar -->
                        <div
                            v-if="quickReplies && quickReplies.length > 0"
                            class="flex items-center gap-2 overflow-x-auto pb-1"
                        >
                            <span class="text-[11px] font-bold text-slate-500 shrink-0 flex items-center gap-1">
                                <Zap class="w-3 h-3 text-amber-400" />
                                <span>ردود سريعة:</span>
                            </span>
                            <button
                                v-for="qr in quickReplies"
                                :key="qr.id"
                                @click="insertQuickReply(qr.body)"
                                class="cursor-pointer text-xs px-2.5 py-1 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700/80 whitespace-nowrap transition"
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
                                placeholder="اكتب ردك هنا... (اضغط Enter للإرسال)"
                                class="flex-1 rounded-xl bg-slate-950 border border-slate-800 px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none transition"
                            ></textarea>
                            <button
                                type="submit"
                                :disabled="!messageText.trim() || isSending"
                                class="cursor-pointer h-10 px-5 rounded-xl bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-bold text-xs flex items-center justify-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                            >
                                <span>إرسال</span>
                                <Send class="w-3.5 h-3.5" />
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
