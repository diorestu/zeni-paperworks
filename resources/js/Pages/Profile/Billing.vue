<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    currentPlan: {
        type: String,
        default: 'Free',
    },
    paymentHistory: {
        type: Array,
        default: () => [],
    },
    midtransEnabled: {
        type: Boolean,
        default: false,
    },
});

const isYearly = ref(false);
const processingPlan = ref(null);
const flashMessage = ref('');
const errorMessage = ref('');

const basePlans = [
    { 
        badge: 'Starter',
        name: 'Free', 
        monthly: '0', 
        yearly: '0',
        info: 'No credit card required',
        color: 'bg-[#07304a] text-white',
        docs: ['10 Invoices / month', '10 Quotations / month', '10 Clients'],
        features: ['1 User', 'Basic Template', 'Basic Reminders', 'Community Support'],
        button: 'Current Plan'
    },
    { 
        badge: 'Growth',
        name: 'Basic', 
        monthly: '49.000', 
        yearly: '38.000',
        info: 'Save 22%',
        color: 'bg-[#07304a] text-white',
        docs: ['10 Invoices / month', '10 Quotations / month', '100 Clients'],
        features: ['1 User', 'Remove Watermark', 'Recurring Invoices', 'CSV/PDF Export', 'Email Support'],
        button: 'Upgrade Basic'
    },
    { 
        badge: 'Scale',
        name: 'Pro', 
        monthly: '139.000', 
        yearly: '106.000',
        info: 'Save 24%',
        color: 'bg-[#07304a] text-white',
        docs: ['500 Invoices / month', 'Unlimited Quotations', '500 Clients'],
        features: ['Up to 5 Users', 'Approval Workflow', 'Custom Branding', 'API & Webhooks', 'Priority Support'],
        button: 'Upgrade Pro'
    },
    { 
        badge: 'Enterprise',
        name: 'Enterprise', 
        monthly: '199.000', 
        yearly: '151.000',
        info: 'Save 24%',
        color: 'bg-[#07304a] text-white',
        docs: ['Unlimited Invoices', 'Unlimited Quotations', 'Unlimited Clients'],
        features: ['Unlimited Users', 'SSO Login', 'Audit Logs', 'Advanced Permissions', 'Dedicated Manager'],
        button: 'Upgrade Enterprise'
    },
];

const plans = computed(() => basePlans.map((plan) => ({
    ...plan,
    current: plan.name === props.currentPlan,
})));

const getPrice = (plan) => isYearly.value ? plan.yearly : plan.monthly;
const getDayPrice = (plan) => {
    if (plan.name === 'Free') return '0';
    const priceNum = parseInt(getPrice(plan).replaceAll('.', ''), 10);
    return Math.floor(priceNum / 30).toLocaleString('id-ID');
};
const getAnnualTotal = (plan) => {
    if (plan.name === 'Free') return '0';
    const yearlyMonthlyEquivalent = parseInt(plan.yearly.replaceAll('.', ''), 10);
    return (yearlyMonthlyEquivalent * 12).toLocaleString('id-ID');
};

const formatCurrency = (amount) => `Rp${Number(amount).toLocaleString('id-ID')}`;

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const statusClass = (status) => {
    return {
        paid: 'bg-emerald-50 text-emerald-600 border-emerald-100',
        sent: 'bg-amber-50 text-amber-600 border-amber-100',
        overdue: 'bg-rose-50 text-rose-600 border-rose-100',
        draft: 'bg-slate-50 text-slate-600 border-slate-100',
        cancelled: 'bg-slate-100 text-slate-500 border-slate-200',
    }[status] ?? 'bg-slate-50 text-slate-600 border-slate-100';
};

const payPlan = async (plan) => {
    if (plan.current || plan.name === 'Free') {
        return;
    }

    router.visit(route('settings.billing.checkout.page', {
        plan: plan.name,
        billing_cycle: isYearly.value ? 'yearly' : 'monthly',
    }));
};
</script>

