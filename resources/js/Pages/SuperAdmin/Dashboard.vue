<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    kpis: {
        type: Object,
        default: () => ({}),
    },
    plans: {
        type: Array,
        default: () => [],
    },
    packageCatalog: {
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

const packageForm = useForm({
    packages: (props.packageCatalog || []).map((item) => ({
        plan_name: item.plan_name,
        monthly_price: Number(item.monthly_price || 0),
        yearly_price: Number(item.yearly_price || 0),
        invoice_limit: item.invoice_limit ?? null,
    })),
});

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

const verifyUser = (user) => {
    if (user?.email_verified_at) return;

    router.post(route('super-admin.users.verify', user.id), {}, {
        preserveScroll: true,
    });
};

const savePackageCatalog = () => {
    packageForm
        .transform((data) => ({
            packages: data.packages.map((item) => ({
                plan_name: item.plan_name,
                monthly_price: Number(item.monthly_price || 0),
                yearly_price: Number(item.yearly_price || 0),
                invoice_limit: item.invoice_limit === '' || item.invoice_limit === null
                    ? null
                    : Number(item.invoice_limit),
            })),
        }))
        .put(route('super-admin.packages.update'), {
            preserveScroll: true,
        });
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

            <div v-if="$page.props.flash.status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ $page.props.flash.status }}
            </div>
            <div v-if="$page.props.flash.error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                {{ $page.props.flash.error }}
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Package Pricing & Limits</h2>
                        <p class="mt-1 text-xs text-slate-500">Set monthly price, yearly monthly-equivalent price, and invoice limit per package. Leave limit empty for unlimited.</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#07304a] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0a3f61] disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="packageForm.processing"
                        @click="savePackageCatalog"
                    >
                        <Icon icon="ri:save-line" :width="16" :height="16" />
                        {{ packageForm.processing ? 'Saving...' : 'Save Package Config' }}
                    </button>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] uppercase tracking-widest text-slate-400">
                                <th class="px-3 py-2 font-semibold">Package</th>
                                <th class="px-3 py-2 font-semibold">Monthly Price (IDR)</th>
                                <th class="px-3 py-2 font-semibold">Yearly Price (IDR/month)</th>
                                <th class="px-3 py-2 font-semibold">Invoice Limit / Month</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in packageForm.packages" :key="item.plan_name" class="border-b border-slate-50">
                                <td class="px-3 py-3 font-semibold text-slate-700">{{ item.plan_name }}</td>
                                <td class="px-3 py-3">
                                    <input
                                        v-model.number="packageForm.packages[index].monthly_price"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#07304a]"
                                    >
                                </td>
                                <td class="px-3 py-3">
                                    <input
                                        v-model.number="packageForm.packages[index].yearly_price"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#07304a]"
                                    >
                                </td>
                                <td class="px-3 py-3">
                                    <input
                                        v-model="packageForm.packages[index].invoice_limit"
                                        type="number"
                                        min="1"
                                        placeholder="Unlimited"
                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#07304a]"
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="packageForm.errors.packages" class="mt-3 text-xs font-semibold text-rose-600">{{ packageForm.errors.packages }}</p>
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
                                    <th class="px-3 py-2 font-semibold">Verification</th>
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
                                    <td class="px-3 py-3">
                                        <span
                                            v-if="user.email_verified_at"
                                            class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                        >
                                            Verified
                                        </span>
                                        <button
                                            v-else
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white hover:bg-slate-800"
                                            @click="verifyUser(user)"
                                        >
                                            <Icon icon="ri:verified-badge-line" :width="12" :height="12" />
                                            Auto Verify
                                        </button>
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
