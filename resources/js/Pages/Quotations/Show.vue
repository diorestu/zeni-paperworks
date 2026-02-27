<script setup>
import { ref, nextTick, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    quotation: Object,
    companyLogoUrl: {
        type: String,
        default: null,
    },
    companyProfile: {
        type: Object,
        default: () => ({}),
    },
});
const page = usePage();
const isFreePlan = computed(() => {
    const planName = page.props?.auth?.user?.plan_name;
    return String(planName || 'Free').toLowerCase() === 'free';
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
        accepted: 'bg-emerald-100 text-emerald-600',
        rejected: 'bg-rose-100 text-rose-600',
        expired: 'bg-orange-100 text-orange-600',
    };
    return colors[status] || 'bg-slate-100 text-slate-600';
};

const shouldShowClientCompany = (client) => {
    const company = String(client?.company || '').trim();
    if (!company) return false;

    const name = String(client?.name || '').trim();
    return company.toLowerCase() !== name.toLowerCase();
};

const variant = ref('classic');
const isDeletingQuotation = ref(false);
const printVariant = ref(null);
const isPrintMode = ref(false);
const isSwitchingVariant = ref(false);
let variantTimer = null;

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

const printQuotation = async () => {
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    url.searchParams.set('variant', variant.value);
    window.open(url.toString(), '_blank', 'noopener,noreferrer');
};

const downloadQuotationPdf = () => {
    const url = route('quotations.download-pdf', {
        quotation: props.quotation.quotation_number,
        variant: variant.value,
    });
    window.open(url, '_blank', 'noopener,noreferrer');
};

const convertToInvoice = () => {
    if (confirm('Are you sure you want to convert this quotation to an invoice?')) {
        router.post(route('quotations.convert', props.quotation));
    }
};

const deleteQuotation = () => {
    if (isDeletingQuotation.value) return;
    if (!confirm(`Delete quotation ${props.quotation.quotation_number}? This action cannot be undone.`)) return;

    isDeletingQuotation.value = true;
    router.delete(route('quotations.destroy', props.quotation.public_id), {
        preserveScroll: true,
        onFinish: () => {
            isDeletingQuotation.value = false;
        },
    });
};

onMounted(async () => {
    const savedVariant = localStorage.getItem('quotation_variant');
    if (savedVariant && ['classic', 'modern', 'minimal'].includes(savedVariant) && !isPrintMode.value) {
        variant.value = savedVariant;
    }

    const params = new URLSearchParams(window.location.search);
    const printMode = params.get('print') === '1';
    isPrintMode.value = printMode;
    const variantFromQuery = params.get('variant');

    if (variantFromQuery && ['classic', 'modern', 'minimal'].includes(variantFromQuery)) {
        variant.value = variantFromQuery;
    }

    if (!printMode) return;

    printVariant.value = variant.value;
    await nextTick();
    setTimeout(() => {
        const source = document.querySelector('.invoice-print');
        if (!source) {
            window.print();
            return;
        }

        const clone = source.cloneNode(true);
        clone.classList.remove('scale-[0.63]', 'origin-top-left', 'invoice-print');
        clone.classList.add('invoice-print-standalone');
        clone.style.transform = 'none';
        clone.style.width = '210mm';
        clone.style.height = '297mm';
        clone.style.margin = '0';
        clone.style.padding = '0';
        clone.style.position = 'fixed';
        clone.style.left = '0';
        clone.style.top = '0';

        document.body.classList.add('print-standalone-mode');
        document.body.appendChild(clone);

        window.print();
        setTimeout(() => {
            clone.remove();
            document.body.classList.remove('print-standalone-mode');
            printVariant.value = null;
        }, 50);
    }, 150);
});

