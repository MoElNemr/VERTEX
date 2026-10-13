<script setup>
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ShieldCheck,
    Users,
    Store,
    MessageSquare,
    Radio,
    Server,
    Activity,
    Cpu,
    Database,
    Zap,
    ExternalLink,
    RefreshCw,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Search,
    ChevronRight,
    Crown,
    LogIn,
    Send,
    Repeat,
    Layers,
    Clock,
    Flame,
    Plus,
    Pencil,
    Trash2,
    CreditCard,
    Check,
    X,
    Sparkles,
    Shield
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    metrics: {
        type: Object,
        required: true,
    },
    health: {
        type: Object,
        required: true,
    },
    businesses: {
        type: Object,
        required: true,
    },
    users: {
        type: Object,
        required: true,
    },
    plans: {
        type: Array,
        default: () => [],
    },
    allUsersSimple: {
        type: Array,
        default: () => [],
    },
    recentCampaigns: {
        type: Array,
        default: () => [],
    },
    systemSpecs: {
        type: Object,
        required: true,
    },
});

const activeTab = ref('overview'); // 'overview' | 'businesses' | 'users' | 'plans' | 'sessions' | 'campaigns'
const searchQuery = ref('');
const restartingSession = ref(null);
const actionMessage = ref(null);
const optimizingSystem = ref(false);

const filteredBusinesses = computed(() => {
    if (!searchQuery.value) return props.businesses.data;
    const q = searchQuery.value.toLowerCase();
    return props.businesses.data.filter(b =>
        b.name?.toLowerCase().includes(q) ||
        b.user?.name?.toLowerCase().includes(q) ||
        b.user?.email?.toLowerCase().includes(q)
    );
});

const filteredUsers = computed(() => {
    if (!searchQuery.value) return props.users.data;
    const q = searchQuery.value.toLowerCase();
    return props.users.data.filter(u =>
        u.name?.toLowerCase().includes(q) ||
        u.email?.toLowerCase().includes(q)
    );
});

const showNotice = (msg, type = 'success') => {
    actionMessage.value = { text: msg, type };
    setTimeout(() => {
        actionMessage.value = null;
    }, 4500);
};

// ==========================================
// User Management State & Actions
// ==========================================
const userModalOpen = ref(false);
const userModalMode = ref('create'); // 'create' | 'edit'
const editingUserId = ref(null);

const userForm = useForm({
    name: '',
    email: '',
    password: '',
    is_super_admin: false,
    plan_id: '',
});

const openCreateUserModal = () => {
    userModalMode.value = 'create';
    editingUserId.value = null;
    userForm.reset();
    userForm.clearErrors();
    userForm.is_super_admin = false;
    userForm.plan_id = props.plans[0]?.id || '';
    userModalOpen.value = true;
};

const openEditUserModal = (u) => {
    userModalMode.value = 'edit';
    editingUserId.value = u.id;
    userForm.clearErrors();
    userForm.name = u.name;
    userForm.email = u.email;
    userForm.password = '';
    userForm.is_super_admin = !!u.is_super_admin;
    userForm.plan_id = u.active_subscription?.plan_id || '';
    userModalOpen.value = true;
};

const submitUserForm = () => {
    if (userModalMode.value === 'create') {
        userForm.post(route('superadmin.users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                userModalOpen.value = false;
                userForm.reset();
                showNotice('تم إنشاء المستخدم بنجاح');
            },
        });
    } else {
        userForm.put(route('superadmin.users.update', editingUserId.value), {
            preserveScroll: true,
            onSuccess: () => {
                userModalOpen.value = false;
                userForm.reset();
                showNotice('تم تحديث بيانات المستخدم بنجاح');
            },
        });
    }
};

const deleteUser = (u) => {
    if (confirm(`هل أنت متأكد من حذف المستخدم "${u.name}" نهائياً من النظام؟`)) {
        router.delete(route('superadmin.users.destroy', u.id), {
            preserveScroll: true,
            onSuccess: () => showNotice('تم حذف المستخدم بنجاح'),
            onError: (err) => showNotice(err.error || 'فشل حذف المستخدم', 'error')
        });
    }
};

const toggleSuperAdmin = (userId) => {
    router.post(route('superadmin.users.toggle-admin', userId), {}, {
        preserveScroll: true,
        onSuccess: () => showNotice('تم تحديث صلاحيات المستخدم بنجاح')
    });
};

// User Plan Assignment Modal
const assignPlanModalOpen = ref(false);
const selectedUserForPlan = ref(null);
const assignPlanForm = useForm({
    plan_id: '',
    duration_months: 12,
});

const openAssignPlanModal = (u) => {
    selectedUserForPlan.value = u;
    assignPlanForm.reset();
    assignPlanForm.plan_id = u.active_subscription?.plan_id || props.plans[0]?.id || '';
    assignPlanForm.duration_months = 12;
    assignPlanModalOpen.value = true;
};

const submitAssignPlan = () => {
    if (!selectedUserForPlan.value) return;
    assignPlanForm.post(route('superadmin.users.assign-plan', selectedUserForPlan.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            assignPlanModalOpen.value = false;
            showNotice('تم تحديث وتفعيل اشتراك المستخدم بنجاح');
        }
    });
};

// ==========================================
// Business Management State & Actions
// ==========================================
const businessModalOpen = ref(false);
const businessModalMode = ref('create'); // 'create' | 'edit'
const editingBusinessId = ref(null);

