<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { Check, CheckCircle2, X, AlertCircle, Sparkles, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    accounts: {
        type: Array,
        default: () => [],
    },
    businessId: {
        type: [String, Number],
        required: true,
    },
});

const emit = defineEmits(['close']);

const selectedPageId = ref('');
const connectInstagram = ref(true);
const isSubmitting = ref(false);

watch(
    () => props.accounts,
    (newAccounts) => {
        if (newAccounts && newAccounts.length > 0 && !selectedPageId.value) {
            selectedPageId.value = newAccounts[0].id;
        }
    },
    { immediate: true }
);

const selectedAccount = computed(() => {
    return props.accounts.find((acc) => String(acc.id) === String(selectedPageId.value)) || null;
});

const hasInstagram = computed(() => {
    return Boolean(selectedAccount.value && selectedAccount.value.instagram_business_account);
});

const instagramAccount = computed(() => {
    return selectedAccount.value?.instagram_business_account || null;
});

const closeModal = () => {
    if (!isSubmitting.value) {
        emit('close');
    }
};

const confirmSelection = () => {
    if (!selectedPageId.value) return;

    isSubmitting.value = true;
    router.post(
        route('meta.persist-asset', props.businessId),
        {
            page_id: selectedPageId.value,
            connect_instagram: connectInstagram.value && hasInstagram.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
                emit('close');
            },
        }
    );
};
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="closeModal">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#1877F2]/10 text-[#1877F2] flex items-center justify-center font-bold border border-[#1877F2]/20">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-white">اختيار الصفحة وحساب إنستجرام للربط</h3>
                        <p class="text-xs text-slate-400">حدد الصفحة الرسمية التي ترغب في ربطها بهذا البيزنس</p>
                    </div>
                </div>
                <button
                    @click="closeModal"
                    class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Page List -->
            <div class="mt-4">
                <label class="block text-xs font-bold text-slate-300 mb-2">
                    الصفحات المتاحة في حساب ميتا الخاص بك ({{ accounts.length }})
                </label>

                <div class="max-h-64 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                    <div
                        v-for="account in accounts"
                        :key="account.id"
                        @click="selectedPageId = account.id"
                        class="p-3.5 rounded-xl border transition cursor-pointer flex items-center justify-between"
                        :class="
                            String(selectedPageId) === String(account.id)
                                ? 'bg-blue-950/40 border-[#1877F2] ring-1 ring-[#1877F2]'
                                : 'bg-slate-950 border-slate-800 hover:border-slate-700'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <!-- Page Avatar -->
                            <img
                                v-if="account.picture?.data?.url"
                                :src="account.picture.data.url"
                                :alt="account.name"
                                class="w-10 h-10 rounded-full object-cover border border-slate-700 bg-slate-800"
                            />
                            <div
                                v-else
                                class="w-10 h-10 rounded-full bg-blue-600/20 text-[#1877F2] flex items-center justify-center font-bold text-sm border border-blue-500/30"
                            >
                                {{ account.name?.charAt(0) || 'F' }}
                            </div>

                            <div>
                                <h4 class="text-sm font-bold text-white">{{ account.name }}</h4>
                                <div class="flex items-center gap-2 text-xs text-slate-400">
                                    <span v-if="account.category">{{ account.category }}</span>
                                    <span v-if="account.category" class="text-slate-600">•</span>
                                    <span class="font-mono text-[10px] text-slate-500">ID: {{ account.id }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Radio Indicator -->
                        <div
                            class="w-5 h-5 rounded-full border flex items-center justify-center transition"
                            :class="
                                String(selectedPageId) === String(account.id)
                                    ? 'border-[#1877F2] bg-[#1877F2]'
                                    : 'border-slate-700 bg-slate-900'
                            "
                        >
                            <Check v-if="String(selectedPageId) === String(account.id)" class="w-3 h-3 text-white stroke-[3]" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linked Instagram Sub-Card -->
            <div class="mt-5 pt-4 border-t border-slate-800">
                <div v-if="hasInstagram" class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="instagramAccount?.profile_picture_url"
                                :src="instagramAccount.profile_picture_url"
                                :alt="instagramAccount.username"
                                class="w-9 h-9 rounded-full object-cover border border-pink-500/40"
                            />
                            <div
                                v-else
                                class="w-9 h-9 rounded-full bg-gradient-to-tr from-yellow-500 via-pink-500 to-purple-600 text-white flex items-center justify-center font-bold text-xs"
                            >
                                IG
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-white">حساب إنستجرام للأعمال المرتبط:</span>
                                    <span class="text-xs font-bold text-pink-400 font-mono">@{{ instagramAccount?.username }}</span>
                                </div>
                                <span class="text-[11px] text-slate-400 block font-mono">ID: {{ instagramAccount?.id }}</span>
                            </div>
                        </div>

                        <!-- Toggle connect Instagram -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="connectInstagram"
                                class="sr-only peer"
                            />
                            <div class="w-10 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-pink-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400" />
                        <span>سيتم ربط إنستجرام تلقائيًا واستقبال رسائل الـ Direct وتفعيل الرد التلقائي.</span>
                    </div>
                </div>

                <div v-else class="p-3.5 rounded-xl bg-slate-950 border border-slate-800/80 flex items-center gap-2.5 text-xs text-slate-400">
                    <AlertCircle class="w-4 h-4 text-amber-400 shrink-0" />
                    <span>هذه الصفحة لا تحتوي على حساب إنستجرام للأعمال مرتبط بها حاليًا في ميتا. يمكنك ربط إنستجرام بالصفحة لاحقًا عبر فيسبوك.</span>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button
                    type="button"
                    @click="closeModal"
                    :disabled="isSubmitting"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition"
                >
                    إلغاء
                </button>
                <button
                    type="button"
                    @click="confirmSelection"
                    :disabled="isSubmitting || !selectedPageId"
                    class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1877F2] hover:bg-[#166fe5] active:bg-[#1465d2] transition flex items-center gap-2 shadow-lg shadow-blue-500/20 disabled:opacity-50"
                >
                    <Loader2 v-if="isSubmitting" class="w-3.5 h-3.5 animate-spin" />
                    <Sparkles v-else class="w-3.5 h-3.5" />
                    <span>تأكيد وربط القنوات</span>
                </button>
            </div>
        </div>
    </Modal>
</template>