const setVariant = (nextVariant) => {
    if (variant.value === nextVariant) return;
    isSwitchingVariant.value = true;
    variant.value = nextVariant;
    localStorage.setItem('quotation_variant', nextVariant);

    if (variantTimer) clearTimeout(variantTimer);
    variantTimer = setTimeout(() => {
        isSwitchingVariant.value = false;
    }, 320);
};

onUnmounted(() => {
    if (variantTimer) clearTimeout(variantTimer);
});
</script>

<template>
    <AppLayout>
        <Head :title="`Quotation ${quotation.quotation_number}`" />

        <div class="max-w-7xl mx-auto" style="font-family: 'Inter', sans-serif;">
            <div class="mb-10 flex items-center justify-between no-print">
                <div class="flex items-center gap-4">
                    <Link :href="route('quotations.index')" class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-100 transition-all border border-slate-100">
                        <Icon icon="ri:arrow-left-line" :width="18" :height="18"  />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">{{ quotation.quotation_number }}</h1>
                            <span :class="['rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-widest', getStatusColor(quotation.status)]">
                                {{ quotation.status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="w-64 shrink-0"></div>
            </div>

            <div class="flex items-start gap-10">
                <div class="flex-1 overflow-visible">
                    <div class="origin-top-left scale-[0.63] invoice-print">
                        <div
                            class="overflow-hidden relative"
                            :class="[variantStyles[variant].card, printVariant ? `print-variant-${printVariant}` : '']"
                            style="width: 210mm; height: 297mm;"
                        >
                            <div v-if="isFreePlan" class="absolute inset-0 z-[1] pointer-events-none flex items-center justify-center">
                                <img src="/img/logo/logo_colorful.png" alt="Watermark" class="h-40 w-auto opacity-[0.08] rotate-[-18deg] select-none">
                            </div>
                            <div v-if="isSwitchingVariant" class="absolute inset-0 z-20 flex items-center justify-center bg-white/65 backdrop-blur-[2px]">
                                <div class="h-11 w-11 animate-spin rounded-full border-4 border-[#07304a] border-t-transparent"></div>
                            </div>
                            <div class="relative z-[2] p-10 sm:p-14">
                                <div class="flex justify-between items-start mb-12">
                                    <div class="flex-1">
                                        <h1 class="text-3xl font-black text-slate-900 tracking-tighter mb-6">QUOTATION</h1>
                                        <div v-if="variant !== 'modern'" class="space-y-1">
                                            <h2 class="text-[16px] font-semibold text-slate-800 tracking-tight">{{ companyProfile?.name || 'Company Name' }}</h2>
                                            <div class="mt-2 text-[11px] text-slate-600 leading-relaxed space-y-0.5">
                                                <p v-if="companyProfile?.address" class="whitespace-pre-line">{{ companyProfile.address }}</p>
                                                <p v-if="companyProfile?.tax_id">Tax ID: {{ companyProfile.tax_id }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <img :src="companyLogoUrl || '/img/logo/logo_colorful.png'" alt="Company Logo" class="h-16 w-auto object-contain">
                                    </div>
                                </div>

                                <div class="bg-slate-100/80 rounded-xl p-6 mb-10">
                                    <div v-if="variant === 'modern'" class="grid grid-cols-12 gap-6">
                                        <div class="col-span-6">
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">FROM</p>
                                            <h3 class="text-[16px] font-semibold text-slate-800 tracking-tight">{{ companyProfile?.name || 'Company Name' }}</h3>
                                            <div class="mt-2 text-[11px] text-slate-600 leading-relaxed space-y-0.5">
                                                <p v-if="companyProfile?.address" class="whitespace-pre-line">{{ companyProfile.address }}</p>
                                                <p v-if="companyProfile?.phone">{{ companyProfile.phone }}</p>
                                                <p v-if="companyProfile?.email">{{ companyProfile.email }}</p>
                                                <p v-if="companyProfile?.website">{{ companyProfile.website }}</p>
                                                <p v-if="companyProfile?.tax_id">Tax ID: {{ companyProfile.tax_id }}</p>
                                            </div>
                                        </div>
                                        <div class="col-span-6">
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">QUOTATION FOR</p>
                                            <h3 class="text-xl font-bold text-slate-800 tracking-tight">{{ quotation.client.name }}</h3>
                                            <div class="mt-2 text-[11px] text-slate-500 leading-relaxed">
                                                <div v-if="shouldShowClientCompany(quotation.client)">{{ quotation.client.company }}</div>
                                                <div v-if="quotation.client.address">{{ quotation.client.address }}</div>
                                                <div v-if="quotation.client.phone">{{ quotation.client.phone }}</div>
                                                <div v-if="quotation.client.email">{{ quotation.client.email }}</div>
                                                <div v-if="quotation.client.tax_id">Tax ID: {{ quotation.client.tax_id }}</div>
                                            </div>
                                            <div class="mt-4 space-y-2">
                                                <div class="flex justify-between items-center">
                                                    <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">QUOTATION NUMBER</span>
                                                    <span class="text-sm font-normal text-slate-900">{{ quotation.quotation_number }}</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">ISSUED</span>
                                                    <span class="text-sm font-normal text-slate-900">{{ formatDate(quotation.quotation_date) }}</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">VALID UNTIL</span>
                                                    <span class="text-sm font-normal text-slate-900">{{ formatDate(quotation.valid_until) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="grid grid-cols-12 gap-6">
                                        <div class="col-span-8">
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-4">QUOTATION FOR</p>
                                            <h3 class="text-xl font-bold text-slate-800 tracking-tight">{{ quotation.client.name }}</h3>
                                            <div class="mt-2 text-[11px] text-slate-500 leading-relaxed">
                                                <div v-if="shouldShowClientCompany(quotation.client)">{{ quotation.client.company }}</div>
                                                <div v-if="quotation.client.address">{{ quotation.client.address }}</div>
                                                <div v-if="quotation.client.phone">{{ quotation.client.phone }}</div>
                                                <div v-if="quotation.client.email">{{ quotation.client.email }}</div>
                                                <div v-if="quotation.client.tax_id">Tax ID: {{ quotation.client.tax_id }}</div>
                                            </div>
                                        </div>
                                        <div class="col-span-4 flex flex-col justify-between space-y-4">
                                            <div class="flex justify-between items-center">
                                                <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">QUOTATION NUMBER</span>
                                                <span class="text-sm font-normal text-slate-900">{{ quotation.quotation_number }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">ISSUED</span>
                                                <span class="text-sm font-normal text-slate-900">{{ formatDate(quotation.quotation_date) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="font-bold text-slate-500 uppercase tracking-widest text-[10px]">VALID UNTIL</span>
                                                <span class="text-sm font-normal text-slate-900">{{ formatDate(quotation.valid_until) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

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
                                            <tr v-for="item in quotation.items" :key="item.id" class="align-top">
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

                                <div class="flex justify-end mt-12 border-t border-slate-100 pt-6">
                                    <div class="w-64 space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Subtotal</span>
                                            <span class="text-sm font-bold text-slate-900">{{ formatCurrency(quotation.subtotal) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tax</span>
                                            <span class="text-sm font-bold text-slate-900">{{ formatCurrency(quotation.tax_total) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center border-t border-slate-100 pt-4">
                                            <span class="text-xs font-bold text-[#000140] uppercase tracking-widest">Total Amount</span>
                                            <span class="text-xl font-black text-[#000140] tracking-tighter">{{ formatCurrency(quotation.total) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="quotation.notes" class="mt-12 pt-6 border-t border-slate-50">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">Terms & Conditions</p>
                                    <p class="text-[11px] text-slate-500 leading-relaxed italic whitespace-pre-line">{{ quotation.notes }}</p>
                                </div>

                                <div v-if="quotation.invoice_id" class="mt-10 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                                    <p class="text-sm font-semibold text-emerald-700">This quotation has been converted to an invoice.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="w-64 shrink-0 space-y-3 no-print sticky top-24 self-start">
                    <div class="rounded-xl border border-slate-200 bg-white p-3 space-y-2">
                        <Link
                            :href="route('quotations.edit', quotation.public_id)"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all"
                        >
                            <Icon icon="ri:edit-line" :width="16" :height="16" />
                            <span>Edit</span>
                        </Link>
                        <button
                            @click="printQuotation"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all"
                        >
                            <Icon icon="ri:file-download-line" :width="16" :height="16" />
                            <span>Print</span>
                        </button>
                        <button
                            @click="downloadQuotationPdf"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all"
                        >
                            <Icon icon="ri:file-text-line" :width="16" :height="16" />
                            <span>Download PDF</span>
                        </button>
                        <button
                            @click="deleteQuotation"
                            :disabled="isDeletingQuotation"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-600 transition-all hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <Icon icon="ri:delete-bin-line" :width="16" :height="16" />
                            <span>{{ isDeletingQuotation ? 'Deleting...' : 'Delete' }}</span>
                        </button>
                        <button
                            v-if="!quotation.invoice_id"
                            @click="convertToInvoice"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#07304a] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#07304a]/20 transition-all hover:bg-[#002d66] active:scale-95"
                        >
                            <Icon icon="ri:file-transfer-line" :width="16" :height="16" />
                            <span>Convert to Invoice</span>
                        </button>
                        <Link
                            v-else
                            :href="route('invoices.show', quotation.invoice)"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#07304a] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#07304a]/20 transition-all hover:bg-[#002d66] active:scale-95"
                        >
                            <Icon icon="ri:file-text-line" :width="16" :height="16" />
                            <span>View Invoice</span>
                        </Link>
                    </div>
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Variants</div>
                    <button
                        type="button"
                        @click="setVariant('classic')"
                        class="w-full rounded-xl border px-4 py-3 text-left transition"
                        :class="variant === 'classic' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                    >
                        <div class="text-sm font-semibold flex items-center gap-2">
                            <Icon icon="ri:layout-2-line" :width="14" :height="14" />
                            Classic
                        </div>
                        <div class="text-xs mt-1">Balanced layout, clean spacing</div>
                    </button>
                    <button
                        type="button"
                        @click="setVariant('modern')"
                        class="w-full rounded-xl border px-4 py-3 text-left transition"
                        :class="variant === 'modern' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                    >
                        <div class="text-sm font-semibold flex items-center gap-2">
                            <Icon icon="ri:shapes-line" :width="14" :height="14" />
                            Modern Edge
                        </div>
                        <div class="text-xs mt-1">Bold header, high contrast</div>
                    </button>
                    <button
                        type="button"
                        @click="setVariant('minimal')"
                        class="w-full rounded-xl border px-4 py-3 text-left transition"
                        :class="variant === 'minimal' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                    >
                        <div class="text-sm font-semibold flex items-center gap-2">
                            <Icon icon="ri:apps-2-line" :width="14" :height="14" />
                            Minimal
                        </div>
                        <div class="text-xs mt-1">Soft borders, airy feel</div>
                    </button>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 0;
    }

    html,
    body {
        margin: 0 !important;
        padding: 0 !important;
        width: 210mm !important;
        height: 297mm !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .no-print {
        display: none !important;
    }

    body {
        background: #fff !important;
    }

    body.print-standalone-mode > *:not(.invoice-print-standalone) {
        display: none !important;
    }

    .invoice-print-standalone,
    .invoice-print-standalone * {
        visibility: visible !important;
    }

    .invoice-print-standalone {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        z-index: 9999 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 210mm !important;
        height: 297mm !important;
        transform: none !important;
        overflow: hidden !important;
    }

    .invoice-print-standalone > div {
        box-shadow: none !important;
        border-radius: 0 !important;
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
