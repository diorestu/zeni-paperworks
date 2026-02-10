<script setup>
import { ref, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    invoice: Object,
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-slate-100 text-slate-600',
        sent: 'bg-sky-100 text-sky-600',
        paid: 'bg-emerald-100 text-emerald-600',
        overdue: 'bg-rose-100 text-rose-600',
        cancelled: 'bg-slate-200 text-slate-600',
    };
    return colors[status] || 'bg-slate-100 text-slate-600';
};

const variant = ref('classic');
const printVariant = ref(null);

const statusForm = useForm({
    status: props.invoice.status,
});

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'sent', label: 'Sent' },
    { value: 'paid', label: 'Paid' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'cancelled', label: 'Cancelled' },
];

const variantStyles = {
    classic: {
        card: 'bg-white shadow-2xl border-t-[6px] border-black',
        header: 'bg-black text-white',
    },
    modern: {
        card: 'bg-white shadow-2xl border-t-[8px] border-slate-900',
        header: 'bg-slate-900 text-white',
    },
    minimal: {
        card: 'bg-white shadow-sm border border-slate-200',
        header: 'bg-white text-slate-900 border-b border-slate-200',
    },
};

const printInvoice = async () => {
    printVariant.value = variant.value;
    await nextTick();
    window.print();
    setTimeout(() => {
        printVariant.value = null;
    }, 0);
};

