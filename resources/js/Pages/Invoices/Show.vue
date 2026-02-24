<script setup>
import { ref, nextTick, onMounted, onUnmounted, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    invoice: Object,
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
const isSendingEmail = ref(false);
const flashStatus = computed(() => page.props?.flash?.status || '');
const flashError = computed(() => page.props?.flash?.error || '');
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
        paid: 'bg-emerald-100 text-emerald-600',
        overdue: 'bg-rose-100 text-rose-600',
        cancelled: 'bg-slate-200 text-slate-600',
    };
    return colors[status] || 'bg-slate-100 text-slate-600';
};

const variant = ref('classic');
const printVariant = ref(null);
const isPrintMode = ref(false);
const isSwitchingVariant = ref(false);
let variantTimer = null;

const statusForm = useForm({
    status: props.invoice.status,
});
const lastSavedStatus = ref(props.invoice.status);

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
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    url.searchParams.set('variant', variant.value);
    window.open(url.toString(), '_blank', 'noopener,noreferrer');
};

const downloadInvoicePdf = () => {
    const url = route('invoices.download-pdf', {
        invoice: props.invoice.invoice_number,
        variant: variant.value,
    });

    window.open(url, '_blank', 'noopener,noreferrer');
};

const sendInvoiceEmail = () => {
    if (isSendingEmail.value) return;

    isSendingEmail.value = true;
    router.post(route('invoices.send', props.invoice.invoice_number), {}, {
        preserveScroll: true,
        onFinish: () => {
            isSendingEmail.value = false;
        },
    });
};

const updateStatus = () => {
    if (statusForm.status === lastSavedStatus.value) return;

    const nextStatus = statusForm.status;
    statusForm.patch(route('invoices.update', props.invoice.invoice_number), {
        preserveScroll: true,
        onSuccess: () => {
            lastSavedStatus.value = nextStatus;
        },
        onError: () => {
            statusForm.status = lastSavedStatus.value;
        },
    });
};

watch(
    () => props.invoice.status,
    (value) => {
        if (!value) return;
        statusForm.status = value;
        lastSavedStatus.value = value;
    }
);

const setVariant = (nextVariant) => {
    if (variant.value === nextVariant) return;
    isSwitchingVariant.value = true;
    variant.value = nextVariant;

    if (variantTimer) clearTimeout(variantTimer);
    variantTimer = setTimeout(() => {
        isSwitchingVariant.value = false;
    }, 320);
};

onUnmounted(() => {
    if (variantTimer) clearTimeout(variantTimer);
});

