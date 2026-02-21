<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Autocomplete from '@/Components/Autocomplete.vue';
import TaxSelector from '@/Components/TaxSelector.vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    clients: Array,
    products: Array,
    taxes: Array,
    bankAccounts: Array,
    nextInvoiceNumber: String,
    sourceInvoice: {
        type: Object,
        default: null,
    },
});

const showTaxSelector = ref(false);
const selectedTaxIds = ref([]);
const showAddClient = ref(false);
const showAddProduct = ref(false);
const showAddBankAccount = ref(false);
const clientSearch = ref('');
const defaultDueDate = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
const formatInitialRupiah = (value) => Number(value ?? 0).toLocaleString('id-ID');
const defaultBankAccountId = props.sourceInvoice?.bank_account_id
    ?? (props.bankAccounts || []).find((bank) => bank.is_default)?.id
    ?? '';

const form = useForm({
    client_id: props.sourceInvoice?.client_id ?? '',
    bank_account_id: defaultBankAccountId,
    parent_invoice_id: props.sourceInvoice?.id ?? null,
    is_down_payment: false,
    invoice_number: props.nextInvoiceNumber,
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: defaultDueDate,
    notes: props.sourceInvoice?.notes ?? '',
    items: props.sourceInvoice?.items?.length
        ? props.sourceInvoice.items.map((item) => ({
              product_id: item.product_id ?? null,
              description: item.description ?? '',
              quantity: item.quantity ?? 1,
              unit_price: Number(item.unit_price ?? 0),
              unit_price_input: formatInitialRupiah(item.unit_price ?? 0),
          }))
        : [{ product_id: null, description: '', quantity: 1, unit_price: 0, unit_price_input: '0' }],
});

const clientForm = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    industry_sector: '',
    address: '',
});

const productForm = useForm({
    name: '',
    sku: '',
    price: 0,
    description: '',
});
const bankAccountForm = useForm({
    bank_name: '',
    account_name: '',
    account_number: '',
    is_default: false,
});

const industryOptions = [
    'Farm',
    'Health',
    'Finance',
    'Hospitality',
    'Retail',
    'Education',
    'Technology',
    'Manufacturing',
    'Construction',
    'Transportation',
    'Real Estate',
    'Professional Services',
    'Government',
    'Non-Profit',
    'Other',
];

const addItem = () => {
    form.items.push({ product_id: null, description: '', quantity: 1, unit_price: 0, unit_price_input: '0' });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const updateItem = (index, product) => {
    form.items[index].product_id = product.id;
    form.items[index].description = product.name;
    const parsedPrice = parseRupiah(product.price);
    form.items[index].unit_price = parsedPrice;
    form.items[index].unit_price_input = formatRupiah(parsedPrice);
};

const parseRupiah = (value) => {
    const digitsOnly = String(value ?? '').replace(/[^\d]/g, '');
    return digitsOnly ? Number(digitsOnly) : 0;
};

const formatRupiah = (value) => {
    const numberValue = Number(value ?? 0);
    return numberValue.toLocaleString('id-ID');
};

const onUnitPriceInput = (index, value) => {
    if (String(value).trim() === '') {
        form.items[index].unit_price = 0;
        form.items[index].unit_price_input = '';
        return;
    }

    const parsed = parseRupiah(value);
    form.items[index].unit_price = parsed;
    form.items[index].unit_price_input = formatRupiah(parsed);
};

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
});

const selectedTaxes = computed(() => {
    return props.taxes.filter(tax => selectedTaxIds.value.includes(tax.id));
});

const taxAmount = computed(() => {
    let amount = 0;
    selectedTaxes.value.forEach(tax => {
        const taxValue = (subtotal.value * tax.rate) / 100;
        if (tax.type === 'add') {
            amount += taxValue;
        } else {
            amount -= taxValue;
        }
    });
    return amount;
});

const total = computed(() => subtotal.value + taxAmount.value);
const totalDigits = computed(() => Math.trunc(Math.abs(total.value || 0)).toString().length);
const previousBankSelection = ref(defaultBankAccountId ? String(defaultBankAccountId) : '');
const bankSelectEl = ref(null);
const bankTom = ref(null);
const datePickerTimeConfig = { enableTimePicker: false };

const bankAccountOptions = computed(() => [
    { id: '__add_bank__', text: '+ Add New Bank Account' },
    ...(props.bankAccounts || []).map((bank) => ({
        id: String(bank.id),
        text: `${bank.bank_name} — ${bank.account_number}`,
    })),
]);

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            items: data.items.map(({ unit_price_input, ...item }) => item),
        }))
        .post(route('invoices.store'));
};

