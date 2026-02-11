<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    bankAccounts: Array,
    status: String,
});

const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    bank_name: '',
    account_name: '',
    account_number: '',
    is_default: false,
});

const resetForm = () => {
    form.reset();
    isEditing.value = false;
    editingId.ref = null;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('profile.bank-accounts.update', editingId.value), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('profile.bank-accounts.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const editAccount = (account) => {
    isEditing.value = true;
    editingId.value = account.id;
    form.bank_name = account.bank_name;
    form.account_name = account.account_name;
    form.account_number = account.account_number;
    form.is_default = account.is_default;
};

const deleteAccount = (id) => {
    if (confirm('Are you sure you want to delete this bank account?')) {
        form.delete(route('profile.bank-accounts.destroy', id));
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Bank Accounts" />

        <div class="max-w-4xl mx-auto">
            <div class="mb-10">
                <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">Bank Accounts</h1>
                <p class="text-slate-500 font-normal">Manage your payment receiving accounts.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Form Section -->
                <div class="md:col-span-1">
                    <div class="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-xl shadow-slate-200/10 h-fit sticky top-24">
                        <h3 class="text-lg font-semibold text-slate-900 mb-6">{{ isEditing ? 'Edit Account' : 'Add New Account' }}</h3>
                        
                        <form @submit.prevent="submit" class="space-y-5">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Bank Name</label>
                                <input 
                                    v-model="form.bank_name"
                                    type="text" 
                                    placeholder="e.g. BCA, Mandiri"
                                    class="w-full rounded-xl border-none bg-slate-50 px-4 py-3.5 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                    required
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Account Holder Name</label>
                                <input 
                                    v-model="form.account_name"
                                    type="text" 
                                    placeholder="Full name"
                                    class="w-full rounded-xl border-none bg-slate-50 px-4 py-3.5 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                    required
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Account Number</label>
                                <input 
                                    v-model="form.account_number"
                                    type="text" 
                                    placeholder="0000 0000 0000"
                                    class="w-full rounded-xl border-none bg-slate-50 px-4 py-3.5 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                    required
                                />
                            </div>

                            <div class="flex items-center gap-2 px-1">
                                <input 
                                    v-model="form.is_default"
                                    type="checkbox" 
                                    id="is_default"
                                    class="h-4 w-4 rounded border-slate-200 text-[#07304a] focus:ring-[#07304a]"
                                />
                                <label for="is_default" class="text-xs font-semibold text-slate-500">Set as default</label>
                            </div>

                            <div class="pt-2 flex flex-col gap-2">
                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full rounded-xl bg-[#07304a] px-5 py-3.5 text-xs font-bold text-white shadow-lg shadow-[#07304a]/20 transition-all hover:bg-[#002d66] active:scale-95 disabled:opacity-50"
                                >
                                    {{ isEditing ? 'Update Account' : 'Add Bank Account' }}
                                </button>
                                <button 
                                    v-if="isEditing"
                                    type="button"
                                    @click="resetForm"
                                    class="w-full rounded-xl bg-slate-100 px-5 py-3.5 text-xs font-bold text-slate-600 transition-all hover:bg-slate-200"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- List Section -->
                <div class="md:col-span-2 space-y-4">
                    <div v-if="status" class="mb-4 rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-xs font-semibold text-emerald-600">
                        {{ status }}
                    </div>

                    <div v-if="bankAccounts.length === 0" class="flex flex-col items-center justify-center rounded-[2rem] border border-dashed border-slate-200 p-20 text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 mb-4">
                            <Icon icon="si:building-line" :width="32" :height="32"  />
                        </div>
                        <p class="text-sm font-semibold text-slate-900">No bank accounts added</p>
                        <p class="text-xs font-normal text-slate-400 mt-1">Add your bank details to show them on your invoices.</p>
                    </div>

                    <div 
                        v-for="account in bankAccounts" 
                        :key="account.id"
                        class="group relative bg-white rounded-[2rem] border border-slate-100 p-8 shadow-xl shadow-slate-200/5 transition-all hover:border-[#07304a]/20"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-5">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-[#07304a] transition-all group-hover:bg-[#07304a] group-hover:text-white">
                                    <Icon icon="si:building-line" :width="24" :height="24"  />
                                </div>
                                <div>
                                    <div class="flex items-center gap-3">
                                        <h4 class="text-lg font-semibold text-slate-900 tracking-tight">{{ account.bank_name }}</h4>
                                        <span v-if="account.is_default" class="rounded-full bg-emerald-50 px-2.5 py-1 text-[8px] font-semibold uppercase tracking-widest text-emerald-600 border border-emerald-100">Default</span>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-widest mt-0.5">{{ account.account_number }}</p>
                                    <p class="text-xs font-semibold text-slate-400 mt-2">Held by: <span class="text-slate-600 font-bold">{{ account.account_name }}</span></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button @click="editAccount(account)" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-slate-900 transition-all">
                                    <Icon icon="si:ai-edit-line" :width="16" :height="16"  />
                                </button>
                                <button @click="deleteAccount(account.id)" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-rose-300 hover:bg-rose-50 hover:text-rose-500 transition-all">
                                    <Icon icon="si:bin-line" :width="16" :height="16"  />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