const businessForm = useForm({
    name: '',
    user_id: '',
});

const openCreateBusinessModal = () => {
    businessModalMode.value = 'create';
    editingBusinessId.value = null;
    businessForm.reset();
    businessForm.clearErrors();
    businessForm.user_id = props.allUsersSimple[0]?.id || '';
    businessModalOpen.value = true;
};

const openEditBusinessModal = (b) => {
    businessModalMode.value = 'edit';
    editingBusinessId.value = b.id;
    businessForm.clearErrors();
    businessForm.name = b.name;
    businessForm.user_id = b.user_id;
    businessModalOpen.value = true;
};

const submitBusinessForm = () => {
    if (businessModalMode.value === 'create') {
        businessForm.post(route('superadmin.businesses.store'), {
            preserveScroll: true,
            onSuccess: () => {
                businessModalOpen.value = false;
                businessForm.reset();
                showNotice('تم إنشاء المتجر بنجاح');
            }
        });
    } else {
        businessForm.put(route('superadmin.businesses.update', editingBusinessId.value), {
            preserveScroll: true,
            onSuccess: () => {
                businessModalOpen.value = false;
                businessForm.reset();
                showNotice('تم تحديث بيانات المتجر بنجاح');
            }
        });
    }
};

const deleteBusiness = (b) => {
    if (confirm(`تحذير: هل أنت متأكد من حذف المتجر "${b.name}" وجميع رسائله وقنواته نهائياً؟`)) {
        router.delete(route('superadmin.businesses.destroy', b.id), {
            preserveScroll: true,
            onSuccess: () => showNotice('تم حذف المتجر وجميع بياناته بنجاح'),
        });
    }
};

const impersonateBusiness = (businessId) => {
    router.post(route('superadmin.businesses.impersonate', businessId));
};

// ==========================================
// Pricing Plans Management State & Actions
// ==========================================
const planModalOpen = ref(false);
const planModalMode = ref('create'); // 'create' | 'edit'
const editingPlanId = ref(null);

const planForm = useForm({
    name: '',
    type: 'standard',
    price_monthly: 29,
    price_yearly: 290,
    max_businesses: 1,
    is_active: true,
});

const openCreatePlanModal = () => {
    planModalMode.value = 'create';
    editingPlanId.value = null;
    planForm.reset();
    planForm.clearErrors();
    planForm.type = 'standard';
    planForm.is_active = true;
    planModalOpen.value = true;
};

const openEditPlanModal = (p) => {
    planModalMode.value = 'edit';
    editingPlanId.value = p.id;
    planForm.clearErrors();
    planForm.name = p.name;
    planForm.type = p.type;
    planForm.price_monthly = p.price_monthly;
    planForm.price_yearly = p.price_yearly;
    planForm.max_businesses = p.max_businesses ?? '';
    planForm.is_active = !!p.is_active;
    planModalOpen.value = true;
};

const submitPlanForm = () => {
    if (planModalMode.value === 'create') {
        planForm.post(route('superadmin.plans.store'), {
            preserveScroll: true,
            onSuccess: () => {
                planModalOpen.value = false;
                planForm.reset();
                showNotice('تمت إضافة خطة التسعير بنجاح');
            }
        });
    } else {
        planForm.put(route('superadmin.plans.update', editingPlanId.value), {
            preserveScroll: true,
            onSuccess: () => {
                planModalOpen.value = false;
                planForm.reset();
                showNotice('تم تحديث خطة التسعير بنجاح');
            }
        });
    }
};

const togglePlanActive = (plan) => {
    router.patch(route('superadmin.plans.toggle-active', plan.id), {}, {
        preserveScroll: true,
        onSuccess: () => showNotice('تم تحديث حالة الباقة بنجاح')
    });
};

const deletePlan = (plan) => {
    if (confirm(`هل أنت متأكد من حذف باقة "${plan.name}"؟`)) {
        router.delete(route('superadmin.plans.destroy', plan.id), {
            preserveScroll: true,
            onSuccess: () => showNotice('تم حذف باقة التسعير بنجاح'),
            onError: (err) => showNotice(err.error || 'لا يمكن حذف باقة تحتوي على مشتركين', 'error')
        });
    }
};

// ==========================================
// System Optimization & WhatsApp Actions
// ==========================================
const optimizeSystem = () => {
    optimizingSystem.value = true;
    router.post(route('superadmin.system.optimize'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showNotice('تم تنظيف الكاش وإعادة تهيئة النظام بنجاح');
            optimizingSystem.value = false;
        },
        onError: () => {
            showNotice('حدث خطأ أثناء تنظيف الكاش', 'error');
            optimizingSystem.value = false;
        }
    });
};

const restartSession = async (sessionId) => {
    restartingSession.value = sessionId;
    try {
        const response = await axios.post(route('superadmin.whatsapp.restart', sessionId));
        showNotice(response.data.message || 'تمت إعادة تشغيل الجلسة');
        router.reload({ only: ['health'] });
    } catch (err) {
        showNotice('فشل إعادة تشغيل الجلسة: ' + (err.response?.data?.error || err.message), 'error');
    } finally {
        restartingSession.value = null;
    }
};
</script>