const submitClient = () => {
    clientForm.post(route('clients.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddClient.value = false;
            clientForm.reset();
            router.reload({ only: ['clients'] });
        },
    });
};

const submitProduct = () => {
    productForm.post(route('products.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddProduct.value = false;
            productForm.reset();
            router.reload({ only: ['products'] });
        },
    });
};

const openAddProduct = () => {
    showAddProduct.value = true;
};

const initBankTomSelect = () => {
    if (!bankSelectEl.value) return;

    if (bankTom.value) {
        bankTom.value.destroy();
    }

    bankTom.value = new TomSelect(bankSelectEl.value, {
        create: false,
        maxItems: 1,
        allowEmptyOption: true,
        placeholder: 'Select bank account...',
        searchField: ['text'],
        sortField: [{ field: '$order' }],
    });
    bankTom.value.wrapper.classList.add('bank-tom-wrapper');

    bankTom.value.on('change', (value) => {
        if (value === '__add_bank__') {
            showAddBankAccount.value = true;
            bankTom.value.setValue(previousBankSelection.value || '', true);
            return;
        }

        previousBankSelection.value = value || '';
        form.bank_account_id = value ? Number(value) : '';
    });

    bankTom.value.setValue(form.bank_account_id ? String(form.bank_account_id) : '', true);
};

const closeAddBankModal = () => {
    showAddBankAccount.value = false;
    bankAccountForm.clearErrors();
};

const submitBankAccount = () => {
    bankAccountForm.post(route('profile.bank-accounts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeAddBankModal();
            bankAccountForm.reset();
            router.reload({ only: ['bankAccounts'] });
        },
    });
};

onMounted(() => {
    if (props.sourceInvoice?.client_id) {
        const sourceClient = (props.clients || []).find((client) => client.id === props.sourceInvoice.client_id);
        clientSearch.value = sourceClient?.name ?? '';
    }
    initBankTomSelect();
});

onUnmounted(() => {
    if (bankTom.value) {
        bankTom.value.destroy();
    }
});

watch(
    () => props.bankAccounts,
    async () => {
        if (!form.bank_account_id) {
            const defaultBank = (props.bankAccounts || []).find((bank) => bank.is_default);
            if (defaultBank) {
                form.bank_account_id = defaultBank.id;
                previousBankSelection.value = String(defaultBank.id);
            }
        }
        await nextTick();
        initBankTomSelect();
    },
    { deep: true }
);

</script>

