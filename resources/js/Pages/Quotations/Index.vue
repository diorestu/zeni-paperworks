<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { IconPlus, IconFileText, IconChevronRight, IconSearch } from '@tabler/icons-vue';

const props = defineProps({
    quotations: Array,
});

const searchQuery = ref('');
const statusFilter = ref('all');

const filteredQuotations = computed(() => {
    let filtered = props.quotations;

    // Filter by status
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(quotation => quotation.status === statusFilter.value);
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(quotation => 
            quotation.quotation_number.toLowerCase().includes(query) ||
            quotation.client.name.toLowerCase().includes(query) ||
            quotation.client.company.toLowerCase().includes(query)
        );
    }

    return filtered;
});

const statusCounts = computed(() => {
    return {
        all: props.quotations.length,
        draft: props.quotations.filter(q => q.status === 'draft').length,
        sent: props.quotations.filter(q => q.status === 'sent').length,
        accepted: props.quotations.filter(q => q.status === 'accepted').length,
        rejected: props.quotations.filter(q => q.status === 'rejected').length,
        expired: props.quotations.filter(q => q.status === 'expired').length,
    };
});
</script>

<template>
    <AppLayout>
        <Head title="Quotations" />

        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">Quotations</h1>
                <p class="text-slate-500 font-normal">Manage and track all your quotations.</p>
            </div>
            <Link 
                :href="route('quotations.create')"
                class="flex items-center gap-2 rounded-xl bg-[#023e8a] px-6 py-4 text-sm font-semibold text-white shadow-xl shadow-[#023e8a]/20 transition-all hover:bg-[#002d66] hover:-translate-y-1 active:scale-95"
            >
                <IconPlus :size="18" :stroke-width="3" />
                <span>Create Quotation</span>
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
                    <span>All Quotations</span>
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
                    @click="statusFilter = 'accepted'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap',
                        statusFilter === 'accepted'
                            ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20'
                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                    ]"
                >
                    <span>Accepted</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="statusFilter === 'accepted' ? 'bg-white/20' : 'bg-slate-200'">
                        {{ statusCounts.accepted }}
                    </span>
                </button>
                <button
                    @click="statusFilter = 'rejected'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap',
                        statusFilter === 'rejected'
                            ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/20'
                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                    ]"
                >
                    <span>Rejected</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="statusFilter === 'rejected' ? 'bg-white/20' : 'bg-slate-200'">
                        {{ statusCounts.rejected }}
                    </span>
                </button>
                <button
                    @click="statusFilter = 'expired'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap',
                        statusFilter === 'expired'
                            ? 'bg-purple-500 text-white shadow-lg shadow-purple-500/20'
                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100'
                    ]"
                >
                    <span>Expired</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="statusFilter === 'expired' ? 'bg-white/20' : 'bg-slate-200'">
                        {{ statusCounts.expired }}
                    </span>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="relative max-w-md">
                <IconSearch :size="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                <input 
                    v-model="searchQuery"
                    type="text" 
                    placeholder="Search quotation number or client..."
                    class="w-full rounded-xl border-none bg-slate-50 pl-12 pr-4 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#023e8a] transition-all outline-none"
                >
            </div>
        </div>

        <!-- Quotations Table -->
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/20 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Quotation</th>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Client</th>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Amount</th>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Date</th>
                        <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Status</th>
                        <th class="px-8 py-5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="quotation in filteredQuotations" :key="quotation.id" class="group hover:bg-slate-50/30 transition-all cursor-pointer" @click="$inertia.visit(route('quotations.show', quotation))">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-[#023e8a] group-hover:bg-white group-hover:shadow-sm transition-all">
                                    <IconFileText :size="18" />
                                </div>
                                <span class="text-sm font-semibold text-slate-900">{{ quotation.quotation_number }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-slate-900">{{ quotation.client.name }}</span>
                                <span class="text-xs font-normal text-slate-400">{{ quotation.client.company }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-sm font-semibold text-slate-900 line-clamp-1">
                            Rp{{ parseFloat(quotation.total).toLocaleString('id-ID') }}
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-slate-900 text-nowrap">{{ new Date(quotation.quotation_date).toLocaleDateString() }}</span>
                                <span class="text-[10px] font-semibold uppercase text-slate-400">Valid until {{ new Date(quotation.valid_until).toLocaleDateString() }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span 
                                class="inline-flex items-center rounded-md px-2.5 py-1 text-[10px] font-semibold uppercase tracking-widest border"
                                :class="{
                                    'bg-emerald-50 text-emerald-600 border-emerald-100': quotation.status === 'accepted',
                                    'bg-amber-50 text-amber-600 border-amber-100': quotation.status === 'sent',
                                    'bg-slate-50 text-slate-500 border-slate-100': quotation.status === 'draft',
                                    'bg-rose-50 text-rose-600 border-rose-100': quotation.status === 'rejected',
                                    'bg-purple-50 text-purple-600 border-purple-100': quotation.status === 'expired',
                                }"
                            >
                                {{ quotation.status }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <IconChevronRight :size="18" class="text-slate-300 group-hover:text-[#023e8a] transition-colors inline-block" />
                        </td>
                    </tr>
                    <tr v-if="filteredQuotations.length === 0">
                        <td colspan="6" class="px-8 py-20 text-center">
                            <div class="flex flex-col items-center gap-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                    <IconFileText :size="32" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">No quotations found</p>
                                    <p class="text-xs font-normal text-slate-400 mt-1">
                                        {{ searchQuery || statusFilter !== 'all' ? 'Try adjusting your filters.' : 'Create your first quotation to get started.' }}
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
