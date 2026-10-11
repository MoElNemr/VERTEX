<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
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
    Camera,
    ChevronRight,
    SlidersHorizontal,
    ArrowLeftRight
} from 'lucide-vue-next';

const props = defineProps({
    business: Object,
    otherBusinesses: {
        type: Array,
        default: () => [],
    },
    conversations: Array,
    quickReplies: Array,
    activeConversationId: [String, Number],
    teamMembers: {
        type: Array,
        default: () => [],
    },
});

// State
const conversationsList = ref([...(props.conversations || [])]);
const activeConv = ref(null);
const isTransferModalOpen = ref(false);
const selectedTargetBusinessId = ref('');
const isTransferring = ref(false);
const messages = ref([]);
const messageText = ref('');
const searchQuery = ref('');
const selectedPlatform = ref('all'); // all, whatsapp, facebook, instagram, telegram
const selectedStatus = ref('all'); // all, open, closed
const isLoadingMessages = ref(false);
const isSending = ref(false);
const messagesContainer = ref(null);
const isFilterDrawerOpen = ref(false);

// Synchronize state when switching active business or when conversations update
watch(() => props.business?.id, (newBusinessId, oldBusinessId) => {
    if (newBusinessId !== oldBusinessId) {
        unsubscribeEcho(oldBusinessId);
        subscribeEcho(newBusinessId);
        conversationsList.value = [...(props.conversations || [])];
        searchQuery.value = '';
        selectedPlatform.value = 'all';
        selectedStatus.value = 'all';

        if (window.innerWidth >= 768 && conversationsList.value.length > 0) {
            selectConversation(conversationsList.value[0]);
        } else {
            activeConv.value = null;
            messages.value = [];
        }
    }
});

watch(() => props.conversations, (newConversations) => {
    conversationsList.value = [...(newConversations || [])];
    if (activeConv.value) {
        const matching = conversationsList.value.find(c => c.id === activeConv.value.id);
        if (matching) {
            activeConv.value = matching;
        } else {
            if (window.innerWidth >= 768 && conversationsList.value.length > 0) {
                selectConversation(conversationsList.value[0]);
            } else {
                activeConv.value = null;
                messages.value = [];
            }
        }
    } else if (window.innerWidth >= 768 && conversationsList.value.length > 0) {
        selectConversation(conversationsList.value[0]);
    }
}, { deep: true });

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