<template>
    <AppLayout>
        <Head title="Create Invoice" />

        <div class="w-full">
            <div v-if="sourceInvoice" class="mb-6 rounded-xl border border-sky-100 bg-sky-50 px-5 py-4 text-sm font-semibold text-sky-700">
                Continuation invoice from <span class="font-bold">{{ sourceInvoice.invoice_number }}</span>.
            </div>
            <div class="mb-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('invoices.index')" class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all border border-slate-100">
                        <Icon icon="si:arrow-left-line" :width="18" :height="18"  />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">New Invoice</h1>
                        <p class="text-slate-500 font-normal">Create a new billable document for your client.</p>
                    </div>
                </div>
                
                <button 
                    @click="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 rounded-xl bg-[#07304a] px-8 py-4 text-sm font-semibold text-white shadow-xl shadow-[#07304a]/20 transition-all hover:bg-[#002d66] active:scale-95 disabled:opacity-50"
                >
                    <Icon icon="si:archive-line" :width="18" :height="18"  />
                    <span>{{ form.processing ? 'Saving...' : 'Save Invoice' }}</span>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Invoice Details -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Basic Info -->
                    <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-xl shadow-slate-200/20">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Client</label>
                                <div class="relative">
                                    <Icon icon="si:user-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10 pointer-events-none"  />
                                    <Autocomplete
                                        v-model="clientSearch"
                                        :items="clients"
                                        item-label="name"
                                        placeholder="Search or select a client..."
                                        :show-default-items="true"
                                        :merge-with-input="true"
                                        @update:modelValue="form.client_id = ''"
                                        @select="(client) => { form.client_id = client.id; clientSearch = client.name; }"
                                        :show-add-option="true"
                                        add-option-label="Add New Client"
                                        @add="showAddClient = true"
                                        input-class="pl-12 pr-4 py-4 rounded-xl text-sm font-semibold"
                                    />
                                </div>
                                <p v-if="form.errors.client_id" class="text-[10px] font-semibold text-rose-500 ml-1">
                                    {{ form.errors.client_id }}
                                </p>
                            </div>
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <label class="block w-3/5 ml-auto text-[10px] font-semibold uppercase tracking-widest text-slate-400">Invoice Number</label>
                                    <div class="relative w-3/5 ml-auto">
                                        <Icon icon="si:text-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"  />
                                        <input
                                            type="text"
                                            v-model="form.invoice_number"
                                            class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                            placeholder="Invoice number"
                                        >
                                    </div>
                                    <p v-if="form.errors.invoice_number" class="w-3/5 ml-auto text-[10px] font-semibold text-rose-500">
                                        {{ form.errors.invoice_number }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <label class="block w-3/5 ml-auto text-[10px] font-semibold uppercase tracking-widest text-slate-400">Invoice Date</label>
                                    <div class="relative w-3/5 ml-auto">
                                        <Icon icon="si:calendar-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none z-10"  />
                                        <VueDatePicker
                                            v-model="form.invoice_date"
                                            model-type="yyyy-MM-dd"
                                            format="dd MMM yyyy"
                                            :enable-time-picker="false"
                                            :time-picker="false"
                                            :time-config="datePickerTimeConfig"
                                            :hide-input-icon="true"
                                            :clearable="false"
                                            auto-apply
                                            input-class-name="invoice-date-input"
                                        />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="block w-3/5 ml-auto text-[10px] font-semibold uppercase tracking-widest text-slate-400">Due Date</label>
                                    <div class="relative w-3/5 ml-auto">
                                        <Icon icon="si:calendar-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none z-10"  />
                                        <VueDatePicker
                                            v-model="form.due_date"
                                            model-type="yyyy-MM-dd"
                                            format="dd MMM yyyy"
                                            :enable-time-picker="false"
                                            :time-picker="false"
                                            :time-config="datePickerTimeConfig"
                                            :hide-input-icon="true"
                                            :clearable="false"
                                            auto-apply
                                            input-class-name="invoice-date-input"
                                        />
                                    </div>
                                </div>
                                <label class="ml-auto flex w-3/5 items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-600">
                                    <input
                                        v-model="form.is_down_payment"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-200 text-[#07304a] focus:ring-[#07304a]"
                                    />
                                    Mark as Down Payment (DP)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-xl shadow-slate-200/20 overflow-hidden">
                        <div class="p-6 space-y-6">
                            <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-[minmax(0,6.9fr)_minmax(0,1.2fr)_minmax(0,2.76fr)_minmax(0,1fr)] gap-4 items-end pb-6 border-b border-slate-50 last:border-0 last:pb-0">
                                <div class="space-y-2">
                                    <label class="text-[9px] font-semibold uppercase tracking-widest text-slate-400">Product / Description</label>
                                    <Autocomplete 
                                        v-model="item.description"
                                        :items="products"
                                        item-label="name"
                                        placeholder="Search product or type description..."
                                        @update:modelValue="item.product_id = null"
                                        @select="(product) => updateItem(index, product)"
                                        :show-add-option="true"
                                        add-option-label="Add Product"
                                        @add="openAddProduct"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[9px] font-semibold uppercase tracking-widest text-slate-400">Qty</label>
                                    <input 
                                        type="number" 
                                        v-model="item.quantity"
                                        class="w-full bg-slate-50 border-none rounded-lg px-3 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none shadow-sm"
                                    >
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[9px] font-semibold uppercase tracking-widest text-slate-400">Unit Price</label>
                                    <input 
                                        type="text"
                                        inputmode="numeric"
                                        :value="item.unit_price_input"
                                        @input="onUnitPriceInput(index, $event.target.value)"
                                        class="w-full bg-slate-50 border-none rounded-lg px-3 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none shadow-sm"
                                    >
                                </div>
                                <div class="flex justify-end pb-1">
                                    <button 
                                        @click="removeItem(index)"
                                        class="p-2 text-rose-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all"
                                    >
                                        <Icon icon="si:bin-line" :width="18" :height="18"  />
                                    </button>
                                </div>
                            </div>

                            <button 
                                @click="addItem"
                                class="w-full py-4 border-2 border-dashed border-slate-100 rounded-[1.5rem] text-xs font-semibold text-slate-400 uppercase tracking-widest hover:bg-slate-50 hover:border-slate-200 transition-all flex items-center justify-center gap-2"
                            >
                                <Icon icon="si:add-line" :width="14" :height="14"  />
                                Add Item
                            </button>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-xl shadow-slate-200/20">
                        <div class="space-y-4">
                            <label class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">
                                <Icon icon="si:ai-note-line" :width="14" :height="14"  />
                                Notes / Terms
                            </label>
                            <textarea 
                                v-model="form.notes"
                                rows="4"
                                placeholder="Thank you for your business..."
                                class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none resize-none"
                            ></textarea>
                            <p class="text-[10px] font-normal text-slate-400 leading-relaxed px-1">
                                These notes will appear at the bottom of the invoice.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right: Summary & Notes -->
                <div class="space-y-8 self-start lg:sticky lg:top-24">
                    <!-- Totals -->
                    <div class="bg-[#07304a] rounded-2xl p-6 text-white shadow-2xl shadow-[#07304a]/30">
                        <div class="mb-5 rounded-xl bg-white/10 px-4 py-3">
                            <div class="text-[9px] font-semibold uppercase tracking-widest text-white/60">Invoice Number</div>
                            <div class="mt-1 text-sm font-semibold tracking-wide">{{ form.invoice_number }}</div>
                        </div>
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-white/50 mb-6">Invoice Summary</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between text-sm font-semibold">
                                <span class="text-white/70">Subtotal</span>
                                <span>Rp{{ subtotal.toLocaleString('id-ID') }}</span>
                            </div>
                            
                            <!-- Tax Button -->
                            <button 
                                @click="showTaxSelector = true"
                                class="w-full flex justify-between items-center text-sm font-semibold py-3 px-4 rounded-xl bg-white/10 hover:bg-white/20 transition-all"
                            >
                                <div class="flex items-center gap-2">
                                    <Icon icon="si:checklist-line" :width="16" :height="16" class="text-white" />
                                    <span class="text-white">Tax</span>
                                    <span v-if="selectedTaxes.length > 0" class="px-2 py-0.5 bg-white/20 rounded-md text-xs">
                                        {{ selectedTaxes.length }}
                                    </span>
                                </div>
                                <span class="text-white">{{ taxAmount >= 0 ? '+' : '' }}Rp{{ taxAmount.toLocaleString('id-ID') }}</span>
                            </button>

                            <!-- Selected Taxes Display -->
                            <div v-if="selectedTaxes.length > 0" class="space-y-2 pt-2">
                                <div 
                                    v-for="tax in selectedTaxes" 
                                    :key="tax.id"
                                    class="flex justify-between text-xs font-semibold text-white pl-4"
                                >
                                    <span>{{ tax.name }} ({{ tax.type === 'add' ? '+' : '-' }}{{ tax.rate }}%)</span>
                                    <span>{{ tax.type === 'add' ? '+' : '-' }}Rp{{ ((subtotal * tax.rate) / 100).toLocaleString('id-ID') }}</span>
                                </div>
                            </div>

                            <div class="pt-4 mt-4 border-t border-white/10">
                                <div class="flex justify-end">
                                    <div class="text-right">
                                        <span class="block text-[10px] font-semibold uppercase tracking-widest text-white/50">Total Amount</span>
                                        <span
                                            class="block font-semibold leading-tight"
                                            :class="totalDigits > 9 ? 'text-2xl' : 'text-3xl'"
                                        >
                                            Rp{{ total.toLocaleString('id-ID') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Selection -->
                    <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-xl shadow-slate-200/20">
                        <div class="space-y-3">
                            <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Bank Account</label>
                            <div class="relative">
                                <Icon icon="si:building-line" :width="18" :height="18" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10" />
                                <Icon icon="si:expand-more-line" :width="16" :height="16" class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 z-10" />
                                <select ref="bankSelectEl" class="bank-tom-select">
                                    <option v-for="option in bankAccountOptions" :key="option.id" :value="option.id">
                                        {{ option.text }}
                                    </option>
                                </select>
                            </div>
                            <p v-if="!bankAccounts?.length" class="text-[10px] font-semibold text-amber-600">
                                No bank accounts found. Add one first.
                            </p>
                            <Link
                                :href="route('profile.bank-accounts.index')"
                                class="inline-flex items-center gap-2 text-[10px] font-semibold uppercase tracking-widest text-[#07304a] hover:underline ml-1"
                            >
                                <Icon icon="si:arrow-right-line" :width="12" :height="12" />
                                Manage Bank Accounts
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Selector Modal -->
        <Teleport to="body">
            <TaxSelector 
                v-if="showTaxSelector"
                :taxes="taxes"
                v-model="selectedTaxIds"
                @close="showTaxSelector = false"
            />
        </Teleport>

        <!-- Add Client Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showAddClient" class="fixed inset-0 z-[90] flex items-center justify-center overflow-y-auto bg-slate-900/40 p-4 md:p-6">
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl max-h-[calc(100vh-7rem)] overflow-y-auto">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-slate-900">Add Client</h3>
                            <button
                                type="button"
                                @click="showAddClient = false"
                                class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                            >
                                <Icon icon="si:close-line" :width="18" :height="18" />
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Name</label>
                                <input
                                    v-model="clientForm.name"
                                    type="text"
                                    class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none"
                                />
                                <p v-if="clientForm.errors.name" class="text-[10px] text-rose-500 ml-1 mt-1">{{ clientForm.errors.name }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Email</label>
                                    <input
                                        v-model="clientForm.email"
                                        type="email"
                                        class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Phone</label>
                                    <input
                                        v-model="clientForm.phone"
                                        type="text"
                                        class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Tax Number</label>
                                <input
                                    v-model="clientForm.company"
                                    type="text"
                                    class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none"
                                    placeholder="Tax number"
                                />
                            </div>
                            <div>
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Industry Sector</label>
                                <select
                                    v-model="clientForm.industry_sector"
                                    class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none"
                                >
                                    <option value="" disabled>Select industry</option>
                                    <option v-for="option in industryOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Address</label>
                                <textarea
                                    v-model="clientForm.address"
                                    rows="3"
                                    class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none resize-none"
                                ></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="showAddClient = false"
                                    class="px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="submitClient"
                                    :disabled="clientForm.processing"
                                    class="px-5 py-2 rounded-xl bg-[#07304a] text-sm font-semibold text-white shadow-lg shadow-[#07304a]/20 hover:bg-[#002d66] disabled:opacity-50"
                                >
                                    {{ clientForm.processing ? 'Saving...' : 'Save Client' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Add Product Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showAddProduct" class="fixed inset-0 z-[90] flex items-center justify-center overflow-y-auto bg-slate-900/40 p-4 md:p-6">
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl max-h-[calc(100vh-7rem)] overflow-y-auto">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-slate-900">Add Product</h3>
                            <button
                                type="button"
                                @click="showAddProduct = false"
                                class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                            >
                                <Icon icon="si:close-line" :width="18" :height="18" />
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Name</label>
                                <input
                                    v-model="productForm.name"
                                    type="text"
                                    class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none"
                                />
                                <p v-if="productForm.errors.name" class="text-[10px] text-rose-500 ml-1 mt-1">{{ productForm.errors.name }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">SKU</label>
                                    <input
                                        v-model="productForm.sku"
                                        type="text"
                                        class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Price</label>
                                    <input
                                        v-model="productForm.price"
                                        type="number"
                                        min="0"
                                        class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Description</label>
                                <textarea
                                    v-model="productForm.description"
                                    rows="3"
                                    class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] outline-none resize-none"
                                ></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="showAddProduct = false"
                                    class="px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="submitProduct"
                                    :disabled="productForm.processing"
                                    class="px-5 py-2 rounded-xl bg-[#07304a] text-sm font-semibold text-white shadow-lg shadow-[#07304a]/20 hover:bg-[#002d66] disabled:opacity-50"
                                >
                                    {{ productForm.processing ? 'Saving...' : 'Save Product' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Add Bank Account Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showAddBankAccount" class="fixed inset-0 z-[90] flex items-center justify-center overflow-y-auto bg-slate-900/40 p-4 md:p-6">
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl max-h-[calc(100vh-7rem)] overflow-y-auto">
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">Add Bank Account</h3>
                            <button type="button" @click="closeAddBankModal" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                <Icon icon="si:close-line" :width="18" :height="18" />
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="ml-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Bank Name</label>
                                <input
                                    v-model="bankAccountForm.bank_name"
                                    type="text"
                                    class="w-full rounded-xl border-none bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 outline-none focus:ring-2 focus:ring-[#07304a]"
                                    placeholder="e.g. BCA"
                                />
                                <p v-if="bankAccountForm.errors.bank_name" class="ml-1 mt-1 text-[10px] text-rose-500">{{ bankAccountForm.errors.bank_name }}</p>
                            </div>
                            <div>
                                <label class="ml-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Account Holder Name</label>
                                <input
                                    v-model="bankAccountForm.account_name"
                                    type="text"
                                    class="w-full rounded-xl border-none bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 outline-none focus:ring-2 focus:ring-[#07304a]"
                                    placeholder="Full name"
                                />
                                <p v-if="bankAccountForm.errors.account_name" class="ml-1 mt-1 text-[10px] text-rose-500">{{ bankAccountForm.errors.account_name }}</p>
                            </div>
                            <div>
                                <label class="ml-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Account Number</label>
                                <input
                                    v-model="bankAccountForm.account_number"
                                    type="text"
                                    class="w-full rounded-xl border-none bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 outline-none focus:ring-2 focus:ring-[#07304a]"
                                    placeholder="0000000000"
                                />
                                <p v-if="bankAccountForm.errors.account_number" class="ml-1 mt-1 text-[10px] text-rose-500">{{ bankAccountForm.errors.account_number }}</p>
                            </div>
                            <label class="flex items-center gap-2 px-1 text-xs font-semibold text-slate-500">
                                <input
                                    v-model="bankAccountForm.is_default"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-200 text-[#07304a] focus:ring-[#07304a]"
                                />
                                Set as default
                            </label>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="closeAddBankModal" class="px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700">
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="submitBankAccount"
                                    :disabled="bankAccountForm.processing"
                                    class="rounded-xl bg-[#07304a] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#07304a]/20 hover:bg-[#002d66] disabled:opacity-50"
                                >
                                    {{ bankAccountForm.processing ? 'Saving...' : 'Save Bank Account' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 180ms ease-out;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .rounded-xl,
.modal-leave-active .rounded-xl,
.modal-enter-active .rounded-2xl,
.modal-leave-active .rounded-2xl {
    transition: transform 180ms ease-out, opacity 180ms ease-out;
}

.modal-enter-from .rounded-xl,
.modal-leave-to .rounded-xl,
.modal-enter-from .rounded-2xl,
.modal-leave-to .rounded-2xl {
    transform: translateY(8px) scale(0.98);
    opacity: 0;
}

:deep(.invoice-date-input) {
    width: 100%;
    border: none;
    border-radius: 8px;
    background: rgb(248 250 252);
    padding: 1rem 1rem 1rem 3rem;
    font-size: 14px;
    font-weight: 600;
    color: rgb(15 23 42);
    box-shadow: 0 0 0 1px rgb(241 245 249);
    outline: none;
    transition: all 200ms ease;
}

:deep(.invoice-date-input:focus) {
    box-shadow: 0 0 0 2px #07304a;
}

:deep(.dp__theme_light) {
    --dp-primary-color: #07304a;
    --dp-primary-text-color: #ffffff;
}

:deep([data-test-id='open-time-picker-btn']) {
    display: none !important;
}

:deep(.bank-tom-wrapper.single .ts-control) {
    display: flex;
    align-items: center;
    min-height: 52px;
    border: none;
    border-radius: 8px;
    background: rgb(248 250 252);
    box-shadow: 0 0 0 1px rgb(241 245 249);
    padding-left: 48px;
    padding-right: 48px;
}

:deep(.bank-tom-wrapper.single.focus .ts-control) {
    box-shadow: 0 0 0 2px #07304a;
}

:deep(.bank-tom-wrapper.single .ts-control input),
:deep(.bank-tom-wrapper.single .item) {
    display: flex;
    align-items: center;
    font-size: 14px;
    font-weight: 600;
    color: rgb(15 23 42);
}

:deep(.bank-tom-wrapper.single .ts-control input) {
    caret-color: transparent;
}

:deep(.bank-tom-wrapper .ts-control .clear-button),
:deep(.bank-tom-wrapper .ts-control .ts-dropdown-toggle) {
    display: none;
}

:deep(.bank-tom-wrapper .ts-dropdown) {
    border: 1px solid rgb(226 232 240);
    border-radius: 8px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

:deep(.bank-tom-wrapper .ts-dropdown .option) {
    display: flex;
    align-items: center;
    min-height: 40px;
    font-size: 13px;
    padding: 10px 12px;
}

:deep(.bank-tom-wrapper .ts-dropdown .option.active) {
    background: rgb(238 244 255);
    color: #07304a;
}
</style>
