<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Autocomplete from '@/Components/Autocomplete.vue';
import TaxSelector from '@/Components/TaxSelector.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    clients: Array,
    products: Array,
    taxes: Array,
    nextQuotationNumber: String,
});

const showTaxSelector = ref(false);
const selectedTaxIds = ref([]);

const form = useForm({
    client_id: '',
    quotation_number: props.nextQuotationNumber,
    quotation_date: new Date().toISOString().split('T')[0],
    valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    notes: '',
    items: [
        { product_id: null, description: '', quantity: 1, unit_price: 0 }
    ],
});

const addItem = () => {
    form.items.push({ product_id: null, description: '', quantity: 1, unit_price: 0 });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const updateItem = (index, product) => {
    form.items[index].product_id = product.id;
    form.items[index].description = product.name;
    form.items[index].unit_price = product.price;
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
    form.post(route('quotations.store'));
};
</script>

<template>
    <AppLayout>
        <Head title="Create Quotation" />

        <div class="max-w-5xl mx-auto">
            <div class="mb-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('quotations.index')" class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all border border-slate-100">
                        <Icon icon="si:arrow-left-line" :width="18" :height="18"  />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">New Quotation</h1>
                        <p class="text-slate-500 font-normal">Create a new quotation for your client.</p>
                    </div>
                </div>
                
                <button 
                    @click="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 rounded-xl bg-[#07304a] px-8 py-4 text-sm font-semibold text-white shadow-xl shadow-[#07304a]/20 transition-all hover:bg-[#002d66] active:scale-95 disabled:opacity-50"
                >
                    <Icon icon="si:archive-line" :width="18" :height="18"  />
                    <span>{{ form.processing ? 'Saving...' : 'Save Quotation' }}</span>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Quotation Details -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Basic Info -->
                    <div class="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-xl shadow-slate-200/20">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Client</label>
                                <div class="relative">
                                    <Icon icon="si:user-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                    <select 
                                         v-model="form.client_id"
                                         class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                     >
                                         <option value="" disabled>Select a client</option>
                                         <option v-for="client in clients" :key="client.id" :value="client.id">
                                             {{ client.name }} ({{ client.company }})
                                         </option>
                                     </select>
                                 </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Quotation Number</label>
                                <div class="relative">
                                    <Icon icon="si:text-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                    <input 
                                        type="text" 
                                        v-model="form.quotation_number"
                                        class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                    >
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Quotation Date</label>
                                <div class="relative">
                                    <Icon icon="si:clock-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                    <input 
                                        type="date" 
                                        v-model="form.quotation_date"
                                        class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                    >
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Valid Until</label>
                                <div class="relative">
                                    <Icon icon="si:clock-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"  />
                                    <input 
                                        type="date" 
                                        v-model="form.valid_until"
                                        class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/20 overflow-hidden">
                        <div class="bg-slate-50/50 px-8 py-5 border-b border-slate-50">
                            <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-widest">Line Items</h3>
                        </div>
                        <div class="p-8 space-y-6">
                            <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-12 gap-4 items-end pb-6 border-b border-slate-50 last:border-0 last:pb-0">
                                <div class="col-span-5 space-y-2">
                                    <label class="text-[9px] font-semibold uppercase tracking-widest text-slate-400">Product / Description</label>
                                    <Autocomplete 
                                        v-model="item.description"
                                        :items="products"
                                        item-label="name"
                                        placeholder="Search product or type description..."
                                        @update:modelValue="item.product_id = null"
                                        @select="(product) => updateItem(index, product)"
                                    />
                                </div>
                                <div class="col-span-2 space-y-2">
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
                                        type="number" 
                                        v-model="item.unit_price"
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
                    <div class="bg-[#07304a] rounded-[2.5rem] p-8 text-white shadow-2xl shadow-[#07304a]/30">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-white/50 mb-6">Order Summary</h3>
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

                    <!-- Notes -->
                    <div class="bg-white rounded-[2rem] border border-slate-100 p-8 shadow-xl shadow-slate-200/20">
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
                                These notes will appear at the bottom of the quotation.
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
    </AppLayout>
</template>