// Return to conversations list on mobile
const backToList = () => {
    activeConv.value = null;
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

// Assign conversation to agent
const isAssigning = ref(false);
const assignAgent = async (agentId) => {
    if (!activeConv.value) return;
    try {
        isAssigning.value = true;
        const res = await axios.patch(route('inbox.assign', activeConv.value.id), {
            assigned_to: agentId ? parseInt(agentId) : null,
        });
        if (res.data?.conversation) {
            activeConv.value.assigned_to = res.data.conversation.assigned_to;
            activeConv.value.assigned_agent = res.data.conversation.assigned_agent;
            const idx = conversationsList.value.findIndex(c => c.id === activeConv.value.id);
            if (idx !== -1) {
                conversationsList.value[idx].assigned_to = res.data.conversation.assigned_to;
                conversationsList.value[idx].assigned_agent = res.data.conversation.assigned_agent;
            }
        }
    } catch (e) {
        console.error('Failed to assign agent', e);
    } finally {
        isAssigning.value = false;
    }
};

const openTransferModal = () => {
    if (props.otherBusinesses && props.otherBusinesses.length > 0) {
        selectedTargetBusinessId.value = props.otherBusinesses[0].id;
        isTransferModalOpen.value = true;
    }
};

const submitTransfer = async () => {
    if (!activeConv.value || !selectedTargetBusinessId.value) return;

    isTransferring.value = true;
    try {
        const res = await axios.post(route('inbox.transfer', activeConv.value.id), {
            target_business_id: selectedTargetBusinessId.value,
        });

        if (res.data?.status === 'success') {
            const convId = activeConv.value.id;
            conversationsList.value = conversationsList.value.filter(c => c.id !== convId);
            activeConv.value = null;
            messages.value = [];
            isTransferModalOpen.value = false;
        }
    } catch (e) {
        console.error('Failed to transfer conversation', e);
        alert(e.response?.data?.message || 'حدث خطأ أثناء تحويل المحادثة.');
    } finally {
        isTransferring.value = false;
    }
};

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

let pollingInterval = null;

const subscribeEcho = (businessId) => {
    if (!window.Echo || !businessId) return;

    window.Echo.private(`business.${businessId}`)
        .listen('.message.sent', (data) => {
            // if message is for active conversation, append it
            if (activeConv.value && activeConv.value.id === data.conversation_id) {
                // avoid duplicates
                if (!messages.value.find(m => m.id === data.message.id)) {
                    messages.value.push(data.message);
                    scrollToBottom();
                }
            }
            // update conversation in list (unread count, last message preview, last_message_at)
            const idx = conversationsList.value.findIndex(c => c.id === data.conversation_id);
            if (idx !== -1) {
                const conv = { ...conversationsList.value[idx] };
                conv.last_message_at = data.conversation.last_message_at;
                conv.unread_count = activeConv.value?.id === data.conversation_id ? 0 : (conv.unread_count || 0) + (data.message.sender_type === 'contact' ? 1 : 0);
                if (data.message.sender_type === 'contact') {
                    conv.latest_message = { body: data.message.body };
                }
                conversationsList.value.splice(idx, 1, conv);
                // sort by last_message_at
                conversationsList.value.sort((a, b) => new Date(b.last_message_at) - new Date(a.last_message_at));
            } else if (data.message.sender_type === 'contact') {
                const newConv = {
                    ...data.conversation,
                    latest_message: { body: data.message.body },
                };
                conversationsList.value.unshift(newConv);
            }
            // browser notification for inbound
            if (data.message.sender_type === 'contact' && document.hidden) {
                if (window.Notification && Notification.permission === 'granted') {
                    new Notification('رسالة جديدة', { body: data.message.body });
                }
            }
        })
        .listen('.conversation.updated', (data) => {
            const idx = conversationsList.value.findIndex(c => c.id === data.conversation.id);
            if (idx !== -1) {
                conversationsList.value.splice(idx, 1, { ...conversationsList.value[idx], ...data.conversation });
            }
            if (activeConv.value?.id === data.conversation.id) {
                activeConv.value = { ...activeConv.value, ...data.conversation };
            }
        });
};

const unsubscribeEcho = (businessId) => {
    if (window.Echo && businessId) {
        window.Echo.leave(`business.${businessId}`);
    }
};

// On Mounted
onMounted(() => {
    if (props.activeConversationId) {
        const found = conversationsList.value.find(c => c.id == props.activeConversationId);
        if (found) selectConversation(found);
    } else if (window.innerWidth >= 768 && conversationsList.value.length > 0) {
        // On desktop/tablet auto-select first conversation, but on mobile stay on list
        selectConversation(conversationsList.value[0]);
    }

    if (props.business?.id) {
        subscribeEcho(props.business.id);
    }

    // Request notification permission
    if (typeof window !== 'undefined' && 'Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Polling fallback every 30 seconds when Echo disconnects
    pollingInterval = setInterval(async () => {
        if (window.Echo?.connector?.socket?.readyState !== 1) {
            try {
                await axios.get(route('inbox.index'));
            } catch (e) { /* silent */ }
        }
    }, 30000);
});

onUnmounted(() => {
    if (props.business?.id) {
        unsubscribeEcho(props.business.id);
    }
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<template>
    <Head :title="`صندوق الوارد الموحد - ${business.name}`" />

    <AuthenticatedLayout :fullHeight="true">
        <!-- Main Inbox Container (strictly fills remaining viewport height) -->
        <div class="h-full flex flex-1 overflow-hidden bg-slate-950 text-slate-100 relative">

            <!-- ============================================================== -->
            <!-- 1. DESKTOP SIDEBAR: Platform & Status Filters (Hidden on Mobile/Tablet) -->
            <!-- ============================================================== -->
            <aside class="hidden lg:flex w-64 border-l border-slate-800 bg-slate-900/90 flex-col justify-between shrink-0 p-4">
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

                    <!-- Status Filter -->
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

                <!-- Channels Setting Link -->
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

            <!-- ============================================================== -->
            <!-- 2. MOBILE / TABLET FILTER SLIDE-OVER DRAWER -->
            <!-- ============================================================== -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isFilterDrawerOpen"
                    @click="isFilterDrawerOpen = false"
                    class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 lg:hidden"
                ></div>
            </transition>

            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <div
                    v-if="isFilterDrawerOpen"
                    class="fixed inset-y-0 right-0 w-80 max-w-[85vw] bg-slate-900 border-l border-slate-800 p-5 z-50 flex flex-col justify-between shadow-2xl lg:hidden"
                >
                    <div class="space-y-6">
                        <!-- Drawer Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                            <div>
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">تصفية صندوق الوارد</span>
                                <h3 class="text-sm font-bold text-white">{{ business.name }}</h3>
                            </div>
                            <button
                                @click="isFilterDrawerOpen = false"
                                class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Platforms -->
                        <div>
                            <span class="text-xs font-bold text-slate-400 block mb-2.5">المنصات</span>
                            <div class="space-y-1.5">
                                <button
                                    v-for="p in platformList"
                                    :key="p.id"
                                    @click="selectedPlatform = p.id; isFilterDrawerOpen = false"
                                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition cursor-pointer"
                                    :class="selectedPlatform === p.id ? 'bg-blue-600 text-white font-bold' : 'text-slate-400 hover:bg-slate-800'"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <component :is="p.icon" class="w-4 h-4" :class="selectedPlatform === p.id ? 'text-white' : p.color" />
                                        <span>{{ p.name }}</span>
                                    </div>
                                    <span
                                        class="text-[11px] font-mono px-2 py-0.5 rounded-full font-bold"
                                        :class="selectedPlatform === p.id ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400'"
                                    >
                                        {{ getPlatformCount(p.id) }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <span class="text-xs font-bold text-slate-400 block mb-2.5">الحالة</span>
                            <div class="grid grid-cols-3 gap-1 p-1 bg-slate-950 rounded-xl border border-slate-800">
                                <button
                                    v-for="s in statusOptions"
                                    :key="s.id"
                                    @click="selectedStatus = s.id; isFilterDrawerOpen = false"
                                    class="py-2 text-center text-xs font-semibold rounded-lg transition cursor-pointer"
                                    :class="selectedStatus === s.id ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-400'"
                                >
                                    {{ s.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom channels link -->
                    <div class="pt-4 border-t border-slate-800">
                        <Link
                            :href="route('channels.index', business.id)"
                            class="w-full flex items-center justify-center gap-2 py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-750 text-xs font-bold text-slate-200 transition"
                        >
                            <Settings class="w-4 h-4 text-slate-400" />
                            <span>إعدادات القنوات</span>
                        </Link>
                    </div>
                </div>
            </transition>

            <!-- ============================================================== -->
            <!-- 3. CONVERSATIONS LIST COLUMN -->
            <!-- Desktop: w-80/w-96 | Mobile: Full width if no activeConv, hidden if activeConv -->
            <!-- ============================================================== -->
            <div
                class="flex-col border-l border-slate-800 bg-slate-900/40 shrink-0"
                :class="[
                    activeConv ? 'hidden md:flex md:w-80 lg:w-88 xl:w-96' : 'flex w-full md:w-80 lg:w-88 xl:w-96'
                ]"
            >
                <!-- Search & Filters Header -->
                <div class="p-3.5 sm:p-4 border-b border-slate-800 shrink-0 space-y-2.5">
                    <div class="flex items-center gap-2">
                        <!-- Search Box -->
                        <div class="relative flex-1">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="بحث بالاسم أو نص الرسالة..."
                                class="w-full pe-4 ps-9 py-2.5 rounded-xl text-xs bg-slate-950 border border-slate-800 text-slate-200 placeholder-slate-500 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
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

                        <!-- Mobile Filter Drawer Toggle Button -->
                        <button
                            @click="isFilterDrawerOpen = true"
                            class="lg:hidden p-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700/80 transition cursor-pointer relative"
                            title="تصفية المحادثات"
                        >
                            <SlidersHorizontal class="w-4 h-4" />
                            <span
                                v-if="selectedPlatform !== 'all' || selectedStatus !== 'all'"
                                class="w-2 h-2 rounded-full bg-blue-500 absolute -top-0.5 -end-0.5 ring-2 ring-slate-900"
                            ></span>
                        </button>
                    </div>

                    <!-- Quick Horizontal Platform Scroll Pills (Visible on all screens for quick filtering) -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 pt-0.5 scrollbar-none">
                        <button
                            v-for="p in platformList"
                            :key="p.id"
                            @click="selectedPlatform = p.id"
                            class="cursor-pointer shrink-0 px-2.5 py-1 rounded-lg text-[11px] font-semibold transition flex items-center gap-1.5 whitespace-nowrap"
                            :class="selectedPlatform === p.id
                                ? 'bg-blue-600 text-white font-bold shadow-sm'
                                : 'bg-slate-800/80 text-slate-400 hover:bg-slate-800 hover:text-slate-200 border border-slate-800'"
                        >
                            <span>{{ p.name }}</span>
                            <span class="text-[10px] font-mono opacity-80">({{ getPlatformCount(p.id) }})</span>
                        </button>
                    </div>
                </div>

                <!-- Scrollable Conversations List -->
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
                        class="p-3.5 rounded-xl transition cursor-pointer flex items-start gap-3 select-none active:scale-[0.99]"
                        :class="[
                            activeConv?.id === conv.id
                                ? 'bg-slate-800/95 border border-blue-500/50 shadow-sm'
                                : 'hover:bg-slate-800/40 border border-transparent'
                        ]"
                    >
                        <!-- Avatar with Platform Badge -->
                        <div class="relative shrink-0">
                            <div
                                class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-sm"
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

                        <!-- Content Layout: 2 distinct rows without collisions -->
                        <div class="flex-1 min-w-0">
                            <!-- Row 1: Name + Time -->
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <h4 class="text-xs font-bold text-white truncate">
                                        {{ conv.contact?.name || conv.contact?.phone || 'عميل غير مسجل' }}
                                    </h4>
                                    <span v-if="conv.assigned_agent" class="text-[9px] text-purple-300 bg-purple-950/80 border border-purple-800/60 px-1.5 py-0.2 rounded shrink-0">
                                        {{ conv.assigned_agent.name }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-400 font-mono shrink-0">
                                    {{ formatTime(conv.last_message_at) }}
                                </span>
                            </div>

                            <!-- Row 2: Message preview + Unread Badge -->
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

            <!-- ============================================================== -->
            <!-- 4. ACTIVE CHAT & MESSAGES COLUMN -->
            <!-- Desktop: flex-1 | Mobile: Full width if activeConv, hidden if !activeConv -->
            <!-- ============================================================== -->
            <div
                class="flex-col min-w-0 bg-slate-950"
                :class="[
                    activeConv ? 'flex flex-1 w-full' : 'hidden md:flex md:flex-1 md:items-center md:justify-center'
                ]"
            >
                <!-- Desktop Empty State when no conversation selected -->
                <div v-if="!activeConv" class="text-center p-8 text-slate-500 text-xs">
                    <MessageSquare class="w-12 h-12 mx-auto mb-3 text-slate-700 opacity-60" />
                    <p class="font-medium text-slate-400 mb-1">لم يتم اختيار محادثة</p>
                    <p>اختر محادثة من القائمة لعرض الرسائل والتواصل مع العميل</p>
                </div>

                <!-- Active Chat Flow -->
                <template v-else>
                    <!-- Chat Header -->
                    <div class="h-16 px-4 sm:px-6 border-b border-slate-800 flex items-center justify-between bg-slate-900/80 backdrop-blur shrink-0">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                            <!-- Mobile Back Button to return to list -->
                            <button
                                @click="backToList"
                                class="md:hidden p-2 -ms-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer shrink-0"
                                title="الرجوع لقائمة المحادثات"
                            >
                                <ChevronRight class="w-5 h-5" />
                            </button>

                            <!-- Avatar -->
                            <div class="relative shrink-0">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-200 text-xs sm:text-sm">
                                    {{ activeConv.contact?.name ? activeConv.contact.name.charAt(0).toUpperCase() : 'ع' }}
                                </div>
                                <div
                                    class="absolute -bottom-0.5 -start-0.5 w-3.5 h-3.5 rounded-full flex items-center justify-center text-[8px] font-bold"
                                    :class="getPlatformBadgeColor(activeConv.platform)"
                                >
                                    {{ activeConv.platform.charAt(0).toUpperCase() }}
                                </div>
                            </div>

                            <!-- Name & Meta -->
                            <div class="min-w-0">
                                <h3 class="text-xs sm:text-sm font-bold text-white truncate">
                                    {{ activeConv.contact?.name || activeConv.contact?.phone || 'محادثة نشطة' }}
                                </h3>
                                <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] text-slate-400 truncate">
                                    <span>عبر {{ getPlatformName(activeConv.platform) }}</span>
                                    <span>•</span>
                                    <span class="font-mono text-slate-500 truncate max-w-[100px] sm:max-w-none">{{ activeConv.contact?.platform_sender_id }}</span>
                                    <span
                                        class="hidden sm:inline-block px-1.5 py-0.2 rounded text-[10px] font-semibold"
                                        :class="activeConv.status === 'open' ? 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/80' : 'bg-slate-800 text-slate-400'"
                                    >
                                        {{ activeConv.status === 'open' ? 'مفتوحة' : 'مغلقة' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions (Assign Agent + Status switch) -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Assign Agent Selector -->
                            <div v-if="$page.props.auth.can_view_all_conversations && teamMembers.length > 0" class="flex items-center gap-1.5">
                                <span class="hidden lg:inline text-[11px] text-slate-400 font-semibold">إسناد:</span>
                                <select
                                    :value="activeConv.assigned_to || ''"
                                    @change="assignAgent($event.target.value)"
                                    :disabled="isAssigning"
                                    class="bg-slate-950/90 border border-slate-750 text-slate-200 text-xs rounded-xl px-2.5 py-1.5 focus:border-blue-500 focus:outline-none cursor-pointer max-w-[130px] truncate"
                                >
                                    <option value="">غير مسندة</option>
                                    <option v-for="tm in teamMembers" :key="tm.id" :value="tm.id">
                                        {{ tm.name }} ({{ tm.role === 'admin' ? 'مسؤول' : tm.role === 'supervisor' ? 'مشرف' : 'وكيل' }})
                                    </option>
                                </select>
                            </div>
                            <div v-else-if="activeConv.assigned_agent" class="text-[11px] px-2.5 py-1 rounded-xl bg-purple-950/60 text-purple-300 border border-purple-800/60">
                                <span>المسند: {{ activeConv.assigned_agent.name }}</span>
                            </div>

                            <!-- Transfer button -->
                            <button
                                v-if="otherBusinesses && otherBusinesses.length > 0"
                                @click="openTransferModal"
                                class="cursor-pointer px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-semibold bg-indigo-950/80 hover:bg-indigo-900 text-indigo-300 border border-indigo-800 transition flex items-center gap-1.5"
                                title="تحويل المحادثة لمتجر آخر"
                            >
                                <ArrowLeftRight class="w-3.5 h-3.5 text-indigo-400" />
                                <span class="hidden sm:inline">تحويل المتجر</span>
                            </button>

                            <button
                                v-if="activeConv.status === 'open'"
                                @click="updateStatus('closed')"
                                class="cursor-pointer px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700/80 transition flex items-center gap-1.5"
                            >
                                <Check class="w-3.5 h-3.5 text-slate-400" />
                                <span class="hidden sm:inline">إغلاق المحادثة</span>
                                <span class="sm:hidden">إغلاق</span>
                            </button>
                            <button
                                v-else
                                @click="updateStatus('open')"
                                class="cursor-pointer px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 border border-emerald-800 transition flex items-center gap-1.5"
                            >
                                <RotateCcw class="w-3.5 h-3.5" />
                                <span class="hidden sm:inline">إعادة فتح</span>
                                <span class="sm:hidden">فتح</span>
                            </button>
                        </div>
                    </div>

                    <!-- Messages Scrollable Area -->
                    <div
                        ref="messagesContainer"
                        class="flex-1 min-h-0 overflow-y-auto p-4 sm:p-6 space-y-4"
                    >
                        <div v-if="isLoadingMessages" class="h-full flex items-center justify-center text-slate-500 text-xs">
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
                                class="max-w-[85%] sm:max-w-lg rounded-2xl px-4 py-2.5 sm:py-3 text-xs leading-relaxed shadow-sm"
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

                    <!-- Message Composer Area (Anchored to bottom, always visible) -->
                    <div class="shrink-0 p-3 sm:p-4 border-t border-slate-800 bg-slate-900/95 backdrop-blur space-y-2.5">
                        <!-- Quick Replies Pill Bar -->
                        <div
                            v-if="quickReplies && quickReplies.length > 0"
                            class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none"
                        >
                            <span class="text-[11px] font-bold text-slate-400 shrink-0 flex items-center gap-1">
                                <Zap class="w-3 h-3 text-amber-400" />
                                <span class="hidden sm:inline">ردود سريعة:</span>
                            </span>
                            <button
                                v-for="qr in quickReplies"
                                :key="qr.id"
                                @click="insertQuickReply(qr.body)"
                                class="cursor-pointer text-[11px] px-2.5 py-1 rounded-full bg-slate-800 hover:bg-slate-750 text-slate-300 hover:text-white border border-slate-700/80 whitespace-nowrap transition"
                            >
                                {{ qr.title }}
                            </button>
                        </div>

                        <!-- Textarea & Send Button -->
                        <form @submit.prevent="sendMessage" class="flex items-end gap-2 sm:gap-3">
                            <textarea
                                v-model="messageText"
                                @keydown.enter.exact.prevent="sendMessage"
                                rows="2"
                                placeholder="اكتب ردك هنا... (اضغط Enter للإرسال)"
                                class="flex-1 rounded-xl bg-slate-950 border border-slate-800 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none transition"
                            ></textarea>
                            <button
                                type="submit"
                                :disabled="!messageText.trim() || isSending"
                                class="cursor-pointer h-10 sm:h-12 px-4 sm:px-6 rounded-xl bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 sm:gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shrink-0"
                            >
                                <span class="hidden sm:inline">إرسال</span>
                                <Send class="w-3.5 h-3.5" />
                            </button>
                        </form>
                    </div>
                </template>
            </div>
        </div>

        <!-- Transfer Conversation Modal -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isTransferModalOpen"
                class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                @click.self="isTransferModalOpen = false"
            >
                <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div class="flex items-center gap-2">
                            <ArrowLeftRight class="w-5 h-5 text-indigo-400" />
                            <h3 class="text-sm font-bold text-white">تحويل المحادثة إلى متجر آخر</h3>
                        </div>
                        <button
                            @click="isTransferModalOpen = false"
                            class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <p class="text-xs text-slate-400 leading-relaxed">
                        اختر المتجر الذي ترغب في تحويل هذه المحادثة إليه. ستنتقل المحادثة بجميع رسائلها وسجلاتها إلى المتجر المحدد فوراً:
                    </p>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-300 block">المتجر الوجهة:</label>
                        <select
                            v-model="selectedTargetBusinessId"
                            class="w-full bg-slate-950 border border-slate-750 text-slate-100 text-xs rounded-xl p-3 focus:border-indigo-500 focus:outline-none cursor-pointer"
                        >
                            <option v-for="ob in otherBusinesses" :key="ob.id" :value="ob.id">
                                {{ ob.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="isTransferModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition"
                        >
                            إلغاء
                        </button>
                        <button
                            type="button"
                            @click="submitTransfer"
                            :disabled="isTransferring"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white transition flex items-center gap-2 disabled:opacity-50"
                        >
                            <span>{{ isTransferring ? 'جاري التحويل...' : 'تأكيد التحويل' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>