const updateStatus = () => {
    statusForm.patch(route('invoices.update', props.invoice.invoice_number), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="`Invoice ${invoice.invoice_number}`" />

        <div class="max-w-7xl mx-auto" style="font-family: 'Inter', sans-serif;">
            <!-- Header Actions -->
            <div class="mb-10 flex items-center justify-between no-print">
                <div class="flex items-center gap-4">
                    <Link :href="route('invoices.index')" class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all border border-slate-100">
                        <Icon icon="si:arrow-left-line" :width="18" :height="18"  />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">{{ invoice.invoice_number }}</h1>
                            <span :class="['rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-widest', getStatusColor(invoice.status)]">
                                {{ invoice.status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <select
                            v-model="statusForm.status"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-3 pr-10 text-sm font-semibold text-slate-700 shadow-sm outline-none transition-all focus:ring-2 focus:ring-[#023e8a] appearance-none"
                            style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <button
                            type="button"
                            @click="updateStatus"
                            :disabled="statusForm.processing"
                            class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:opacity-50"
                        >
                            {{ statusForm.processing ? 'Saving...' : 'Update Status' }}
                        </button>
                    </div>
                    <button @click="printInvoice" class="flex items-center gap-2 rounded-xl bg-white border border-slate-100 px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 transition-all">
                        <Icon icon="si:file-download-line" :width="18" :height="18"  />
                        <span>Print</span>
                    </button>
                    <button class="flex items-center gap-2 rounded-xl bg-[#023e8a] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#023e8a]/20 transition-all hover:bg-[#002d66] active:scale-95">
                        <Icon icon="si:mail-line" :width="18" :height="18"  />
                        <span>Send</span>
                    </button>
                </div>
            </div>

            <div class="flex items-start gap-10">
                <div class="flex-1 overflow-visible">
                    <!-- Invoice Card -->
                    <div class="origin-top-left scale-[0.9] invoice-print">
                        <div
                            class="overflow-hidden relative"
                            :class="[variantStyles[variant].card, printVariant ? `print-variant-${printVariant}` : '']"
                            style="width: 210mm; height: 297mm;"
                        > 
                            <div class="p-10 sm:p-14">
                    <!-- Top Branding -->
                    <div class="flex justify-between items-start mb-12">
                         <div class="flex-1">
                            <h1 class="text-3xl font-black text-slate-900 tracking-tighter mb-6">INVOICE</h1>
                            <div v-if="variant !== 'modern'" class="space-y-1">
                                <h2 class="text-xl font-bold text-slate-900 tracking-tight">PT Solusi Usaha Adijaya</h2>
                                <p class="text-[11px] text-slate-500 font-normal leading-relaxed max-w-sm mt-2">
                                    Bimasakti Office, Jl. Ahmad Yani Utara No.319, Peguyangan, Denpasar<br>
                                    Utara, Kota Denpasar, Bali 80115<br>
                                    ID<br>
                                    (+62) 851 8344 0300<br>
                                    info@konsulin.id
                                </p>
                            </div>
                         </div>
                         <div class="flex flex-col items-end">
                             <img src="/img/logo/logo_text_blue.png" alt="Company Logo" class="h-16 w-auto object-contain">
                             <!-- Optional logo subtitle like in image -->
                             <p class="text-[8px] font-bold text-[#023e8a] uppercase tracking-[0.2em] mt-2">Accounting | Business | Tax Consulting</p>
                         </div>
                    </div>

                    <!-- Info Box (Grey section) -->
                    <div class="bg-slate-100/80 rounded-xl p-6 mb-10">
                        <div v-if="variant === 'modern'" class="grid grid-cols-12 gap-6">
                            <div class="col-span-6">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">FROM</p>
                                <h3 class="text-xl font-bold text-slate-900 tracking-tight">PT Solusi Usaha Adijaya</h3>
                                <p class="mt-2 text-[11px] text-slate-500 leading-relaxed">
                                    Bimasakti Office, Jl. Ahmad Yani Utara No.319, Peguyangan, Denpasar<br>
                                    Utara, Kota Denpasar, Bali 80115<br>
                                    ID<br>
                                    (+62) 851 8344 0300<br>
                                    info@konsulin.id
                                </p>
                            </div>
                            <div class="col-span-6">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">BILL TO</p>
                                <h3 class="text-xl font-bold text-slate-800 tracking-tight">{{ invoice.client.name }}</h3>
                                <div class="mt-2 text-[11px] text-slate-500 leading-relaxed">
                                    <div v-if="invoice.client.company">{{ invoice.client.company }}</div>
                                    <div v-if="invoice.client.address">{{ invoice.client.address }}</div>
                                    <div v-if="invoice.client.phone">{{ invoice.client.phone }}</div>
                                    <div v-if="invoice.client.email">{{ invoice.client.email }}</div>
                                    <div v-if="invoice.client.tax_id">Tax ID: {{ invoice.client.tax_id }}</div>
                                </div>
                                <div class="mt-4 space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">INVOICE NUMBER</span>
                                        <span class="text-sm font-semibold text-slate-900">{{ invoice.invoice_number.split('/').pop() }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">ISSUED</span>
                                        <span class="text-sm font-semibold text-slate-900">{{ formatDate(invoice.invoice_date) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="grid grid-cols-12 gap-6">
                            <div class="col-span-8">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-4">BILL TO</p>
                                <h3 class="text-xl font-bold text-slate-800 tracking-tight">{{ invoice.client.name }}</h3>
                                <div class="mt-2 text-[11px] text-slate-500 leading-relaxed">
                                    <div v-if="invoice.client.company">{{ invoice.client.company }}</div>
                                    <div v-if="invoice.client.address">{{ invoice.client.address }}</div>
                                    <div v-if="invoice.client.phone">{{ invoice.client.phone }}</div>
                                    <div v-if="invoice.client.email">{{ invoice.client.email }}</div>
                                    <div v-if="invoice.client.tax_id">Tax ID: {{ invoice.client.tax_id }}</div>
                                </div>
                            </div>
                            <div class="col-span-4 flex flex-col justify-between space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">INVOICE NUMBER</span>
                                    <span class="text-sm font-semibold text-slate-900">{{ invoice.invoice_number.split('/').pop() }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">ISSUED</span>
                                    <span class="text-sm font-semibold text-slate-900">{{ formatDate(invoice.invoice_date) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mb-10">
                        <table class="w-full">
                            <thead>
                                <tr :class="variantStyles[variant].header">
                                    <th class="px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-left">ITEM</th>
                                    <th class="px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-right">PRICE</th>
                                    <th class="px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-center">QUANTITY</th>
                                    <th class="px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-right">AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in invoice.items" :key="item.id" class="align-top">
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-bold text-slate-900 mb-1">{{ item.description.split('\n')[0] }}</p>
                                        <div v-if="item.description.includes('\n')" class="text-[11px] text-slate-500 font-normal leading-relaxed whitespace-pre-line">
                                            {{ item.description.split('\n').slice(1).join('\n') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-right text-sm font-semibold text-slate-900">
                                        {{ formatCurrency(item.unit_price) }}
                                    </td>
                                    <td class="px-6 py-5 text-center text-sm font-semibold text-slate-900">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-6 py-5 text-right text-sm font-bold text-slate-900">
                                        {{ formatCurrency(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals and Signature Areas can go here if needed -->
                    <!-- In the image there is no explicit total section visible at the bottom of the snippet, but let's keep it clean -->
                    <div class="flex justify-end mt-12 border-t border-slate-100 pt-6">
                        <div class="w-64 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Subtotal</span>
                                <span class="text-sm font-bold text-slate-900">{{ formatCurrency(invoice.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-slate-100 pt-4">
                                <span class="text-xs font-bold text-[#000140] uppercase tracking-widest">Total Amount</span>
                                <span class="text-xl font-black text-[#000140] tracking-tighter">{{ formatCurrency(invoice.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Notes -->
                    <div v-if="invoice.notes" class="mt-12 pt-6 border-t border-slate-50">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">Terms & Conditions</p>
                        <p class="text-[11px] text-slate-500 leading-relaxed italic">{{ invoice.notes }}</p>
                    </div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="w-64 shrink-0 space-y-3 no-print sticky top-24 self-start">
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Variants</div>
                    <button
                        type="button"
                        @click="variant = 'classic'"
                        class="w-full rounded-xl border px-4 py-3 text-left transition"
                        :class="variant === 'classic' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                    >
                        <div class="text-sm font-semibold">Classic</div>
                        <div class="text-xs mt-1">Balanced layout, clean spacing</div>
                    </button>
                    <button
                        type="button"
                        @click="variant = 'modern'"
                        class="w-full rounded-xl border px-4 py-3 text-left transition"
                        :class="variant === 'modern' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                    >
                        <div class="text-sm font-semibold">Modern Edge</div>
                        <div class="text-xs mt-1">Bold header, high contrast</div>
                    </button>
                    <button
                        type="button"
                        @click="variant = 'minimal'"
                        class="w-full rounded-xl border px-4 py-3 text-left transition"
                        :class="variant === 'minimal' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                    >
                        <div class="text-sm font-semibold">Minimal</div>
                        <div class="text-xs mt-1">Soft borders, airy feel</div>
                    </button>
                </aside>
            </div>
        </div>
    </AppLayout>

</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }

    :global(body) {
        background: #fff !important;
    }

    :global(body *:not(.invoice-print):not(.invoice-print *)) {
        visibility: hidden !important;
    }

    .invoice-print {
        visibility: visible !important;
        position: absolute;
        left: 0;
        top: 0;
        transform: scale(1) !important;
        transform-origin: top left !important;
    }

    .print-variant-classic {
        border-color: #000 !important;
    }

    .print-variant-classic thead tr {
        background-color: #000 !important;
        color: #fff !important;
    }

    .print-variant-modern {
        border-color: #0f172a !important;
    }

    .print-variant-modern thead tr {
        background-color: #0f172a !important;
        color: #fff !important;
    }

    .print-variant-minimal {
        border-color: #e2e8f0 !important;
    }

    .print-variant-minimal thead tr {
        background-color: #fff !important;
        color: #0f172a !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }
}
</style>
