<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    kpis: {
        type: Object,
        default: () => ({}),
    },
    plans: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    subscriptionHistory: {
        type: Array,
        default: () => [],
    },
    feedbacks: {
        type: Array,
        default: () => [],
    },
});

const formatCurrency = (value) => `Rp${Number(value || 0).toLocaleString('id-ID')}`;

const statusBadgeClass = (status) => {
    const map = {
        paid: 'bg-emerald-100 text-emerald-700',
        sent: 'bg-sky-100 text-sky-700',
        overdue: 'bg-rose-100 text-rose-700',
        draft: 'bg-slate-100 text-slate-700',
        cancelled: 'bg-amber-100 text-amber-700',
    };

    return map[(status || '').toLowerCase()] || 'bg-slate-100 text-slate-700';
};
</script>

<template>
    <AppLayout>
        <Head title="Super Admin Dashboard" />

        <div class="space-y-8">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Super Admin Dashboard</h1>
                <p class="mt-1 text-sm text-slate-500">Monitoring user growth, plan adoption, subscription history, and feedback.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Registered Users</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ Number(kpis.registered_users || 0).toLocaleString('id-ID') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Paid Plan Users</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ Number(kpis.paid_plan_users || 0).toLocaleString('id-ID') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Paid Revenue</p>
                    <p class="mt-2 text-3xl font-semibold text-emerald-600">{{ formatCurrency(kpis.total_revenue_paid) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Invoiced Revenue</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ formatCurrency(kpis.total_revenue_invoiced) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Subscription Invoices</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ Number(kpis.subscription_invoices_count || 0).toLocaleString('id-ID') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">User Feedback</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ Number(kpis.feedback_count || 0).toLocaleString('id-ID') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
                    <h2 class="text-base font-semibold text-slate-900">Plan Distribution</h2>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="plan in plans"
                            :key="plan.plan_name"
                            class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3"
                        >
                            <span class="text-sm font-semibold text-slate-700">{{ plan.plan_name || 'Free' }}</span>
                            <span class="rounded-lg bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-sm">{{ plan.total }}</span>
                        </div>
                        <p v-if="!plans.length" class="text-sm text-slate-500">No plan data yet.</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-3">
                    <h2 class="text-base font-semibold text-slate-900">Latest Registered Users</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-[11px] uppercase tracking-widest text-slate-400">
                                    <th class="px-3 py-2 font-semibold">Name</th>
                                    <th class="px-3 py-2 font-semibold">Email</th>
                                    <th class="px-3 py-2 font-semibold">Plan</th>
                                    <th class="px-3 py-2 font-semibold">Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users.slice(0, 12)" :key="user.id" class="border-b border-slate-50">
                                    <td class="px-3 py-3 font-semibold text-slate-700">{{ user.name }}</td>
                                    <td class="px-3 py-3 text-slate-500">{{ user.email }}</td>
                                    <td class="px-3 py-3">
                                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ user.plan_name }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-slate-500">{{ user.registered_at || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="!users.length" class="py-4 text-sm text-slate-500">No user data yet.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Subscription History</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-widest text-slate-400">
                                <th class="px-3 py-2 font-semibold">Invoice</th>
                                <th class="px-3 py-2 font-semibold">User</th>
                                <th class="px-3 py-2 font-semibold">Plan</th>
                                <th class="px-3 py-2 font-semibold">Amount</th>
                                <th class="px-3 py-2 font-semibold">Status</th>
                                <th class="px-3 py-2 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in subscriptionHistory.slice(0, 20)" :key="invoice.id" class="border-b border-slate-50">
                                <td class="px-3 py-3 font-semibold text-slate-700">{{ invoice.invoice_number }}</td>
                                <td class="px-3 py-3">
                                    <p class="font-semibold text-slate-700">{{ invoice.user_name || '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ invoice.user_email || '-' }}</p>
                                </td>
                                <td class="px-3 py-3 text-slate-700">{{ invoice.plan_name }}</td>
                                <td class="px-3 py-3 font-semibold text-slate-700">{{ formatCurrency(invoice.amount) }}</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(invoice.status)">
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-slate-500">{{ invoice.invoice_date || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="!subscriptionHistory.length" class="py-4 text-sm text-slate-500">No subscription invoice history yet.</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">User Feedback</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article
                        v-for="feedback in feedbacks.slice(0, 20)"
                        :key="feedback.id"
                        class="rounded-xl border border-slate-100 bg-slate-50 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ feedback.name }}</p>
                                <p class="text-xs text-slate-500">{{ feedback.user_email || feedback.company || '-' }}</p>
                            </div>
                            <span class="rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-amber-600 shadow-sm">{{ feedback.rating }}/5</span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ feedback.message }}</p>
                        <p class="mt-3 text-[11px] font-semibold uppercase tracking-widest text-slate-400">{{ feedback.created_at }}</p>
                    </article>
                    <p v-if="!feedbacks.length" class="text-sm text-slate-500">No user feedback yet.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