<template>
    <AppLayout>
        <Head title="Billing & Plans" />

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div v-if="errorMessage" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                {{ errorMessage }}
            </div>
            <div v-if="flashMessage" class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                {{ flashMessage }}
            </div>

            <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-8">
                <div>
                    <h1 class="text-4xl font-semibold text-slate-900 tracking-tight mb-2">Upgrade your workspace</h1>
                    <p class="text-slate-500 font-normal">Choose the plan that fits your team's growth.</p>
                </div>

                <!-- Billing Toggle -->
                <div class="flex items-center gap-2 bg-slate-100 p-1.5 rounded-2xl border border-slate-200 w-fit">
                    <button 
                        @click="isYearly = false"
                        :class="[!isYearly ? 'bg-white text-slate-900 shadow-md ring-1 ring-black/[0.05]' : 'text-slate-500 hover:text-slate-700']"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-all"
                    >
                        <Icon icon="ri:calendar-line" :width="14" :height="14" />
                        Monthly
                    </button>
                    <button 
                        @click="isYearly = true"
                        :class="[isYearly ? 'bg-white text-slate-900 shadow-md ring-1 ring-black/[0.05]' : 'text-slate-500 hover:text-slate-700']"
                        class="px-6 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-all flex items-center gap-2"
                    >
                        <Icon icon="ri:calendar-2-line" :width="14" :height="14" />
                        Yearly
                        <span class="bg-emerald-500 text-white text-[9px] px-1.5 py-0.5 rounded-md">SAVE 25%</span>
                    </button>
                </div>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">
                <div v-for="plan in plans" :key="plan.name" 
                    :class="['relative p-8 rounded-[2.5rem] border transition-all duration-300 flex flex-col', 
                        plan.current ? 'border-[#07304a] bg-white shadow-2xl shadow-[#07304a]/5 ring-1 ring-[#07304a]/5' : 'border-slate-100 bg-white hover:border-slate-200 hover:shadow-xl hover:shadow-slate-200/40']"
                >
                    <div v-if="plan.current" class="absolute -top-3 left-10 bg-[#07304a] text-white text-[10px] font-bold uppercase tracking-widest px-4 py-1.5 rounded-full shadow-lg shadow-[#07304a]/20">
                        Current
                    </div>
                    
                    <div class="mb-6">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest block mb-1">
                            {{ plan.badge }}
                        </span>
                        <h3 class="text-3xl font-bold text-slate-900">{{ plan.name }}</h3>
                    </div>

                    <button
                        @click="payPlan(plan)"
                        :disabled="plan.current || plan.name === 'Free' || processingPlan === plan.name"
                        :class="['inline-flex w-full items-center justify-center gap-2 py-4.5 rounded-[1.25rem] text-[13px] font-bold tracking-wide transition-all active:scale-95 mb-4', plan.color]"
                    >
                        <Icon icon="ri:wallet-3-line" :width="16" :height="16" />
                        {{ processingPlan === plan.name ? 'Processing...' : (plan.current ? 'Current Plan' : plan.button) }}
                    </button>

                    <p class="text-[11px] font-semibold text-slate-400 mb-8">{{ plan.info }}</p>

                    <div class="h-px bg-slate-100 w-full mb-8"></div>

                    <div class="mb-10">
                        <div class="flex items-baseline gap-2">
                            <span :class="[plan.name === 'Free' ? 'text-4xl font-bold text-slate-900' : (!isYearly ? 'text-3xl font-bold text-slate-900 tracking-tight' : 'text-slate-400 text-sm font-semibold')]">
                                Rp{{ plan.monthly }}
                            </span>
                        </div>
                        <div v-if="plan.name !== 'Free'" class="mt-2 flex items-baseline gap-2">
                            <span class="text-slate-500 text-sm font-semibold">Rp{{ getDayPrice(plan) }}</span>
                            <span class="text-slate-400 text-[10px] font-semibold uppercase">/ day</span>
                        </div>
                        <div v-if="isYearly && plan.name !== 'Free'" class="mt-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Total annually</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">Rp{{ getAnnualTotal(plan) }} / year</p>
                        </div>
                        <p v-if="plan.name === 'Free'" class="text-slate-400 text-[10px] font-semibold uppercase mt-1">forever</p>
                    </div>

                    <div class="h-px bg-slate-100 w-full mb-8"></div>

                    <!-- Documents Section -->
                    <div class="mb-8">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-5 px-1">Documents</p>
                        <ul class="space-y-4">
                            <li v-for="doc in plan.docs" :key="doc" class="flex items-center gap-3 text-xs font-semibold text-slate-600">
                                <Icon icon="ri:checkbox-circle-line" :width="18" :height="18" class="text-emerald-500 shrink-0"  />
                                {{ doc }}
                            </li>
                        </ul>
                    </div>

                    <!-- Features Section -->
                    <div>
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-5 px-1">Features</p>
                        <ul class="space-y-4">
                            <li v-for="feat in plan.features" :key="feat" class="flex items-center gap-3 text-xs font-semibold text-slate-600">
                                <Icon icon="ri:checkbox-circle-line" :width="18" :height="18" class="text-emerald-500 shrink-0"  />
                                {{ feat }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl shadow-slate-200/20 overflow-hidden">
                <div class="p-10 border-b border-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-100">
                            <Icon icon="ri:time-line" :width="24" :height="24"  />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900">Payment History</h2>
                    </div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-widest px-4 py-2 bg-slate-50 rounded-xl border border-slate-100">
                        {{ paymentHistory.length }} Records
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-10 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Date</th>
                                <th class="px-10 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Plan</th>
                                <th class="px-10 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Amount</th>
                                <th class="px-10 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Status</th>
                                <th class="px-10 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Invoice</th>
                                <th class="px-10 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="payment in paymentHistory" :key="payment.id" class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-10 py-6 text-sm font-semibold text-slate-900 text-nowrap">{{ formatDate(payment.invoice_date) }}</td>
                                <td class="px-10 py-6 text-sm font-normal text-slate-600">{{ payment.plan_name }}</td>
                                <td class="px-10 py-6 text-sm font-normal text-slate-600">{{ formatCurrency(payment.amount) }}</td>
                                <td class="px-10 py-6">
                                    <span :class="['text-[10px] font-semibold uppercase tracking-widest px-3 py-1 rounded-md border', statusClass(payment.status)]">{{ payment.status }}</span>
                                </td>
                                <td class="px-10 py-6 text-sm font-semibold text-slate-700 text-nowrap">{{ payment.invoice_number }}</td>
                                <td class="px-10 py-6 text-sm font-semibold text-slate-700 text-nowrap">
                                    <a
                                        v-if="payment.receipt_url"
                                        :href="payment.receipt_url"
                                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50"
                                    >
                                        <Icon icon="ri:file-download-line" :width="14" :height="14" />
                                        Download
                                    </a>
                                    <span v-else class="text-xs text-slate-400">-</span>
                                </td>
                            </tr>
                            <tr v-if="paymentHistory.length === 0">
                                <td colspan="6" class="px-10 py-10 text-center">
                                    <p class="text-sm font-semibold text-slate-900">No payment history yet</p>
                                    <p class="text-xs text-slate-500 mt-1">Auto-generated subscription invoices will appear here.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
