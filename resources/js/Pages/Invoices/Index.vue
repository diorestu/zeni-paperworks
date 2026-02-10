<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { IconPlus, IconFileText, IconChevronRight, IconSearch } from '@tabler/icons-vue';

const props = defineProps({
    invoices: Array,
});

const searchQuery = ref('');
const statusFilter = ref('all');

const filteredInvoices = computed(() => {
    let filtered = props.invoices;

    // Filter by status
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(invoice => invoice.status === statusFilter.value);
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(invoice => 
            invoice.invoice_number.toLowerCase().includes(query) ||
            invoice.client.name.toLowerCase().includes(query) ||
            invoice.client.company.toLowerCase().includes(query)
        );
    }

    return filtered;
});

const statusCounts = computed(() => {
    return {
        all: props.invoices.length,
        draft: props.invoices.filter(i => i.status === 'draft').length,
        sent: props.invoices.filter(i => i.status === 'sent').length,
        paid: props.invoices.filter(i => i.status === 'paid').length,
        overdue: props.invoices.filter(i => i.status === 'overdue').length,
    };
});
</script>

<template>
    <AppLayout>
        <Head title="Invoices" />

        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">Invoices</h1>
                <p class="text-slate-500 font-normal">Manage and track all your outgoing invoices.</p>
            </div>
            <Link 
                :href="route('invoices.create')"
                class="flex items-center gap-2 rounded-xl bg-[#023e8a] px-6 py-4 text-sm font-semibold text-white shadow-xl shadow-[#023e8a]/20 transition-all hover:bg-[#002d66] hover:-translate-y-1 active:scale-95"
            >
                <IconPlus :size="18" :stroke-width="3" />
                <span>Create Invoice</span>
            </Link>
        </div>

        <div v-if="$page.props.flash.status" class="mb-8 rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-xs font-semibold text-emerald-600">
            {{ $page.props.flash.status }}
        </div>

        <!-- Filters & Search -->
        <div class="mb-8 space-y-4">
            <!-- Status Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2">
                <button
                    @click="statusFilter = 'all'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap',
                        statusFilter === 'all'
                            ? 'bg-[#023e8a] text-white shadow-lg shadow-[#023e8a]/20'
                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                    ]"
                >
                    <span>All Invoices</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-200'">
                        {{ statusCounts.all }}
                    </span>
                </button>
                <button
                    @click="statusFilter = 'draft'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap',
                        statusFilter === 'draft'
                            ? 'bg-slate-600 text-white shadow-lg shadow-slate-600/20'
                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                    ]"
                >
                    <span>Draft</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="statusFilter === 'draft' ? 'bg-white/20' : 'bg-slate-200'">
                        {{ statusCounts.draft }}
                    </span>
                </button>
                <button
                    @click="statusFilter = 'sent'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap',
                        statusFilter === 'sent'
                            ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20'
                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                    ]"
                >
                    <span>Sent</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="statusFilter === 'sent' ? 'bg-white/20' : 'bg-slate-200'">
                        {{ statusCounts.sent }}
                    </span>
                </button>
                <button
                    @click="statusFilter = 'paid'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap',
                        statusFilter === 'paid'
                            ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20'
                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                    ]"
                >
                    <span>Paid</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="statusFilter === 'paid' ? 'bg-white/20' : 'bg-slate-200'">
                        {{ statusCounts.paid }}
                    </span>
                </button>
                <button
                    @click="statusFilter = 'overdue'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap',
                        statusFilter === 'overdue'
                            ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/20'
                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                    ]"
                >
                    <span>Overdue</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="statusFilter === 'overdue' ? 'bg-white/20' : 'bg-slate-200'">
                        {{ statusCounts.overdue }}
                    </span>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="relative max-w-md">
                <IconSearch :size="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                <input 
                    v-model="searchQuery"
                    type="text" 
                    placeholder="Search invoice number or client..."
                    class="w-full rounded-xl border-none bg-slate-50 pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#023e8a] transition-all outline-none"
                >
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/20 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Invoice</th>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Client</th>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Amount</th>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Date</th>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Status</th>
                        <th class="px-8 py-5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="invoice in filteredInvoices" :key="invoice.id" class="group hover:bg-slate-50/30 transition-all cursor-pointer" @click="$inertia.visit(route('invoices.show', invoice))">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-[#023e8a] group-hover:bg-white group-hover:shadow-sm transition-all">
                                    <IconFileText :size="18" />
                                </div>
                                <span class="text-sm font-semibold text-slate-900">{{ invoice.invoice_number }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-slate-900">{{ invoice.client.name }}</span>
                                <span class="text-xs font-normal text-slate-400">{{ invoice.client.company }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-sm font-semibold text-slate-900 line-clamp-1">
                            Rp{{ parseFloat(invoice.total).toLocaleString('id-ID') }}
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-slate-900 text-nowrap">{{ new Date(invoice.invoice_date).toLocaleDateString() }}</span>
                                <span class="text-[10px] font-semibold uppercase text-slate-400">Due {{ new Date(invoice.due_date).toLocaleDateString() }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span 
                                class="inline-flex items-center rounded-md px-2.5 py-1 text-[10px] font-semibold uppercase tracking-widest border"
                                :class="{
                                    'bg-emerald-50 text-emerald-600 border-emerald-100': invoice.status === 'paid',
                                    'bg-amber-50 text-amber-600 border-amber-100': invoice.status === 'sent',
                                    'bg-slate-50 text-slate-500 border-slate-100': invoice.status === 'draft',
                                    'bg-rose-50 text-rose-600 border-rose-100': invoice.status === 'overdue',
                                }"
                            >
                                {{ invoice.status }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <IconChevronRight :size="18" class="text-slate-300 group-hover:text-[#023e8a] transition-colors inline-block" />
                        </td>
                    </tr>
                    <tr v-if="filteredInvoices.length === 0">
                        <td colspan="6" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center gap-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                    <IconFileText :size="32" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">No invoices found</p>
                                    <p class="text-xs font-normal text-slate-400 mt-1">
                                        {{ searchQuery || statusFilter !== 'all' ? 'Try adjusting your filters.' : 'Create your first invoice to get started.' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