onMounted(async () => {
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
        clone.classList.add('invoice-print-standalone');
        clone.style.transform = 'scale(1)';
        clone.style.transformOrigin = 'top left';
        clone.style.width = '210mm';
        clone.style.height = '297mm';
        clone.style.margin = '0';
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
                            <span
                                v-if="invoice.is_down_payment"
                                class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-widest text-amber-700"
                            >
                                Down Payment
                            </span>
                        </div>
                        <p v-if="invoice.parent_invoice" class="mt-2 text-xs font-semibold text-slate-500">
                            Continuation of {{ invoice.parent_invoice.invoice_number }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        v-if="invoice.is_down_payment"
                        :href="route('invoices.create', { source_invoice: invoice.invoice_number })"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all"
                    >
                        <Icon icon="si:add-line" :width="18" :height="18" />
                        <span>Create Continuation</span>
                    </Link>
                    <div class="flex items-center gap-2">
                        <select
                            v-model="statusForm.status"
                            @change="updateStatus"
                            :disabled="statusForm.processing"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-3 pr-10 text-sm font-semibold text-slate-700 shadow-sm outline-none transition-all focus:ring-2 focus:ring-[#07304a] appearance-none"
                            style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2394a3b8%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <span v-if="statusForm.processing" class="text-xs font-medium text-slate-500">Saving...</span>
                    </div>
                </div>
            </div>

            <div v-if="flashStatus" class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700 no-print">
                {{ flashStatus }}
            </div>
            <div v-if="flashError" class="mb-4 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700 no-print">
                {{ flashError }}
            </div>

            <div class="flex items-start gap-10">
                <div class="flex-1 overflow-visible">
                    <!-- Invoice Card -->
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
                    <!-- Top Branding -->
                    <div class="flex justify-between items-start mb-8">
                         <div class="flex-1">
                            <h1 class="text-3xl font-semibold text-slate-900 tracking-tighter mb-6">INVOICE</h1>
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

                    <!-- Info Box (Grey section) -->
                    <div class="bg-slate-100/80 rounded-xl p-6 mb-10">
                        <div v-if="variant === 'modern'" class="grid grid-cols-12 gap-6">
                            <div class="col-span-6">
                                <p class="text-[10px] font-normal uppercase tracking-widest text-slate-500 mb-3">FROM</p>
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
                                <p class="text-[10px] font-normal uppercase tracking-widest text-slate-500 mb-3">BILL TO</p>
                                <h3 class="text-[16px] font-semibold text-slate-800 tracking-tight">{{ invoice.client.name }}</h3>
                                <div class="mt-2 text-[11px] text-slate-600 leading-relaxed">
                                    <div v-if="invoice.client.company">{{ invoice.client.company }}</div>
                                    <div v-if="invoice.client.address">{{ invoice.client.address }}</div>
                                    <div v-if="invoice.client.phone">{{ invoice.client.phone }}</div>
                                    <div v-if="invoice.client.email">{{ invoice.client.email }}</div>
                                    <div v-if="invoice.client.tax_id">Tax ID: {{ invoice.client.tax_id }}</div>
                                </div>
                                <div class="mt-4 space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-normal text-slate-600 uppercase tracking-widest text-[10px]">INVOICE NUMBER</span>
                                        <span class="text-sm font-normal text-slate-900">{{ invoice.invoice_number }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-normal text-slate-600 uppercase tracking-widest text-[10px]">ISSUED</span>
                                        <span class="text-sm font-normal text-slate-900">{{ formatDate(invoice.invoice_date) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-normal text-slate-600 uppercase tracking-widest text-[10px]">DUE DATE</span>
                                        <span class="text-sm font-normal text-slate-900">{{ formatDate(invoice.due_date) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="grid grid-cols-12 gap-6">
                            <div class="col-span-8">
                                <p class="text-[10px] font-normal uppercase tracking-widest text-slate-500 mb-4">BILL TO</p>
                                <h3 class="text-[16px] font-semibold text-slate-800 tracking-tight">{{ invoice.client.name }}</h3>
                                <div class="mt-2 text-[11px] text-slate-600 leading-relaxed">
                                    <div v-if="invoice.client.company">{{ invoice.client.company }}</div>
                                    <div v-if="invoice.client.address">{{ invoice.client.address }}</div>
                                    <div v-if="invoice.client.phone">{{ invoice.client.phone }}</div>
                                    <div v-if="invoice.client.email">{{ invoice.client.email }}</div>
                                    <div v-if="invoice.client.tax_id">Tax ID: {{ invoice.client.tax_id }}</div>
                                </div>
                            </div>
                            <div class="col-span-4 flex flex-col justify-between space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="font-normal text-slate-600 uppercase tracking-widest text-[10px]">INVOICE NUMBER</span>
                                    <span class="text-sm font-normal text-slate-900">{{ invoice.invoice_number }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="font-normal text-slate-600 uppercase tracking-widest text-[10px]">ISSUED</span>
                                    <span class="text-sm font-normal text-slate-900">{{ formatDate(invoice.invoice_date) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="font-normal text-slate-600 uppercase tracking-widest text-[10px]">DUE DATE</span>
                                    <span class="text-sm font-normal text-slate-900">{{ formatDate(invoice.due_date) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mb-10">
                        <table class="w-full">
                            <thead>
                                <tr :class="variantStyles[variant].header">
                                    <th class="px-6 py-3 text-[10px] font-medium uppercase tracking-widest text-left text-white">NO</th>
                                    <th class="px-6 py-3 text-[10px] font-medium uppercase tracking-widest text-left text-white">ITEM</th>
                                    <th class="px-6 py-3 text-[10px] font-normal uppercase tracking-widest text-right text-white">PRICE</th>
                                    <th class="px-6 py-3 text-[10px] font-medium uppercase tracking-widest text-right text-white">QUANTITY</th>
                                    <th class="px-6 py-3 text-[10px] font-medium uppercase tracking-widest text-right text-white">AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, index) in invoice.items" :key="item.id" class="align-top">
                                    <td class="px-6 py-5 text-left text-sm font-normal text-slate-900">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-normal text-slate-900 mb-1">{{ item.description.split('\n')[0] }}</p>
                                        <div v-if="item.description.includes('\n')" class="text-[11px] text-slate-600 font-normal leading-relaxed whitespace-pre-line">
                                            {{ item.description.split('\n').slice(1).join('\n') }}
                                        </div>
                                        <p v-if="item.product?.description" class="mt-1 text-[11px] text-slate-600 font-normal leading-relaxed">
                                            {{ item.product.description }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-5 text-right text-sm font-normal text-slate-900">
                                        {{ formatCurrency(item.unit_price) }}
                                    </td>
                                    <td class="px-6 py-5 text-right text-sm font-medium text-slate-900">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-6 py-5 text-right text-sm font-medium text-slate-900">
                                        {{ formatCurrency(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals and Signature Areas can go here if needed -->
                    <!-- In the image there is no explicit total section visible at the bottom of the snippet, but let's keep it clean -->
                    <div class="flex justify-end mt-12 border-t border-slate-100 pt-6">
                        <div class="w-64 pr-6 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-slate-400 uppercase tracking-widest">Subtotal</span>
                                <span class="text-sm font-medium text-slate-900">{{ formatCurrency(invoice.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-slate-100 pt-4">
                                <span class="text-xs font-semibold text-[#000140] uppercase tracking-widest">Grand Total</span>
                                <span class="text-[17px] font-semibold text-[#000140] tracking-tighter">{{ formatCurrency(invoice.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="invoice.bank_account" class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-[10px] font-medium uppercase tracking-widest text-slate-500 mb-3">Payment Information</p>
                        <p class="text-[11px] text-slate-700 leading-snug">
                            Please transfer payment to <span class="font-medium">{{ invoice.bank_account.bank_name }}</span>,
                            account number <span class="font-medium">{{ invoice.bank_account.account_number }}</span>
                            under name <span class="font-medium">{{ invoice.bank_account.account_name }}</span>.
                        </p>
                        <p class="mt-2 text-[11px] text-slate-700 leading-snug">
                            After payment, please send confirmation to your registered number:
                            <span class="font-medium">{{ invoice.client.phone || '—' }}</span>.
                        </p>
                    </div>

                    <!-- Footer Notes -->
                    <div v-if="invoice.notes" class="mt-12 pt-6 border-t border-slate-50">
                        <p class="text-[10px] font-normal uppercase tracking-widest text-slate-400 mb-4">Terms & Conditions</p>
                        <p class="text-[11px] text-slate-500 leading-relaxed italic">{{ invoice.notes }}</p>
                    </div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="w-64 shrink-0 space-y-3 no-print sticky top-24 self-start">
                    <div class="rounded-xl border border-slate-200 bg-white p-3 space-y-2">
                        <button
                            @click="printInvoice"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all"
                        >
                            <Icon icon="si:file-download-line" :width="16" :height="16" />
                            <span>Print</span>
                        </button>
                        <button
                            @click="downloadInvoicePdf"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all"
                        >
                            <Icon icon="si:file-download-line" :width="16" :height="16" />
                            <span>Download PDF</span>
                        </button>
                        <button
                            @click="sendInvoiceEmail"
                            :disabled="isSendingEmail || !invoice.client?.email"
                            :title="invoice.client?.email ? 'Send invoice email to client' : 'Client email is missing'"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#07304a] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#07304a]/20 transition-all hover:bg-[#002d66] active:scale-95 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <Icon icon="si:mail-line" :width="16" :height="16" />
                            <span>{{ isSendingEmail ? 'Sending...' : 'Send' }}</span>
                        </button>
                    </div>
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Variants</div>
                    <button
                        type="button"
                        @click="setVariant('classic')"
                        class="w-full rounded-xl border px-4 py-3 text-left transition"
                        :class="variant === 'classic' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                    >
                        <div class="text-sm font-semibold">Classic</div>
                        <div class="text-xs mt-1">Balanced layout, clean spacing</div>
                    </button>
                    <button
                        type="button"
                        @click="setVariant('modern')"
                        class="w-full rounded-xl border px-4 py-3 text-left transition"
                        :class="variant === 'modern' ? 'border-slate-900 text-slate-900' : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                    >
                        <div class="text-sm font-semibold">Modern Edge</div>
                        <div class="text-xs mt-1">Bold header, high contrast</div>
                    </button>
                    <button
                        type="button"
                        @click="setVariant('minimal')"
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
        width: 210mm !important;
        height: 297mm !important;
        transform: scale(1) !important;
        transform-origin: top left !important;
        overflow: hidden !important;
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