<template>
    <Head title="لوحة التحكم العليا - Super Admin" />

    <AdminLayout>
        <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">

            <!-- Toast Notification -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="actionMessage"
                    class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-2xl text-xs font-semibold border backdrop-blur-md"
                    :class="actionMessage.type === 'error' ? 'bg-rose-950/90 text-rose-200 border-rose-800' : 'bg-emerald-950/90 text-emerald-200 border-emerald-800'"
                >
                    <CheckCircle2 v-if="actionMessage.type !== 'error'" class="w-4 h-4 text-emerald-400 shrink-0" />
                    <XCircle v-else class="w-4 h-4 text-rose-400 shrink-0" />
                    <span>{{ actionMessage.text }}</span>
                </div>
            </Transition>

            <!-- ========================================== -->
            <!-- 1. HEADER: Super Admin Command Center      -->
            <!-- ========================================== -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-900/90 to-purple-950/40 border border-slate-800 relative overflow-hidden shadow-xl">
                <div class="absolute -end-10 -top-10 w-48 h-48 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500/20 to-purple-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0 shadow-inner">
                        <Crown class="w-6 h-6 animate-pulse" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-extrabold tracking-tight text-white">مركز القيادة والتحكم (Super Admin)</h1>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                مالك المنصة
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            التحكم الشامل في المستخدمين، المتاجر، خطط الأسعار، وخوادم الاتصال
                        </p>
                    </div>
                </div>

                <!-- Actions & Live Pills -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="optimizeSystem"
                        :disabled="optimizingSystem"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-600/20 hover:bg-purple-600 border border-purple-500/30 text-purple-300 hover:text-white text-xs font-semibold transition disabled:opacity-50"
                        title="تنظيف الذاكرة المؤقتة وتسريع الخادم"
                    >
                        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': optimizingSystem }" />
                        <span>تنظيف الكاش والتهيئة</span>
                    </button>

                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-950/80 border border-slate-800 text-[11px] font-mono">
                        <span class="w-2 h-2 rounded-full" :class="health.node_baileys ? 'bg-emerald-400 animate-pulse' : 'bg-rose-500'"></span>
                        <span class="text-slate-400">Node Baileys:</span>
                        <span :class="health.node_baileys ? 'text-emerald-400 font-bold' : 'text-rose-400 font-bold'">
                            {{ health.node_baileys ? 'شغال' : 'متوقف' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-950/80 border border-slate-800 text-[11px] font-mono">
                        <span class="w-2 h-2 rounded-full" :class="health.reverb_websockets ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                        <span class="text-slate-400">Reverb:</span>
                        <span :class="health.reverb_websockets ? 'text-emerald-400 font-bold' : 'text-amber-400 font-bold'">
                            {{ health.reverb_websockets ? '8080' : 'غير متصل' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 2. BENTO GRID METRICS: Platform Highlights -->
            <!-- ========================================== -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4">
                <!-- Total Stores -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800/80 hover:border-slate-700 transition">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-xs font-medium">المتاجر</span>
                        <Store class="w-4 h-4 text-blue-400" />
                    </div>
                    <div class="text-2xl font-black text-white tracking-tight">{{ metrics.total_businesses }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">
                        <span>إجمالي المتاجر المسجلة</span>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800/80 hover:border-slate-700 transition">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-xs font-medium">المستخدمين</span>
                        <Users class="w-4 h-4 text-purple-400" />
                    </div>
                    <div class="text-2xl font-black text-white tracking-tight">{{ metrics.total_users }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">
                        <span>أصحاب المتاجر والمشرفين</span>
                    </div>
                </div>

                <!-- Connected Channels -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800/80 hover:border-slate-700 transition">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-xs font-medium">القنوات النشطة</span>
                        <Radio class="w-4 h-4 text-emerald-400" />
                    </div>
                    <div class="text-2xl font-black text-emerald-400 tracking-tight">{{ metrics.channels.total }}</div>
                    <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                        <span>WA: {{ metrics.channels.whatsapp }}</span>
                        <span>•</span>
                        <span>TG: {{ metrics.channels.telegram }}</span>
                        <span>•</span>
                        <span>FB: {{ metrics.channels.facebook }}</span>
                    </div>
                </div>

                <!-- Total Messages -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800/80 hover:border-slate-700 transition">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-xs font-medium">إجمالي الرسائل</span>
                        <MessageSquare class="w-4 h-4 text-sky-400" />
                    </div>
                    <div class="text-2xl font-black text-white tracking-tight">{{ metrics.total_messages.toLocaleString() }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">
                        <span>في {{ metrics.total_contacts }} جهة اتصال</span>
                    </div>
                </div>

                <!-- Total Plans -->
                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800/80 hover:border-slate-700 transition col-span-2 md:col-span-1">
                    <div class="flex items-center justify-between text-slate-400 mb-2">
                        <span class="text-xs font-medium">خطط التسعير</span>
                        <CreditCard class="w-4 h-4 text-amber-400" />
                    </div>
                    <div class="text-2xl font-black text-amber-400 tracking-tight">{{ plans.length }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">
                        <span>باقات الاشتراك الفعالة</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 3. NAVIGATION TABS                         -->
            <!-- ========================================== -->
            <div class="flex items-center gap-2 border-b border-slate-800 pb-2 overflow-x-auto">
                <button
                    type="button"
                    @click="activeTab = 'overview'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0"
                    :class="activeTab === 'overview' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'"
                >
                    نظرة شاملة وسيرفرات
                </button>
                <button
                    type="button"
                    @click="activeTab = 'businesses'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                    :class="activeTab === 'businesses' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'"
                >
                    <span>المتاجر والشركات</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-slate-800">{{ businesses.total || businesses.data.length }}</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'users'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                    :class="activeTab === 'users' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'"
                >
                    <span>المستخدمين وأصحاب المتاجر</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-slate-800">{{ users.total || users.data.length }}</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'plans'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                    :class="activeTab === 'plans' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'"
                >
                    <span>خطط الأسعار والاشتراكات</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-amber-500/20 text-amber-300">{{ plans.length }}</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'sessions'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0 flex items-center gap-1.5"
                    :class="activeTab === 'sessions' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'"
                >
                    <span>جلسات واتساب (Node)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-emerald-500/20 text-emerald-300">
                        {{ Object.keys(health.live_sessions || {}).length }}
                    </span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'campaigns'"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition shrink-0"
                    :class="activeTab === 'campaigns' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'"
                >
                    أحدث الحملات
                </button>
            </div>

            <!-- ========================================== -->
            <!-- 4. TAB CONTENT: OVERVIEW & SERVER HEALTH   -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
                <!-- System & Daemons Bento -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Node Baileys Daemon Status -->
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <Server class="w-5 h-5 text-emerald-400" />
                                <h3 class="font-bold text-sm text-white">محرك واتساب (Baileys Node)</h3>
                            </div>
                            <span
                                class="px-2 py-0.5 rounded text-[11px] font-mono font-bold"
                                :class="health.node_baileys ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'"
                            >
                                {{ health.node_baileys ? 'متصل (Online)' : 'متوقف (Offline)' }}
                            </span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-800/80">
                                <span class="text-slate-400">المنفذ المحلي:</span>
                                <span class="font-mono text-slate-200">http://127.0.0.1:3000</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-800/80">
                                <span class="text-slate-400">الجلسات النشطة في الذاكرة:</span>
                                <span class="font-mono text-emerald-400 font-bold">{{ Object.keys(health.live_sessions || {}).length }} جلسات</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-400">المكتبة:</span>
                                <span class="font-mono text-slate-300">@whiskeysockets/baileys v6.7.x</span>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button
                                type="button"
                                @click="activeTab = 'sessions'"
                                class="w-full flex items-center justify-center gap-1.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 text-xs font-semibold transition"
                            >
                                <span>فحص وإعادة تشغيل الجلسات</span>
                                <ChevronRight class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Queue Worker & Background Jobs -->
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <Cpu class="w-5 h-5 text-blue-400" />
                                <h3 class="font-bold text-sm text-white">طوابير المهام (Queue Worker)</h3>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                نشط (Active)
                            </span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-800/80">
                                <span class="text-slate-400">الدرايفر:</span>
                                <span class="font-mono text-slate-200">{{ systemSpecs.queue_driver }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-800/80">
                                <span class="text-slate-400">المهام المنتظرة (Pending):</span>
                                <span class="font-mono text-amber-400 font-bold">{{ health.pending_jobs }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-400">المهام المتعثرة (Failed):</span>
                                <span class="font-mono" :class="health.failed_jobs > 0 ? 'text-rose-400 font-bold' : 'text-slate-400'">
                                    {{ health.failed_jobs }}
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60 text-[11px] text-slate-400 flex items-center gap-2">
                            <Clock class="w-3.5 h-3.5 text-blue-400 shrink-0" />
                            <span>يعالج الحملات بـ Jitter و Anti-Ban تلقائياً</span>
                        </div>
                    </div>

                    <!-- Environment & Server Specs -->
                    <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <Database class="w-5 h-5 text-purple-400" />
                                <h3 class="font-bold text-sm text-white">البيئة وقاعدة البيانات</h3>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                Production
                            </span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-800/80">
                                <span class="text-slate-400">إصدار PHP:</span>
                                <span class="font-mono text-slate-200">{{ systemSpecs.php_version }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-800/80">
                                <span class="text-slate-400">إصدار Laravel:</span>
                                <span class="font-mono text-slate-200">{{ systemSpecs.laravel_version }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-800/80">
                                <span class="text-slate-400">قاعدة البيانات:</span>
                                <span class="font-mono text-slate-200">{{ systemSpecs.db_connection }} (MySQL)</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-400">نظام التشغيل:</span>
                                <span class="font-mono text-slate-200">{{ systemSpecs.server_os }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Stores preview in Overview -->
                <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <Store class="w-4 h-4 text-blue-400" />
                            <span>أحدث المتاجر المنشأة</span>
                        </h3>
                        <button
                            type="button"
                            @click="activeTab = 'businesses'"
                            class="text-xs text-blue-400 hover:text-blue-300 font-semibold"
                        >
                            عرض الكل ({{ businesses.total }}) &larr;
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-start">
                            <thead class="text-slate-400 border-b border-slate-800 uppercase text-[10px] font-mono">
                                <tr>
                                    <th class="py-2.5 px-3 text-start">المتجر</th>
                                    <th class="py-2.5 px-3 text-start">المالك</th>
                                    <th class="py-2.5 px-3 text-center">القنوات</th>
                                    <th class="py-2.5 px-3 text-center">جهات الاتصال</th>
                                    <th class="py-2.5 px-3 text-center">الحملات</th>
                                    <th class="py-2.5 px-3 text-end">إجراء</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="b in businesses.data.slice(0, 5)" :key="b.id" class="hover:bg-slate-850/50 transition">
                                    <td class="py-3 px-3 font-bold text-slate-200">
                                        {{ b.name }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-400">
                                        <div>{{ b.user?.name || 'غير معروف' }}</div>
                                        <div class="text-[10px] font-mono text-slate-500">{{ b.user?.email }}</div>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <div class="inline-flex items-center gap-1 font-mono text-[10px]">
                                            <span v-if="b.whatsapp_account?.status === 'connected'" class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400">WA</span>
                                            <span v-if="b.telegram_account?.is_active" class="px-1.5 py-0.5 rounded bg-sky-500/20 text-sky-400">TG</span>
                                            <span v-if="b.facebook_account?.is_active" class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400">FB</span>
                                            <span v-if="b.instagram_account?.is_active" class="px-1.5 py-0.5 rounded bg-pink-500/20 text-pink-400">IG</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold text-slate-300">
                                        {{ b.contacts_count || 0 }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold text-slate-300">
                                        {{ b.campaigns_count || 0 }}
                                    </td>
                                    <td class="py-3 px-3 text-end">
                                        <button
                                            type="button"
                                            @click="impersonateBusiness(b.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white transition text-[11px] font-semibold border border-blue-500/30"
                                        >
                                            <LogIn class="w-3 h-3" />
                                            <span>دخول للمتجر</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 5. TAB CONTENT: ALL BUSINESSES / STORES    -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'businesses'" class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <Store class="w-4 h-4 text-blue-400" />
                            <span>كافة المتاجر المسجلة ({{ businesses.total }})</span>
                        </h3>
                        <button
                            type="button"
                            @click="openCreateBusinessModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition text-xs font-semibold shadow-sm"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            <span>إضافة متجر جديد</span>
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <Search class="w-3.5 h-3.5 text-slate-400 absolute start-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="بحث باسم المتجر أو المالك..."
                            class="w-full ps-9 pe-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:ring-0"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead class="text-slate-400 border-b border-slate-800 uppercase text-[10px] font-mono">
                            <tr>
                                <th class="py-3 px-3 text-start">#</th>
                                <th class="py-3 px-3 text-start">اسم المتجر</th>
                                <th class="py-3 px-3 text-start">المالك</th>
                                <th class="py-3 px-3 text-center">قنوات الربط</th>
                                <th class="py-3 px-3 text-center">العملاء</th>
                                <th class="py-3 px-3 text-center">المحادثات</th>
                                <th class="py-3 px-3 text-center">تاريخ الإنشاء</th>
                                <th class="py-3 px-3 text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="b in filteredBusinesses" :key="b.id" class="hover:bg-slate-850/50 transition">
                                <td class="py-3 px-3 font-mono text-slate-500">{{ b.id }}</td>
                                <td class="py-3 px-3 font-bold text-slate-200">
                                    {{ b.name }}
                                </td>
                                <td class="py-3 px-3 text-slate-300">
                                    <div>{{ b.user?.name || 'غير معروف' }}</div>
                                    <div class="text-[10px] font-mono text-slate-500">{{ b.user?.email }}</div>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <div class="inline-flex items-center gap-1 font-mono text-[10px]">
                                        <span v-if="b.whatsapp_account?.status === 'connected'" class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">WA</span>
                                        <span v-if="b.telegram_account?.is_active" class="px-1.5 py-0.5 rounded bg-sky-500/20 text-sky-400 font-bold">TG</span>
                                        <span v-if="b.facebook_account?.is_active" class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">FB</span>
                                        <span v-if="b.instagram_account?.is_active" class="px-1.5 py-0.5 rounded bg-pink-500/20 text-pink-400 font-bold">IG</span>
                                        <span v-if="!b.whatsapp_account && !b.telegram_account && !b.facebook_account && !b.instagram_account" class="text-slate-600">بدون</span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-slate-300">
                                    {{ b.contacts_count || 0 }}
                                </td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-slate-300">
                                    {{ b.conversations_count || 0 }}
                                </td>
                                <td class="py-3 px-3 text-center font-mono text-[11px] text-slate-400">
                                    {{ new Date(b.created_at).toLocaleDateString('ar-EG') }}
                                </td>
                                <td class="py-3 px-3 text-end">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="impersonateBusiness(b.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition text-xs font-semibold shadow-sm"
                                            title="دخول لإدارة المتجر"
                                        >
                                            <LogIn class="w-3.5 h-3.5" />
                                            <span>دخول</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openEditBusinessModal(b)"
                                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                                            title="تعديل المتجر"
                                        >
                                            <Pencil class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteBusiness(b)"
                                            class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition"
                                            title="حذف المتجر"
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

            <!-- ========================================== -->
            <!-- 6. TAB CONTENT: USERS & STORE OWNERS       -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'users'" class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <Users class="w-4 h-4 text-purple-400" />
                            <span>المستخدمين وأصحاب المتاجر ({{ users.total }})</span>
                        </h3>
                        <button
                            type="button"
                            @click="openCreateUserModal"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white transition text-xs font-semibold shadow-sm"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            <span>إضافة مستخدم جديد</span>
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <Search class="w-3.5 h-3.5 text-slate-400 absolute start-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="بحث بالاسم أو البريد..."
                            class="w-full ps-9 pe-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:border-purple-500 focus:ring-0"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead class="text-slate-400 border-b border-slate-800 uppercase text-[10px] font-mono">
                            <tr>
                                <th class="py-3 px-3 text-start">#</th>
                                <th class="py-3 px-3 text-start">الاسم</th>
                                <th class="py-3 px-3 text-start">البريد الإلكتروني</th>
                                <th class="py-3 px-3 text-center">المتاجر</th>
                                <th class="py-3 px-3 text-center">الباقة الحالية</th>
                                <th class="py-3 px-3 text-center">الرتبة</th>
                                <th class="py-3 px-3 text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-slate-850/50 transition">
                                <td class="py-3 px-3 font-mono text-slate-500">{{ u.id }}</td>
                                <td class="py-3 px-3 font-bold text-white flex items-center gap-2">
                                    <span>{{ u.name }}</span>
                                    <Crown v-if="u.is_super_admin" class="w-3.5 h-3.5 text-amber-400 shrink-0" />
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-300">{{ u.email }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-purple-400">
                                    {{ u.businesses_count || 0 }} متجر
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span v-if="u.active_subscription?.plan" class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        {{ u.active_subscription.plan.name }}
                                    </span>
                                    <span v-else class="text-slate-600 text-[10px]">بدون اشتراك</span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                        :class="u.is_super_admin ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-slate-800 text-slate-300'"
                                    >
                                        {{ u.is_super_admin ? 'Super Admin' : 'Store Owner' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-end">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openAssignPlanModal(u)"
                                            class="p-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 transition"
                                            title="تعيين باقة"
                                        >
                                            <CreditCard class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="toggleSuperAdmin(u.id)"
                                            class="p-1.5 rounded-lg transition"
                                            :class="u.is_super_admin ? 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-300' : 'bg-purple-500/10 hover:bg-purple-500/20 text-purple-300'"
                                            :title="u.is_super_admin ? 'إلغاء Super Admin' : 'ترقية لـ Super Admin'"
                                        >
                                            <Shield class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openEditUserModal(u)"
                                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-750 text-slate-300 hover:text-white transition"
                                            title="تعديل المستخدم"
                                        >
                                            <Pencil class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteUser(u)"
                                            class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition"
                                            title="حذف المستخدم"
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

            <!-- ========================================== -->
            <!-- 7. TAB CONTENT: PRICING PLANS              -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'plans'" class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <CreditCard class="w-4 h-4 text-amber-400" />
                            <span>خطط وباقات التسعير ({{ plans.length }})</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            التحكم الكامل في حدود الباقات، أسعار الاشتراكات الشهرية والسنوية، وإتاحتها للعملاء
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openCreatePlanModal"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white transition text-xs font-semibold shadow-sm self-start sm:self-auto"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>إضافة باقة تسعير جديدة</span>
                    </button>
                </div>

                <div v-if="plans.length === 0" class="p-8 text-center text-slate-500 text-xs bg-slate-950/60 rounded-xl border border-slate-800">
                    لا توجد أي باقات مسجلة حالياً. قم بإضافة باقة لتتمكن من تعيين الاشتراكات للمستخدمين.
                </div>

                <!-- Plans Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="p in plans"
                        :key="p.id"
                        class="p-5 rounded-xl border space-y-4 transition relative overflow-hidden"
                        :class="p.is_active ? 'bg-slate-950/80 border-slate-800 hover:border-slate-700' : 'bg-slate-950/40 border-dashed border-slate-800/60 opacity-60'"
                    >
                        <!-- Plan Header -->
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase" :class="p.type === 'premium' ? 'bg-amber-500/20 text-amber-300' : 'bg-blue-500/20 text-blue-300'">
                                    {{ p.type }}
                                </span>
                                <h4 class="text-base font-extrabold text-white mt-1">{{ p.name }}</h4>
                            </div>

                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="p.is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'"
                            >
                                {{ p.is_active ? 'مفعلة' : 'معطلة' }}
                            </span>
                        </div>

                        <!-- Pricing -->
                        <div class="space-y-1">
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-black text-white">${{ p.price_monthly }}</span>
                                <span class="text-xs text-slate-400">/ شهرياً</span>
                            </div>
                            <div class="text-xs text-slate-500">
                                أو ${{ p.price_yearly }} سنوياً
                            </div>
                        </div>

                        <!-- Limits & Features -->
                        <div class="space-y-2 py-2 border-y border-slate-850 text-xs text-slate-300">
                            <div class="flex justify-between">
                                <span class="text-slate-400">الحد الأقصى للمتاجر:</span>
                                <span class="font-bold font-mono">{{ p.max_businesses ? p.max_businesses + ' متجر' : 'غير محدود' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">المشتركين الحاليين:</span>
                                <span class="font-bold font-mono text-amber-400">{{ p.subscriptions_count || 0 }} مشترك</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-2 flex items-center justify-between gap-2">
                            <button
                                type="button"
                                @click="togglePlanActive(p)"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition border"
                                :class="p.is_active ? 'bg-rose-500/10 text-rose-300 border-rose-500/30 hover:bg-rose-500/20' : 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/20'"
                            >
                                {{ p.is_active ? 'تعطيل' : 'تفعيل' }}
                            </button>

                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="openEditPlanModal(p)"
                                    class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition"
                                    title="تعديل الباقة"
                                >
                                    <Pencil class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    @click="deletePlan(p)"
                                    class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition"
                                    title="حذف الباقة"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 8. TAB CONTENT: LIVE WHATSAPP SESSIONS     -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'sessions'" class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <Radio class="w-4 h-4 text-emerald-400" />
                            <span>جلسات محرك واتساب في الذاكرة (Node Baileys Service)</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            التحكم المباشر في جلسات الاتصال والتوجيه لحسابات الواتساب
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="router.reload({ only: ['health'] })"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-750 text-slate-200 text-xs font-semibold transition"
                    >
                        <RefreshCw class="w-3.5 h-3.5" />
                        <span>تحديث الحالة</span>
                    </button>
                </div>

                <div v-if="Object.keys(health.live_sessions || {}).length === 0" class="p-8 text-center text-slate-400 text-xs bg-slate-950/60 rounded-xl border border-slate-800">
                    لا توجد جلسات واتساب نشطة في الذاكرة حالياً. يتم تحميل الجلسات تلقائياً عند طلب الاتصال.
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="(sess, sessKey) in health.live_sessions"
                        :key="sessKey"
                        class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-sm text-slate-200">{{ sessKey }}</span>
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                :class="sess.status === 'connected' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'"
                            >
                                {{ sess.status === 'connected' ? 'متصل (Connected)' : (sess.status || 'جاري الاتصال') }}
                            </span>
                        </div>

                        <div class="space-y-1 text-xs text-slate-400 font-mono">
                            <div class="flex justify-between">
                                <span>رقم الهاتف:</span>
                                <span class="text-white font-bold">{{ sess.phoneNumber ? '+' + sess.phoneNumber : 'غير محدد' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>المتجر ID:</span>
                                <span class="text-slate-300">#{{ sess.businessId || '1' }}</span>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-850 flex items-center justify-between">
                            <span class="text-[10px] text-slate-500">منفذ الاستماع: 3000</span>
                            <button
                                type="button"
                                @click="restartSession(sessKey)"
                                :disabled="restartingSession === sessKey"
                                class="flex items-center gap-1 px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold transition disabled:opacity-50"
                            >
                                <RefreshCw class="w-3 h-3" :class="{ 'animate-spin': restartingSession === sessKey }" />
                                <span>إعادة تشغيل</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 9. TAB CONTENT: RECENT CAMPAIGNS           -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'campaigns'" class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <Flame class="w-4 h-4 text-amber-400" />
                    <span>أحدث الحملات الإعلانية على مستوى المنصة</span>
                </h3>

                <div v-if="recentCampaigns.length === 0" class="p-8 text-center text-slate-500 text-xs">
                    لم يتم إطلاق أي حملات تسويقية بعد.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead class="text-slate-400 border-b border-slate-800 uppercase text-[10px] font-mono">
                            <tr>
                                <th class="py-3 px-3 text-start">الحملة</th>
                                <th class="py-3 px-3 text-start">المتجر</th>
                                <th class="py-3 px-3 text-center">النوع</th>
                                <th class="py-3 px-3 text-center">الإرسال / الإجمالي</th>
                                <th class="py-3 px-3 text-center">الفارق الزمني</th>
                                <th class="py-3 px-3 text-center">الحالة</th>
                                <th class="py-3 px-3 text-end">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="c in recentCampaigns" :key="c.id" class="hover:bg-slate-850/50 transition">
                                <td class="py-3 px-3 font-bold text-white">{{ c.name }}</td>
                                <td class="py-3 px-3 text-slate-300">{{ c.business?.name || 'غير معروف' }}</td>
                                <td class="py-3 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-500/20 text-blue-300">
                                        {{ c.type }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center font-mono">
                                    <span class="text-emerald-400 font-bold">{{ c.sent_count }}</span>
                                    <span class="text-slate-500"> / </span>
                                    <span class="text-slate-300">{{ c.total_recipients }}</span>
                                </td>
                                <td class="py-3 px-3 text-center font-mono text-slate-400">
                                    {{ c.interval_seconds }}s (±{{ c.jitter_seconds }}s)
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                        :class="{
                                            'bg-emerald-500/20 text-emerald-400': c.status === 'completed',
                                            'bg-blue-500/20 text-blue-400': c.status === 'processing',
                                            'bg-amber-500/20 text-amber-400': c.status === 'paused' || c.status === 'pending',
                                            'bg-rose-500/20 text-rose-400': c.status === 'cancelled',
                                        }"
                                    >
                                        {{ c.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-end font-mono text-[11px] text-slate-400">
                                    {{ new Date(c.created_at).toLocaleDateString('ar-EG') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- MODAL: USER CREATE / EDIT                  -->
        <!-- ========================================== -->
        <div v-if="userModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden p-6 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="font-bold text-white text-base">
                        {{ userModalMode === 'create' ? 'إضافة مستخدم جديد' : 'تعديل بيانات المستخدم' }}
                    </h3>
                    <button type="button" @click="userModalOpen = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitUserForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">الاسم الكامل</label>
                        <input
                            v-model="userForm.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-purple-500 focus:ring-0"
                            placeholder="محمد علي"
                        />
                        <p v-if="userForm.errors.name" class="text-[11px] text-rose-400 mt-1">{{ userForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">البريد الإلكتروني</label>
                        <input
                            v-model="userForm.email"
                            type="email"
                            required
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-purple-500 focus:ring-0 font-mono text-left"
                            placeholder="user@example.com"
                        />
                        <p v-if="userForm.errors.email" class="text-[11px] text-rose-400 mt-1">{{ userForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            كلمة المرور {{ userModalMode === 'edit' ? '(اتركها فارغة للتخطي)' : '' }}
                        </label>
                        <input
                            v-model="userForm.password"
                            type="password"
                            :required="userModalMode === 'create'"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-purple-500 focus:ring-0 font-mono"
                            placeholder="••••••••"
                        />
                        <p v-if="userForm.errors.password" class="text-[11px] text-rose-400 mt-1">{{ userForm.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">خطة التسعير (الاشتراك)</label>
                        <select
                            v-model="userForm.plan_id"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-purple-500 focus:ring-0"
                        >
                            <option value="">بدون خطة حالياً</option>
                            <option v-for="p in plans" :key="p.id" :value="p.id">
                                {{ p.name }} (${{ p.price_monthly }}/شهر)
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            id="is_super_admin_check"
                            v-model="userForm.is_super_admin"
                            type="checkbox"
                            class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-purple-600 focus:ring-purple-500 focus:ring-offset-slate-900"
                        />
                        <label for="is_super_admin_check" class="text-xs text-slate-300 font-medium">
                            صلاحية Super Admin (وصول كامل للنظام)
                        </label>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="userModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="userForm.processing"
                            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold transition shadow-md disabled:opacity-50"
                        >
                            {{ userModalMode === 'create' ? 'إنشاء المستخدم' : 'حفظ التعديلات' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL: ASSIGN PLAN TO USER                 -->
        <!-- ========================================== -->
        <div v-if="assignPlanModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden p-6 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="font-bold text-white text-base">
                        تعيين باقة للمستخدم: {{ selectedUserForPlan?.name }}
                    </h3>
                    <button type="button" @click="assignPlanModalOpen = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAssignPlan" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">اختر باقة التسعير</label>
                        <select
                            v-model="assignPlanForm.plan_id"
                            required
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 focus:ring-0"
                        >
                            <option v-for="p in plans" :key="p.id" :value="p.id">
                                {{ p.name }} - ${{ p.price_monthly }}/شهر ({{ p.type }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">مدة الاشتراك (بالأشهر)</label>
                        <input
                            v-model="assignPlanForm.duration_months"
                            type="number"
                            min="1"
                            max="120"
                            required
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 focus:ring-0 font-mono"
                        />
                        <span class="text-[11px] text-slate-500">مثال: 12 شهر يعني سنة كاملة من تاريخ اليوم</span>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="assignPlanModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="assignPlanForm.processing"
                            class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition shadow-md disabled:opacity-50"
                        >
                            تأكيد تفعيل الاشتراك
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL: BUSINESS CREATE / EDIT              -->
        <!-- ========================================== -->
        <div v-if="businessModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden p-6 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="font-bold text-white text-base">
                        {{ businessModalMode === 'create' ? 'إضافة متجر جديد' : 'تعديل المتجر' }}
                    </h3>
                    <button type="button" @click="businessModalOpen = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitBusinessForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">اسم المتجر / الشركة</label>
                        <input
                            v-model="businessForm.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-blue-500 focus:ring-0"
                            placeholder="متجر الأناقة"
                        />
                        <p v-if="businessForm.errors.name" class="text-[11px] text-rose-400 mt-1">{{ businessForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">مالك المتجر (المستخدم المسجل)</label>
                        <select
                            v-model="businessForm.user_id"
                            required
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-blue-500 focus:ring-0"
                        >
                            <option v-for="u in allUsersSimple" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.email }})
                            </option>
                        </select>
                        <p v-if="businessForm.errors.user_id" class="text-[11px] text-rose-400 mt-1">{{ businessForm.errors.user_id }}</p>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="businessModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="businessForm.processing"
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold transition shadow-md disabled:opacity-50"
                        >
                            {{ businessModalMode === 'create' ? 'إنشاء المتجر' : 'حفظ التعديلات' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL: PRICING PLAN CREATE / EDIT          -->
        <!-- ========================================== -->
        <div v-if="planModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden p-6 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="font-bold text-white text-base">
                        {{ planModalMode === 'create' ? 'إضافة باقة تسعير جديدة' : 'تعديل باقة التسعير' }}
                    </h3>
                    <button type="button" @click="planModalOpen = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitPlanForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">اسم الباقة</label>
                        <input
                            v-model="planForm.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 focus:ring-0"
                            placeholder="باقة المشاريع الناشئة"
                        />
                        <p v-if="planForm.errors.name" class="text-[11px] text-rose-400 mt-1">{{ planForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">نوع الباقة (Type)</label>
                        <select
                            v-model="planForm.type"
                            required
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 focus:ring-0"
                        >
                            <option value="standard">Standard (أساسية)</option>
                            <option value="premium">Premium (متقدمة)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">السعر الشهري ($)</label>
                            <input
                                v-model="planForm.price_monthly"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 focus:ring-0 font-mono"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">السعر السنوي ($)</label>
                            <input
                                v-model="planForm.price_yearly"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 focus:ring-0 font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">الحد الأقصى للمتاجر (اتركه فارغاً لغير المحدود)</label>
                        <input
                            v-model="planForm.max_businesses"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:border-amber-500 focus:ring-0 font-mono"
                            placeholder="مثال: 1"
                        />
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            id="is_active_plan"
                            v-model="planForm.is_active"
                            type="checkbox"
                            class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-amber-600 focus:ring-amber-500 focus:ring-offset-slate-900"
                        />
                        <label for="is_active_plan" class="text-xs text-slate-300 font-medium">
                            الباقة مفعلة ومتاحة للاشتراك
                        </label>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="planModalOpen = false"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="planForm.processing"
                            class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition shadow-md disabled:opacity-50"
                        >
                            {{ planModalMode === 'create' ? 'إضافة الباقة' : 'حفظ التعديلات' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
