<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Autocomplete from '@/Components/Autocomplete.vue';
import TaxSelector from '@/Components/TaxSelector.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    clients: Array,
    products: Array,
    taxes: Array,
    bankAccounts: Array,
    nextInvoiceNumber: String,
});

const showTaxSelector = ref(false);
const selectedTaxIds = ref([]);
const showAddClient = ref(false);
const showAddProduct = ref(false);
const clientSearch = ref('');

const form = useForm({
    client_id: '',
    bank_account_id: '',
    invoice_number: props.nextInvoiceNumber,
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    notes: '',
    items: [
        { product_id: null, description: '', quantity: 1, unit_price: 0, unit_price_input: '0' }
    ],
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

const openAddClient = () => {
    showAddClient.value = true;
};
</script>

<template>
    <AppLayout>
        <Head title="Create Invoice" />

        <div class="w-full">
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
                                        placeholder="Select a client..."
                                        @update:modelValue="form.client_id = ''"
                                        @select="(client) => { form.client_id = client.id; clientSearch = client.name; }"
                                        :show-add-option="true"
                                        add-option-label="Add Client"
                                        @add="openAddClient"
                                        input-class="pl-12 pr-4 py-4 rounded-xl text-sm font-semibold"
                                    />
                                </div>
                            </div>
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <label class="block w-3/5 ml-auto text-[10px] font-semibold uppercase tracking-widest text-slate-400">Invoice Date</label>
                                    <div class="relative w-3/5 ml-auto">
                                        <Icon icon="si:clock-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"  />
                                        <input 
                                            type="date" 
                                            v-model="form.invoice_date"
                                            class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                        >
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="block w-3/5 ml-auto text-[10px] font-semibold uppercase tracking-widest text-slate-400">Due Date</label>
                                    <div class="relative w-3/5 ml-auto">
                                        <Icon icon="si:clock-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"  />
                                        <input 
                                            type="date" 
                                            v-model="form.due_date"
                                            class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-xl shadow-slate-200/20 overflow-hidden">
                        <div class="bg-slate-50/50 px-8 py-5 border-b border-slate-50 flex items-center justify-between">
                            <div></div>
                            <button
                                type="button"
                                @click="showAddProduct = true"
                                class="text-[10px] font-semibold uppercase tracking-widest text-[#07304a] hover:underline"
                            >
                                Add Product
                            </button>
                        </div>
                        <div class="p-6 space-y-6">
                            <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-4 items-end pb-6 border-b border-slate-50 last:border-0 last:pb-0">
                                <div class="col-span-6 space-y-2">
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
                                <div class="col-span-1 space-y-2">
                                    <label class="text-[9px] font-semibold uppercase tracking-widest text-slate-400">Qty</label>
                                    <input 
                                        type="number" 
                                        v-model="item.quantity"
                                        class="w-full bg-slate-50 border-none rounded-lg px-3 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none shadow-sm"
                                    >
                                </div>
                                <div class="col-span-3 space-y-2">
                                    <label class="text-[9px] font-semibold uppercase tracking-widest text-slate-400">Unit Price</label>
                                    <input 
                                        type="text"
                                        inputmode="numeric"
                                        :value="item.unit_price_input"
                                        @input="onUnitPriceInput(index, $event.target.value)"
                                        class="w-full bg-slate-50 border-none rounded-lg px-3 py-3 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none shadow-sm"
                                    >
                                </div>
                                <div class="col-span-1 flex flex-col justify-end text-right pb-3">
                                    <span class="text-xs font-semibold text-slate-900">Rp{{ (item.quantity * item.unit_price).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="col-span-1 flex justify-end pb-1">
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
                </div>

                <!-- Right: Summary & Notes -->
                <div class="space-y-8">
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
                                    <Icon icon="si:checklist-line" :width="16" :height="16"  />
                                    <span class="text-white/70">Tax</span>
                                    <span v-if="selectedTaxes.length > 0" class="px-2 py-0.5 bg-white/20 rounded-md text-xs">
                                        {{ selectedTaxes.length }}
                                    </span>
                                </div>
                                <span>{{ taxAmount >= 0 ? '+' : '' }}Rp{{ taxAmount.toLocaleString('id-ID') }}</span>
                            </button>

                            <!-- Selected Taxes Display -->
                            <div v-if="selectedTaxes.length > 0" class="space-y-2 pt-2">
                                <div 
                                    v-for="tax in selectedTaxes" 
                                    :key="tax.id"
                                    class="flex justify-between text-xs font-semibold text-white/60 pl-4"
                                >
                                    <span>{{ tax.name }} ({{ tax.type === 'add' ? '+' : '-' }}{{ tax.rate }}%)</span>
                                    <span>{{ tax.type === 'add' ? '+' : '-' }}Rp{{ ((subtotal * tax.rate) / 100).toLocaleString('id-ID') }}</span>
                                </div>
                            </div>

                            <div class="pt-4 mt-4 border-t border-white/10">
                                <div class="flex justify-between items-end">
                                    <span class="text-[10px] font-semibold uppercase tracking-widest text-white/50">Total Amount</span>
                                    <span class="text-3xl font-semibold">Rp{{ total.toLocaleString('id-ID') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Selection -->
                    <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-xl shadow-slate-200/20">
                        <div class="space-y-3">
                            <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Bank Account</label>
                            <div class="relative">
                                <Icon icon="si:building-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                                <select
                                    v-model="form.bank_account_id"
                                    class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                >
                                    <option value="" disabled>Select a bank account</option>
                                    <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                                        {{ bank.bank_name }} — {{ bank.account_number }}
                                    </option>
                                </select>
                            </div>
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
            </div>
        </div>

        <!-- Tax Selector Modal -->
        <TaxSelector 
            v-if="showTaxSelector"
            :taxes="taxes"
            v-model="selectedTaxIds"
            @close="showTaxSelector = false"
        />

        <!-- Add Client Modal -->
        <Transition name="modal">
            <div v-if="showAddClient" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-6">
            <div class="w-full max-w-lg rounded-xl bg-white p-8 shadow-2xl">
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

        <!-- Add Product Modal -->
        <Transition name="modal">
            <div v-if="showAddProduct" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-6">
            <div class="w-full max-w-lg rounded-xl bg-white p-8 shadow-2xl">
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

.modal-enter-active .rounded-2xl,
.modal-leave-active .rounded-2xl {
    transition: transform 180ms ease-out, opacity 180ms ease-out;
}

.modal-enter-from .rounded-2xl,
.modal-leave-to .rounded-2xl {
    transform: translateY(8px) scale(0.98);
    opacity: 0;
}
</style>
