<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    IconArrowLeft, 
    IconPrinter, 
    IconDownload, 
    IconMail, 
    IconDots,
    IconCalendar,
    IconHash,
    IconUser
} from '@tabler/icons-vue';

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
    };
    return colors[status] || 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <AppLayout>
        <Head :title="`Invoice ${invoice.invoice_number}`" />

        <div class="max-w-5xl mx-auto">
            <!-- Header Actions -->
            <div class="mb-10 flex items-center justify-between no-print">
                <div class="flex items-center gap-4">
                    <Link :href="route('invoices.index')" class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all border border-slate-100">
                        <IconArrowLeft :size="18" />
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
                    <button @click="window.print()" class="flex items-center gap-2 rounded-xl bg-white border border-slate-100 px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 transition-all">
                        <IconPrinter :size="18" />
                        <span>Print</span>
                    </button>
                    <button class="flex items-center gap-2 rounded-xl bg-[#023e8a] px-6 py-3 text-sm font-semibold text-white shadow-xl shadow-[#023e8a]/20 transition-all hover:bg-[#002d66] active:scale-95">
                        <IconMail :size="18" />
                        <span>Send</span>
                    </button>
                </div>
            </div>

            <!-- Invoice Card -->
            <div class="bg-white shadow-2xl overflow-hidden relative border-t-[6px] border-[#000140] min-h-[1123px]"> 
                <div class="p-16 sm:p-24">
                    <!-- Top Branding -->
                    <div class="flex justify-between items-start mb-20">
                         <div class="flex-1">
                            <h1 class="text-6xl font-black text-slate-900 tracking-tighter mb-12" style="font-family: serif;">INVOICE</h1>
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
                             <!-- Optional logo subtitle like in image -->
                             <p class="text-[10px] font-bold text-[#023e8a] uppercase tracking-[0.2em] mt-2">Accounting | Business | Tax Consulting</p>
                         </div>
                    </div>

                    <!-- Info Box (Grey section) -->
                    <div class="bg-slate-100/80 rounded-xl p-10 grid grid-cols-12 gap-8 mb-16">
                        <div class="col-span-8">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-4">BILL TO</p>
                            <h3 class="text-3xl font-bold text-slate-800 tracking-tight">{{ invoice.client.name }}</h3>
                        </div>
                        <div class="col-span-4 flex flex-col justify-between space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">INVOICE NUMBER</span>
                                <span class="font-semibold text-slate-900">{{ invoice.invoice_number.split('/').pop() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">ISSUED</span>
                                <span class="font-semibold text-slate-900">{{ formatDate(invoice.invoice_date) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mb-16">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-[#000140] text-white">
                                    <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-left">ITEM</th>
                                    <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-right">PRICE</th>
                                    <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-center">QUANTITY</th>
                                    <th class="px-8 py-4 text-xs font-bold uppercase tracking-widest text-right">AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in invoice.items" :key="item.id" class="align-top">
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

                    <!-- Totals and Signature Areas can go here if needed -->
                    <!-- In the image there is no explicit total section visible at the bottom of the snippet, but let's keep it clean -->
                    <div class="flex justify-end mt-20 border-t border-slate-100 pt-10">
                        <div class="w-72 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold text-slate-400 uppercase tracking-widest">Subtotal</span>
                                <span class="text-lg font-bold text-slate-900">{{ formatCurrency(invoice.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-slate-100 pt-4">
                                <span class="text-sm font-bold text-[#000140] uppercase tracking-widest">Total Amount</span>
                                <span class="text-3xl font-black text-[#000140] tracking-tighter">{{ formatCurrency(invoice.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Notes -->
                    <div v-if="invoice.notes" class="mt-20 pt-10 border-t border-slate-50">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">Terms & Conditions</p>
                        <p class="text-xs text-slate-500 leading-relaxed italic">{{ invoice.notes }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

</template>
