<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    invoice_prefix: String,
    quotation_prefix: String,
    currency: String,
    company_name: String,
    company_address: String,
    company_phone: String,
    company_email: String,
    company_website: String,
    company_tax_id: String,
    company_logo_url: String,
    taxes: Array,
});

const activeTab = ref('general');

const profileForm = useForm({
    invoice_prefix: props.invoice_prefix,
    quotation_prefix: props.quotation_prefix,
    currency: props.currency || 'IDR',
});

const companyForm = useForm({
    company_name: props.company_name,
    company_address: props.company_address,
    company_phone: props.company_phone,
    company_email: props.company_email,
    company_website: props.company_website,
    company_tax_id: props.company_tax_id,
    company_logo: null,
});
const companyLogoPreview = ref(props.company_logo_url || '');

const taxForm = useForm({
    name: '',
    type: 'add',
    rate: 0,
});

const editingTax = ref(null);

const submitProfile = () => {
    profileForm.put(route('settings.update'), {
        preserveScroll: true,
    });
};

const submitCompany = () => {
    companyForm
        .transform((data) => ({
            ...data,
            _method: 'put',
        }))
        .post(route('settings.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                companyForm.company_logo = null;
            },
        });
};

const onCompanyLogoChange = (event) => {
    const [file] = event.target.files || [];

    if (!file) {
        return;
    }

    companyForm.company_logo = file;
    companyLogoPreview.value = URL.createObjectURL(file);
};

const submitTax = () => {
    if (editingTax.value) {
        taxForm.put(route('settings.taxes.update', editingTax.value), {
            preserveScroll: true,
            onSuccess: () => {
                taxForm.reset();
                editingTax.value = null;
            },
        });
    } else {
        taxForm.post(route('settings.taxes.store'), {
            preserveScroll: true,
            onSuccess: () => {
                taxForm.reset();
            },
        });
    }
};

const editTax = (tax) => {
    editingTax.value = tax.id;
    taxForm.name = tax.name;
    taxForm.type = tax.type;
    taxForm.rate = tax.rate;
};

const cancelEdit = () => {
    editingTax.value = null;
    taxForm.reset();
};

