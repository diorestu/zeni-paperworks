<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { IconArrowLeft, IconPrinter, IconFileInvoice, IconSend } from '@tabler/icons-vue';

const props = defineProps({
    quotation: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
};

const formatCurrency = (amount) => {
    return `Rp${parseFloat(amount).toLocaleString('id-ID')}`;
};

const convertToInvoice = () => {
    if (confirm('Are you sure you want to convert this quotation to an invoice?')) {
        router.post(route('quotations.convert', props.quotation));
    }
};
</script>

<template>
    <AppLayout>
        <Head :title="`Quotation ${quotation.quotation_number}`" />

        <div class="max-w-5xl mx-auto">
            <!-- Header Actions -->
            <div class="mb-10 flex items-center justify-between no-print">
                <div class="flex items-center gap-4">
                    <Link :href="route('quotations.index')" class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all border border-slate-100">
                        <IconArrowLeft :size="18" />
                    </Link>
                    <div>
                        <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">Quotation Details</h1>
                        <p class="text-slate-500 font-normal">{{ quotation.quotation_number }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button 
                        @click="window.print()"
                        class="flex items-center gap-2 rounded-xl bg-slate-100 px-6 py-3 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-200 active:scale-95"
                    >
                        <IconPrinter :size="18" />
                        <span>Print</span>
                    </button>

                    <button 
                        v-if="!quotation.invoice_id"
                        @click="convertToInvoice"
                        class="flex items-center gap-2 rounded-xl bg-emerald-500 px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-emerald-500/20 transition-all hover:bg-emerald-600 active:scale-95"
                    >
                        <IconFileInvoice :size="18" />
                        <span>Convert to Invoice</span>
                    </button>

                    <Link 
                        v-if="quotation.invoice_id"
                        :href="route('invoices.show', quotation.invoice)"
                        class="flex items-center gap-2 rounded-xl bg-[#023e8a] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#023e8a]/20 transition-all hover:bg-[#002d66] active:scale-95"
                    >
                        <IconFileInvoice :size="18" />
                        <span>View Invoice</span>
                    </Link>
                </div>
            </div>

            <!-- Quotation Card -->
            <div class="bg-white shadow-2xl overflow-hidden relative border-t-[6px] border-[#023e8a] min-h-[1123px]">
                <div class="p-16 sm:p-24">
                    <!-- Top Branding -->
                    <div class="flex justify-between items-start mb-20">
                         <div class="flex-1">
                            <h1 class="text-6xl font-black text-slate-900 tracking-tighter mb-12" style="font-family: serif;">QUOTATION</h1>
                            <div class="space-y-1">
                                <h2 class="text-3xl font-bold text-slate-900 tracking-tight">PT Solusi Usaha Adijaya</h2>
                                <p class="text-sm text-slate-500 font-normal leading-relaxed max-w-sm mt-4">
                                    Bimasakti Office, Jl. Ahmad Yani Utara No.319, Peguyangan, Denpasar<br>
                                    Utara, Kota Denpasar, Bali 80115<br>
                                    ID<br>
                                    (+62) 851 8344 0300<br>
                                    info@konsulin.id
                                </p>
                            </div>
                         </div>
                         <div class="flex flex-col items-end">
                             <img src="/img/logo/logo_text_blue.png" alt="Company Logo" class="h-32 w-auto object-contain">
                             <p class="text-[10px] font-bold text-[#023e8a] uppercase tracking-[0.2em] mt-2">Accounting | Business | Tax Consulting</p>
                         </div>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-slate-100/80 rounded-xl p-10 grid grid-cols-12 gap-8 mb-16">
                        <div class="col-span-8">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-4">QUOTATION FOR</p>
                            <h3 class="text-3xl font-bold text-slate-800 tracking-tight">{{ quotation.client.name }}</h3>
                            <p class="text-sm text-slate-600 mt-2">{{ quotation.client.company }}</p>
                        </div>
                        <div class="col-span-4 flex flex-col justify-between space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">QUOTATION NUMBER</span>
                                <span class="font-semibold text-slate-900">{{ quotation.quotation_number.split('/').pop() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">DATE</span>
                                <span class="font-semibold text-slate-900">{{ formatDate(quotation.quotation_date) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">VALID UNTIL</span>
                                <span class="font-semibold text-slate-900">{{ formatDate(quotation.valid_until) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mb-16">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-[#000140] text-white">
                                    <th class="px-8 py-6 text-left text-xs font-bold uppercase tracking-widest">Description</th>
                                    <th class="px-8 py-6 text-right text-xs font-bold uppercase tracking-widest">Unit Price</th>
                                    <th class="px-8 py-6 text-center text-xs font-bold uppercase tracking-widest">Qty</th>
                                    <th class="px-8 py-6 text-right text-xs font-bold uppercase tracking-widest">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in quotation.items" :key="item.id" class="align-top">
                                    <td class="px-8 py-8">
                                        <p class="text-lg font-bold text-slate-900 mb-2">{{ item.description.split('\n')[0] }}</p>
                                        <div v-if="item.description.includes('\n')" class="text-sm text-slate-500 font-normal leading-relaxed whitespace-pre-line">
                                            {{ item.description.split('\n').slice(1).join('\n') }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-8 text-right text-lg font-semibold text-slate-900">
                                        {{ formatCurrency(item.unit_price) }}
                                    </td>
                                    <td class="px-8 py-8 text-center text-lg font-semibold text-slate-900">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-8 py-8 text-right text-lg font-bold text-slate-900">
                                        {{ formatCurrency(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div class="flex justify-end mb-16">
                        <div class="w-96 space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-slate-100">
                                <span class="text-sm font-semibold text-slate-500 uppercase tracking-widest">Subtotal</span>
                                <span class="text-xl font-bold text-slate-900">{{ formatCurrency(quotation.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-slate-100">
                                <span class="text-sm font-semibold text-slate-500 uppercase tracking-widest">Tax (0%)</span>
                                <span class="text-xl font-bold text-slate-900">{{ formatCurrency(quotation.tax_total) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-6 bg-[#000140] px-8 rounded-xl">
                                <span class="text-xs font-bold text-white uppercase tracking-widest">Total Amount</span>
                                <span class="text-3xl font-black text-white">{{ formatCurrency(quotation.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="quotation.notes" class="border-t border-slate-100 pt-12">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-4">Terms & Conditions</h4>
                        <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ quotation.notes }}</p>
                    </div>

                    <!-- Status Badge -->
                    <div v-if="quotation.invoice_id" class="mt-12 p-6 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <p class="text-sm font-semibold text-emerald-700">
                            ✓ This quotation has been converted to an invoice
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
