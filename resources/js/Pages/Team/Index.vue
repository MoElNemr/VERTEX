<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Users,
    UserPlus,
    ShieldCheck,
    Shield,
    Headphones,
    Store,
    CheckCircle2,
    XCircle,
    Edit2,
    Trash2,
    Search,
    Filter,
    Key,
    Sparkles,
    AlertCircle,
    Building2,
    Power,
    Check
} from 'lucide-vue-next';

const props = defineProps({
    members: Array,
    businesses: Array,
    stats: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');
const businessFilter = ref(props.filters?.business_id || '');

const showModal = ref(false);
const isEditing = ref(false);
const editingMemberId = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'agent',
    is_active: true,
    business_ids: [],
});

const openCreateModal = () => {
    isEditing.value = false;
    editingMemberId.value = null;
    form.reset();
    form.clearErrors();
    form.role = 'agent';
    form.is_active = true;
    // افتراضياً تحديد جميع المتاجر إذا كان متجراً واحداً أو تركه فارغاً ليختار
    form.business_ids = props.businesses.length === 1 ? [props.businesses[0].id] : [];
    showModal.value = true;
};

const openEditModal = (member) => {
    isEditing.value = true;
    editingMemberId.value = member.id;
    form.clearErrors();
    form.name = member.name;
    form.email = member.email;
    form.password = '';
    form.role = member.role;
    form.is_active = member.is_active;
    form.business_ids = member.businesses ? member.businesses.map(b => b.id) : [];
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('team.update', editingMemberId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('team.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const toggleMemberStatus = (member) => {
    router.patch(route('team.toggle-status', member.id));
};

const deleteMember = (member) => {
    if (confirm(`هل أنت متأكد من حذف حساب "${member.name}" من فريق العمل؟`)) {
        router.delete(route('team.destroy', member.id));
    }
};

const selectAllBusinesses = () => {
    form.business_ids = props.businesses.map(b => b.id);
};

const deselectAllBusinesses = () => {
    form.business_ids = [];
};

const toggleBusiness = (businessId) => {
    const index = form.business_ids.indexOf(businessId);
    if (index === -1) {
        form.business_ids.push(businessId);
    } else {
        form.business_ids.splice(index, 1);
    }
};

const applyFilters = () => {
    router.get(
        route('team.index'),
        {
            search: searchQuery.value || undefined,
            role: roleFilter.value || undefined,
            business_id: businessFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const getRoleBadge = (role) => {
    switch (role) {
        case 'admin':
            return {
                label: 'مسؤول',
                class: 'bg-purple-950/70 text-purple-300 border-purple-800/80',
                icon: ShieldCheck,
                color: 'text-purple-400',
            };
        case 'supervisor':
            return {
                label: 'مشرف',
                class: 'bg-amber-950/70 text-amber-300 border-amber-800/80',
                icon: Shield,
                color: 'text-amber-400',
            };
        default:
            return {
                label: 'وكيل دعم',
                class: 'bg-emerald-950/70 text-emerald-300 border-emerald-800/80',
                icon: Headphones,
                color: 'text-emerald-400',
            };
    }
};
</script>

<template>
    <Head title="إدارة فريق العمل والصلاحيات - VERTEX" />

    <AuthenticatedLayout>
        <!-- Top Hero Section -->
        <div class="border-b border-slate-800 bg-slate-900/60 backdrop-blur-md" dir="rtl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-950/60 text-blue-300 border border-blue-800/60 mb-2.5">
                            <Sparkles class="w-3.5 h-3.5 text-blue-400" />
                            <span>المرحلة 4: إدارة الفريق والصلاحيات</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                            <span>فريق العمل والوكلاء</span>
                            <span class="text-xs px-2.5 py-1 rounded-full bg-slate-800 text-slate-300 font-mono">
                                {{ stats.total }} أعضاء
                            </span>
                        </h1>
                        <p class="mt-1 text-xs sm:text-sm text-slate-400 max-w-2xl leading-relaxed">
                            أضف أعضاء الفريق ووكلاء الدعم وحدد أدوارهم والمتاجر المصرح لهم برؤيتها مع عزل كامل للبيانات والمحادثات.
                        </p>
                    </div>

                    <!-- Action Button -->
                    <div class="flex items-center gap-3">
                        <button
                            @click="openCreateModal"
                            class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-5 sm:py-3 rounded-xl bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-semibold text-xs sm:text-sm shadow-lg shadow-blue-600/20 transition w-full sm:w-auto"
                        >
                            <UserPlus class="w-4 h-4" />
                            <span>إضافة عضو جديد</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mt-6">
                    <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 sm:p-4 flex items-center justify-between">
                        <div>
                            <span class="block text-[11px] font-semibold text-slate-400">إجمالي الفريق</span>
                            <span class="text-xl sm:text-2xl font-black text-white">{{ stats.total }}</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-blue-950/60 text-blue-400 border border-blue-800/40">
                            <Users class="w-5 h-5" />
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 sm:p-4 flex items-center justify-between">
                        <div>
                            <span class="block text-[11px] font-semibold text-purple-300">المسؤولين</span>
                            <span class="text-xl sm:text-2xl font-black text-white">{{ stats.admins }}</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-purple-950/60 text-purple-400 border border-purple-800/40">
                            <ShieldCheck class="w-5 h-5" />
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 sm:p-4 flex items-center justify-between">
                        <div>
                            <span class="block text-[11px] font-semibold text-amber-300">المشرفين</span>
                            <span class="text-xl sm:text-2xl font-black text-white">{{ stats.supervisors }}</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-amber-950/60 text-amber-400 border border-amber-800/40">
                            <Shield class="w-5 h-5" />
                        </div>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-3 sm:p-4 flex items-center justify-between">
                        <div>
                            <span class="block text-[11px] font-semibold text-emerald-300">وكلاء الدعم</span>
                            <span class="text-xl sm:text-2xl font-black text-white">{{ stats.agents }}</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-emerald-950/60 text-emerald-400 border border-emerald-800/40">
                            <Headphones class="w-5 h-5" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="py-6 sm:py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" dir="rtl">
            <!-- Search & Filter Bar -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-slate-900/80 p-3 sm:p-4 rounded-2xl border border-slate-800">
                <div class="relative flex-1">
                    <Search class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        type="text"
                        v-model="searchQuery"
                        @input="applyFilters"
                        placeholder="ابحث بالاسم أو البريد الإلكتروني..."
                        class="w-full pr-10 pl-4 py-2 bg-slate-950/80 border border-slate-800 rounded-xl text-xs sm:text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <!-- Role Filter -->
                    <select
                        v-model="roleFilter"
                        @change="applyFilters"
                        class="bg-slate-950/80 border border-slate-800 text-slate-200 text-xs sm:text-sm rounded-xl px-3 py-2 focus:border-blue-500 focus:outline-none cursor-pointer"
                    >
                        <option value="">جميع الأدوار</option>
                        <option value="admin">مسؤول</option>
                        <option value="supervisor">مشرف</option>
                        <option value="agent">وكيل دعم</option>
                    </select>

                    <!-- Business Filter -->
                    <select
                        v-model="businessFilter"
                        @change="applyFilters"
                        class="bg-slate-950/80 border border-slate-800 text-slate-200 text-xs sm:text-sm rounded-xl px-3 py-2 focus:border-blue-500 focus:outline-none cursor-pointer"
                    >
                        <option value="">جميع المتاجر</option>
                        <option v-for="b in businesses" :key="b.id" :value="b.id">
                            {{ b.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="members.length === 0"
                class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-8 sm:p-12 text-center"
            >
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-blue-950/50 border border-blue-800/50 flex items-center justify-center text-blue-400">
                    <Users class="w-7 h-7" />
                </div>
                <h3 class="text-base sm:text-lg font-bold text-white mb-1">لا يوجد أعضاء مطابقين للبحث</h3>
                <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto mb-6">
                    لم يتم العثور على أعضاء مسجلين بهذه المعايير. يمكنك إضافة عضو جديد الآن لبدء توزيع المهام.
                </p>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition"
                >
                    <UserPlus class="w-4 h-4" />
                    <span>إضافة عضو جديد</span>
                </button>
            </div>

            <!-- Members Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="member in members"
                    :key="member.id"
                    class="bg-slate-900/90 border rounded-2xl p-5 flex flex-col justify-between transition hover:border-slate-700 shadow-md relative"
                    :class="member.is_active ? 'border-slate-800' : 'border-rose-950/60 bg-slate-925/60 opacity-80'"
                >
                    <div>
                        <!-- Top Row: Role & Status -->
                        <div class="flex items-center justify-between mb-3.5">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border"
                                :class="getRoleBadge(member.role).class"
                            >
                                <component :is="getRoleBadge(member.role).icon" class="w-3.5 h-3.5" />
                                <span>{{ getRoleBadge(member.role).label }}</span>
                            </span>

                            <button
                                @click="toggleMemberStatus(member)"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border transition cursor-pointer"
                                :class="member.is_active
                                    ? 'bg-emerald-950/60 text-emerald-400 border-emerald-800/60 hover:bg-rose-950/40 hover:text-rose-400'
                                    : 'bg-rose-950/60 text-rose-400 border-rose-800/60 hover:bg-emerald-950/40 hover:text-emerald-400'"
                                :title="member.is_active ? 'انقر للتعطيل' : 'انقر للتفعيل'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="member.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                <span>{{ member.is_active ? 'نشط' : 'معطل' }}</span>
                            </button>
                        </div>

                        <!-- Member Info -->
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700/80 flex items-center justify-center text-white font-bold text-sm shrink-0 uppercase shadow">
                                {{ member.name.charAt(0) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-bold text-white truncate">{{ member.name }}</h3>
                                <p class="text-xs text-slate-400 truncate dir-ltr text-right">{{ member.email }}</p>
                            </div>
                        </div>

                        <!-- Assigned Businesses -->
                        <div class="pt-3 border-t border-slate-800/80">
                            <span class="block text-[11px] font-semibold text-slate-400 mb-2 flex items-center gap-1.5">
                                <Store class="w-3.5 h-3.5 text-blue-400" />
                                <span>المتاجر المخصصة ({{ member.businesses ? member.businesses.length : 0 }}):</span>
                            </span>
                            <div class="flex flex-wrap gap-1.5 max-h-20 overflow-y-auto">
                                <span
                                    v-for="b in member.businesses"
                                    :key="b.id"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-800/90 text-[11px] text-slate-300 border border-slate-700/60"
                                >
                                    <Building2 class="w-3 h-3 text-slate-400" />
                                    <span class="truncate max-w-[120px]">{{ b.name }}</span>
                                </span>
                                <span
                                    v-if="!member.businesses || member.businesses.length === 0"
                                    class="text-[11px] text-rose-400 italic"
                                >
                                    غير مربوط بأي متجر
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Actions -->
                    <div class="mt-5 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                        <button
                            @click="openEditModal(member)"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-400 hover:text-blue-300 transition cursor-pointer p-1"
                        >
                            <Edit2 class="w-3.5 h-3.5" />
                            <span>تعديل الصلاحيات</span>
                        </button>

                        <button
                            @click="deleteMember(member)"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-400 hover:text-rose-300 transition cursor-pointer p-1"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                            <span>حذف</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Team Member Modal -->
        <Modal :show="showModal" @close="closeModal" max-width="lg">
            <div class="p-6 bg-slate-900 text-slate-100 rounded-2xl border border-slate-800" dir="rtl">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-blue-950/60 text-blue-400 border border-blue-800/50">
                            <UserPlus v-if="!isEditing" class="w-5 h-5" />
                            <Edit2 v-else class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-white">
                                {{ isEditing ? 'تعديل بيانات وصلاحيات العضو' : 'إضافة عضو جديد للفريق' }}
                            </h2>
                            <p class="text-xs text-slate-400">
                                {{ isEditing ? 'قم بتحديث الأدوار أو كلمة المرور أو المتاجر المخصصة' : 'أدخل بيانات العضو وحدد دوره ومتاجره في النظام' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="mt-5 space-y-4">
                    <!-- Name -->
                    <div>
                        <InputLabel for="m_name" value="الاسم الكامل" class="text-slate-300 text-xs font-semibold mb-1" />
                        <TextInput
                            id="m_name"
                            type="text"
                            v-model="form.name"
                            class="block w-full text-right bg-slate-950 border-slate-800 text-slate-100 focus:border-blue-500 rounded-xl text-sm"
                            required
                            placeholder="مثال: أحمد عبد الله"
                        />
                        <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div>
                        <InputLabel for="m_email" value="البريد الإلكتروني للعمل" class="text-slate-300 text-xs font-semibold mb-1" />
                        <TextInput
                            id="m_email"
                            type="email"
                            v-model="form.email"
                            class="block w-full text-right bg-slate-950 border-slate-800 text-slate-100 focus:border-blue-500 rounded-xl text-sm"
                            required
                            placeholder="agent@company.com"
                        />
                        <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div>
                        <InputLabel
                            for="m_password"
                            :value="isEditing ? 'كلمة المرور الجديدة (اتركها فارغة للإبقاء على الحالية)' : 'تعيين كلمة المرور الأولية'"
                            class="text-slate-300 text-xs font-semibold mb-1"
                        />
                        <TextInput
                            id="m_password"
                            type="password"
                            v-model="form.password"
                            class="block w-full text-right bg-slate-950 border-slate-800 text-slate-100 focus:border-blue-500 rounded-xl text-sm"
                            :required="!isEditing"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.password" />
                    </div>

                    <!-- Role Selection Cards -->
                    <div>
                        <InputLabel value="الدور والصلاحيات في النظام" class="text-slate-300 text-xs font-semibold mb-1.5" />
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <!-- Admin -->
                            <div
                                @click="form.role = 'admin'"
                                class="p-3 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                :class="form.role === 'admin'
                                    ? 'bg-purple-950/50 border-purple-600 text-white shadow-sm'
                                    : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-1.5 font-bold text-xs" :class="form.role === 'admin' ? 'text-purple-300' : 'text-slate-300'">
                                        <ShieldCheck class="w-4 h-4 text-purple-400" />
                                        <span>مسؤول</span>
                                    </div>
                                    <Check v-if="form.role === 'admin'" class="w-3.5 h-3.5 text-purple-400" />
                                </div>
                                <p class="text-[10px] text-slate-400 leading-tight">
                                    تحكم كامل بإدارة الفريق، القنوات، وكافة المحادثات.
                                </p>
                            </div>

                            <!-- Supervisor -->
                            <div
                                @click="form.role = 'supervisor'"
                                class="p-3 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                :class="form.role === 'supervisor'
                                    ? 'bg-amber-950/50 border-amber-600 text-white shadow-sm'
                                    : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-1.5 font-bold text-xs" :class="form.role === 'supervisor' ? 'text-amber-300' : 'text-slate-300'">
                                        <Shield class="w-4 h-4 text-amber-400" />
                                        <span>مشرف</span>
                                    </div>
                                    <Check v-if="form.role === 'supervisor'" class="w-3.5 h-3.5 text-amber-400" />
                                </div>
                                <p class="text-[10px] text-slate-400 leading-tight">
                                    متابعة جميع المحادثات وتوزيعها وإسنادها للوكلاء.
                                </p>
                            </div>

                            <!-- Agent -->
                            <div
                                @click="form.role = 'agent'"
                                class="p-3 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                :class="form.role === 'agent'
                                    ? 'bg-emerald-950/50 border-emerald-600 text-white shadow-sm'
                                    : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700'"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-1.5 font-bold text-xs" :class="form.role === 'agent' ? 'text-emerald-300' : 'text-slate-300'">
                                        <Headphones class="w-4 h-4 text-emerald-400" />
                                        <span>وكيل دعم</span>
                                    </div>
                                    <Check v-if="form.role === 'agent'" class="w-3.5 h-3.5 text-emerald-400" />
                                </div>
                                <p class="text-[10px] text-slate-400 leading-tight">
                                    الرد على المحادثات المسندة إليه فقط ضمن متاجره.
                                </p>
                            </div>
                        </div>
                        <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.role" />
                    </div>

                    <!-- Business Assignments -->
                    <div class="pt-2">
                        <div class="flex items-center justify-between mb-2">
                            <InputLabel value="المتاجر المخصصة للعضو (Tenant Scoping)" class="text-slate-300 text-xs font-semibold" />
                            <div class="flex items-center gap-2 text-[11px]">
                                <button
                                    type="button"
                                    @click="selectAllBusinesses"
                                    class="text-blue-400 hover:underline cursor-pointer"
                                >
                                    تحديد الكل
                                </button>
                                <span class="text-slate-600">|</span>
                                <button
                                    type="button"
                                    @click="deselectAllBusinesses"
                                    class="text-slate-400 hover:underline cursor-pointer"
                                >
                                    إلغاء التحديد
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 bg-slate-950/80 rounded-xl border border-slate-800">
                            <div
                                v-for="b in businesses"
                                :key="b.id"
                                @click="toggleBusiness(b.id)"
                                class="flex items-center justify-between p-2 rounded-lg border cursor-pointer transition select-none"
                                :class="form.business_ids.includes(b.id)
                                    ? 'bg-blue-950/40 border-blue-600/80 text-white'
                                    : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'"
                            >
                                <div class="flex items-center gap-2 truncate">
                                    <Building2 class="w-3.5 h-3.5 text-blue-400 shrink-0" />
                                    <span class="text-xs font-medium truncate">{{ b.name }}</span>
                                </div>
                                <div
                                    class="w-4 h-4 rounded border flex items-center justify-center shrink-0"
                                    :class="form.business_ids.includes(b.id) ? 'bg-blue-600 border-blue-500 text-white' : 'border-slate-700'"
                                >
                                    <Check v-if="form.business_ids.includes(b.id)" class="w-3 h-3 stroke-[3]" />
                                </div>
                            </div>
                        </div>
                        <InputError class="mt-1 text-xs text-rose-400" :message="form.errors.business_ids" />
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                        <SecondaryButton @click="closeModal" class="border-slate-700 text-slate-300 hover:bg-slate-800 cursor-pointer text-xs">
                            إلغاء
                        </SecondaryButton>
                        <PrimaryButton
                            class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold cursor-pointer"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ isEditing ? 'حفظ التعديلات' : 'إضافة العضو' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