const deleteTax = (tax) => {
    if (confirm('Are you sure you want to delete this tax?')) {
        router.delete(route('settings.taxes.destroy', tax), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Settings" />

        <!-- Full Width Container -->
        <div class="max-w-full">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">Settings</h1>
                <p class="text-slate-500 font-normal mt-1">Manage your application and company preferences.</p>
            </div>

            <div v-if="$page.props.flash.status" class="mb-8 rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-xs font-semibold text-emerald-600">
                {{ $page.props.flash.status }}
            </div>

            <!-- Tab Navigation (Horizontal) -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="flex gap-8">
                    <button
                        @click="activeTab = 'general'"
                        :class="[
                            'pb-4 text-sm font-semibold transition-all border-b-2',
                            activeTab === 'general'
                                ? 'text-[#07304a] border-[#07304a]'
                                : 'text-slate-500 border-transparent hover:text-slate-700'
                        ]"
                    >
                        General
                    </button>
                    <button
                        @click="activeTab = 'company'"
                        :class="[
                            'pb-4 text-sm font-semibold transition-all border-b-2',
                            activeTab === 'company'
                                ? 'text-[#07304a] border-[#07304a]'
                                : 'text-slate-500 border-transparent hover:text-slate-700'
                        ]"
                    >
                        Company
                    </button>
                    <button
                        @click="activeTab = 'taxes'"
                        :class="[
                            'pb-4 text-sm font-semibold transition-all border-b-2',
                            activeTab === 'taxes'
                                ? 'text-[#07304a] border-[#07304a]'
                                : 'text-slate-500 border-transparent hover:text-slate-700'
                        ]"
                    >
                        Taxes
                    </button>
                    <button
                        @click="activeTab = 'profile'"
                        :class="[
                            'pb-4 text-sm font-semibold transition-all border-b-2',
                            activeTab === 'profile'
                                ? 'text-[#07304a] border-[#07304a]'
                                : 'text-slate-500 border-transparent hover:text-slate-700'
                        ]"
                    >
                        Profile
                    </button>
                </nav>
            </div>

            <!-- General Tab Content -->
            <div v-show="activeTab === 'general'" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Account Section -->
                <div class="bg-white rounded-2xl border border-slate-100 p-8 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900 mb-6">Account</h3>
                    <div class="space-y-6">
                        <div class="flex items-center gap-6">
                            <div class="flex-shrink-0">
                                <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center">
                                    <Icon icon="si:user-line" :width="32" :height="32" class="text-slate-400"  />
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-900">{{ $page.props.auth.user.name }}</p>
                                <button class="text-sm text-[#07304a] font-semibold hover:underline">Change</button>
                            </div>
                        </div>
                        <div class="pt-6 border-t border-slate-100">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</label>
                            <p class="text-sm font-semibold text-slate-900 mt-1">{{ $page.props.auth.user.email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Invoice & Quotation Configuration -->
                <div class="bg-white rounded-2xl border border-slate-100 p-8 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900 mb-6">Document Configuration</h3>
                    <form @submit.prevent="submitProfile" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Invoice Prefix</label>
                            <div class="relative">
                                <Icon icon="si:text-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                <input 
                                    type="text" 
                                    v-model="profileForm.invoice_prefix"
                                    placeholder="e.g. INV"
                                    class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                            </div>
                            <p class="text-xs text-slate-400">Format: <span class="font-semibold">{{ profileForm.invoice_prefix }}/YYMMDD/001</span></p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Quotation Prefix</label>
                            <div class="relative">
                                <Icon icon="si:text-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                <input 
                                    type="text" 
                                    v-model="profileForm.quotation_prefix"
                                    placeholder="e.g. QUO"
                                    class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                            </div>
                            <p class="text-xs text-slate-400">Format: <span class="font-semibold">{{ profileForm.quotation_prefix }}/YYMMDD/001</span></p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Currency</label>
                            <div class="relative">
                                <Icon icon="si:money-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                <select
                                    v-model="profileForm.currency"
                                    class="w-full appearance-none bg-slate-50 border-none rounded-xl pl-12 pr-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                                    <option value="IDR">IDR</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="SGD">SGD</option>
                                </select>
                            </div>
                            <p class="text-xs text-slate-400">Dipakai untuk format harga produk.</p>
                        </div>

                        <div class="pt-4">
                            <button 
                                type="submit" 
                                :disabled="profileForm.processing"
                                class="flex items-center gap-2 rounded-xl bg-[#07304a] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#07304a]/20 transition-all hover:bg-[#002d66] active:scale-95 disabled:opacity-50"
                            >
                                <Icon icon="si:archive-line" :width="18" :height="18"  />
                                <span>{{ profileForm.processing ? 'Saving...' : 'Save Changes' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Company Tab Content -->
            <div v-show="activeTab === 'company'" class="bg-white rounded-2xl border border-slate-100 p-8 shadow-sm max-w-4xl">
                <h3 class="text-lg font-semibold text-slate-900 mb-6">Company Information</h3>
                <p class="text-sm text-slate-500 mb-8">This information will appear on your invoices and official documents.</p>

                <form @submit.prevent="submitCompany" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Company Logo</label>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="h-20 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 md:w-72">
                                    <img
                                        v-if="companyLogoPreview"
                                        :src="companyLogoPreview"
                                        alt="Company logo preview"
                                        class="h-full w-full object-contain object-left"
                                    >
                                    <img
                                        v-else
                                        src="/img/logo/logo_colorful.png"
                                        alt="Default logo preview"
                                        class="h-full w-full object-contain object-left opacity-70"
                                    >
                                </div>
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-[#07304a] ring-1 ring-slate-200 transition hover:bg-slate-100">
                                    <Icon icon="si:image-line" :width="16" :height="16" />
                                    <span>Change Logo</span>
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                                        class="hidden"
                                        @change="onCompanyLogoChange"
                                    >
                                </label>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400">Accepted format: JPG, PNG, WEBP, SVG. Max size 2MB.</p>
                        <p v-if="companyForm.errors.company_logo" class="text-xs font-semibold text-rose-500">
                            {{ companyForm.errors.company_logo }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Company Name -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Company Name</label>
                            <div class="relative">
                                <Icon icon="si:building-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                <input 
                                    type="text" 
                                    v-model="companyForm.company_name"
                                    placeholder="e.g. PT Solusi Usaha Adijaya"
                                    class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                            </div>
                        </div>

                        <!-- Company Address -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Company Address</label>
                            <div class="relative">
                                <Icon icon="si:pin-line" :width="18" :height="18" class="absolute left-4 top-4 text-slate-400"  />
                                <textarea 
                                    v-model="companyForm.company_address"
                                    rows="3"
                                    placeholder="Full company address"
                                    class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none resize-none"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Phone Number</label>
                            <div class="relative">
                                <Icon icon="si:phone-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                <input 
                                    type="text" 
                                    v-model="companyForm.company_phone"
                                    placeholder="e.g. (+62) 851 8344 0300"
                                    class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Email Address</label>
                            <div class="relative">
                                <Icon icon="si:mail-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                <input 
                                    type="email" 
                                    v-model="companyForm.company_email"
                                    placeholder="e.g. info@konsulin.id"
                                    class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                            </div>
                        </div>

                        <!-- Website -->
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Website</label>
                            <div class="relative">
                                <Icon icon="si:globe-detailed-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                <input 
                                    type="url" 
                                    v-model="companyForm.company_website"
                                    placeholder="e.g. https://konsulin.id"
                                    class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                            </div>
                        </div>

                        <!-- Tax ID -->
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tax ID / Registration Number</label>
                            <div class="relative">
                                <Icon icon="si:quote-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                <input 
                                    type="text" 
                                    v-model="companyForm.company_tax_id"
                                    placeholder="e.g. NPWP"
                                    class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit" 
                            :disabled="companyForm.processing"
                            class="flex items-center gap-2 rounded-xl bg-[#07304a] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#07304a]/20 transition-all hover:bg-[#002d66] active:scale-95 disabled:opacity-50"
                        >
                            <Icon icon="si:archive-line" :width="18" :height="18"  />
                            <span>{{ companyForm.processing ? 'Saving...' : 'Save Company Info' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Profile Tab Content -->
            <div v-show="activeTab === 'profile'" class="max-w-4xl">
                <div class="bg-white rounded-2xl border border-slate-100 p-8 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Profile Settings Menu</h3>
                    <p class="text-sm text-slate-500 mb-8">Quick access to account and subscription related settings.</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Link
                            :href="route('profile.bank-accounts.index')"
                            class="group rounded-xl border border-slate-100 bg-slate-50 p-5 transition-all hover:border-[#07304a]/20 hover:bg-white hover:shadow-sm"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#07304a] ring-1 ring-slate-100">
                                <Icon icon="si:building-line" :width="18" :height="18" />
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-900">Bank Accounts</p>
                            <p class="mt-1 text-xs text-slate-500">Manage account number and payout destination.</p>
                        </Link>

                        <Link
                            :href="route('settings.billing')"
                            class="group rounded-xl border border-slate-100 bg-slate-50 p-5 transition-all hover:border-[#07304a]/20 hover:bg-white hover:shadow-sm"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#07304a] ring-1 ring-slate-100">
                                <Icon icon="si:credit-card-line" :width="18" :height="18" />
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-900">Billing</p>
                            <p class="mt-1 text-xs text-slate-500">Review plan, payment status, and receipts.</p>
                        </Link>

                        <Link
                            :href="route('settings.reset-password')"
                            class="group rounded-xl border border-slate-100 bg-slate-50 p-5 transition-all hover:border-[#07304a]/20 hover:bg-white hover:shadow-sm"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#07304a] ring-1 ring-slate-100">
                                <Icon icon="si:shield-line" :width="18" :height="18" />
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-900">Reset Password</p>
                            <p class="mt-1 text-xs text-slate-500">Update password and secure account access.</p>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Taxes Tab Content -->
            <div v-show="activeTab === 'taxes'">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Add/Edit Tax Form -->
                    <div class="bg-white rounded-2xl border border-slate-100 p-8 shadow-sm">
                        <h3 class="text-lg font-semibold text-slate-900 mb-6">
                            {{ editingTax ? 'Edit Tax' : 'Add New Tax' }}
                        </h3>
                        <form @submit.prevent="submitTax" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tax Name</label>
                                <input 
                                    type="text" 
                                    v-model="taxForm.name"
                                    placeholder="e.g. VAT, PPN, WHT"
                                    class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                    required
                                >
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</label>
                                <select 
                                    v-model="taxForm.type"
                                    class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                                    <option value="add">Add (Penambah)</option>
                                    <option value="subtract">Subtract (Pengurang)</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rate (%)</label>
                                <input 
                                    type="number" 
                                    v-model="taxForm.rate"
                                    placeholder="e.g. 10"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                    required
                                >
                            </div>

                            <div class="flex gap-3">
                                <button 
                                    type="submit" 
                                    :disabled="taxForm.processing"
                                    class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-[#07304a] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#07304a]/20 transition-all hover:bg-[#002d66] active:scale-95 disabled:opacity-50"
                                >
                                    <Icon icon="si:archive-line" :width="18" :height="18"  />
                                    <span>{{ editingTax ? 'Update' : 'Add Tax' }}</span>
                                </button>
                                <button 
                                    v-if="editingTax"
                                    type="button"
                                    @click="cancelEdit"
                                    class="px-6 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tax List -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="p-8 border-b border-slate-100">
                            <h3 class="text-lg font-semibold text-slate-900">Available Taxes</h3>
                            <p class="text-sm text-slate-500 mt-1">Manage taxes that will be available when creating invoices and quotations.</p>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div v-if="taxes.length === 0" class="p-12 text-center">
                                <Icon icon="si:checklist-line" :width="48" :height="48" class="mx-auto text-slate-300 mb-4"  />
                                <p class="text-sm font-semibold text-slate-900">No taxes configured</p>
                                <p class="text-xs text-slate-400 mt-1">Add your first tax to get started.</p>
                            </div>
                            <div 
                                v-for="tax in taxes" 
                                :key="tax.id"
                                class="p-6 hover:bg-slate-50 transition-all group"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h4 class="text-sm font-semibold text-slate-900">{{ tax.name }}</h4>
                                            <span 
                                                class="px-2 py-0.5 rounded-md text-xs font-semibold"
                                                :class="tax.type === 'add' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                                            >
                                                {{ tax.type === 'add' ? 'Penambah' : 'Pengurang' }}
                                            </span>
                                        </div>
                                        <p class="text-2xl font-bold text-[#07304a]">{{ tax.rate }}%</p>
                                    </div>
                                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button 
                                            @click="editTax(tax)"
                                            class="px-4 py-2 text-xs font-semibold text-[#07304a] hover:bg-[#07304a]/10 rounded-lg transition-all"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            @click="deleteTax(tax)"
                                            class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-all"
                                        >
                                            <Icon icon="si:bin-line" :width="18" :height="18"  />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
