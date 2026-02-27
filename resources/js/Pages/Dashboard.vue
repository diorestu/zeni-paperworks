<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

import { Icon } from '@iconify/vue';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
);

const props = defineProps({
    range: Object,
    kpis: Object,
    chart: Object,
    period: Object,
    verification: Object,
});

const formatCurrency = (value) =>
    `Rp${Number(value || 0).toLocaleString('id-ID')}`;
const formatPercent = (value) => `${Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })}%`;

const dashboardStats = computed(() => [
    {
        title: 'Rata-rata per Invoice',
        value: formatCurrency(props.kpis?.average_invoice),
        hint: 'Total invoice dibagi jumlah invoice.',
    },
    {
        title: 'Collection Rate',
        value: formatPercent(props.kpis?.collection_rate),
        hint: 'Persentase nilai invoice yang sudah terbayar.',
    },
    {
        title: 'Invoice Paid',
        value: Number(props.kpis?.paid_count || 0).toLocaleString('id-ID'),
        hint: 'Jumlah invoice berstatus paid.',
    },
    {
        title: 'Invoice Draft',
        value: Number(props.kpis?.draft_count || 0).toLocaleString('id-ID'),
        hint: 'Jumlah invoice yang masih draft.',
    },
]);

const selectedPeriod = ref(Number(props.period?.selected || 12));
const periodOptions = computed(() => (props.period?.options || [3, 6, 12, 24]));
const periodLabel = computed(() => `${selectedPeriod.value} Bulan`);

watch(
    () => props.period?.selected,
    (value) => {
        if (value) {
            selectedPeriod.value = Number(value);
        }
    }
);

const updatePeriod = () => {
    router.get(
        route('dashboard'),
        { period: selectedPeriod.value },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['range', 'kpis', 'chart', 'period', 'verification'],
        }
    );
};

// Live data from backend
const barData = computed(() => {
    return {
        labels: props.chart?.labels || [],
        datasets: [
            {
                label: 'Total created',
                backgroundColor: '#3b82f6',
                data: props.chart?.created || [],
                borderRadius: 4,
                barThickness: 18,
            },
            {
                label: 'Total paid',
                backgroundColor: '#22c55e',
                data: props.chart?.paid || [],
                borderRadius: 4,
                barThickness: 18,
            },
            {
                label: 'Outstanding / Overdue',
                backgroundColor: '#ef4444',
                data: props.chart?.overdue || [],
                borderRadius: 4,
                barThickness: 18,
            },
        ],
    };
});

const barOptions = computed(() => {
    const series = [
        ...(props.chart?.created || []),
        ...(props.chart?.paid || []),
        ...(props.chart?.overdue || []),
    ].map((value) => Number(value || 0));
    const maxData = Math.max(0, ...series);
    const suggestedMax = Math.ceil((maxData || 1000000) * 1.15);

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                enabled: true,
                backgroundColor: '#ffffff',
                titleColor: '#1e293b',
                bodyColor: '#3b82f6',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                padding: 12,
                displayColors: false,
                callbacks: {
                    label: (context) => `Rp${context.parsed.y.toLocaleString('id-ID')}`,
                },
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                suggestedMax,
                grid: {
                    color: '#f1f5f9',
                    drawBorder: false,
                },
                ticks: {
                    color: '#94a3b8',
                    font: { size: 11, weight: '500' },
                    callback: (value) => value === 0 ? '0' : Number(value).toLocaleString('id-ID'),
                },
                title: {
                    display: true,
                    text: 'Amount',
                    color: '#94a3b8',
                    font: { size: 10, weight: '700' },
                },
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 11, weight: '500' } },
            },
        },
    };
});

let refreshTimer;

onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({ only: ['range', 'kpis', 'chart', 'period', 'verification'], preserveScroll: true });
    }, 300000);
});

onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
    <AppLayout>
        <Head title="Overview" />

        <!-- Header -->
        <div class="mb-8 flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">Overview</h1>
                <p class="text-slate-500 font-normal">Monitor your invoice performance and cashflow overview.</p>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">
                    {{ range?.start }} - {{ range?.end }}
                </p>
                <div class="flex items-center gap-2 rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm">
                    <Icon icon="ri:time-line" :width="16" :height="16" class="text-slate-400"  />
                    <select
                        v-model.number="selectedPeriod"
                        class="bg-transparent text-sm font-semibold text-slate-700 outline-none"
                        @change="updatePeriod"
                    >
                        <option v-for="month in periodOptions" :key="month" :value="month">
                            {{ month }} Bulan
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div v-if="!verification?.email_verified" class="mb-8 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-amber-700">Email belum diverifikasi</p>
                    <p class="text-xs text-amber-700/80">Verifikasi email <span class="font-semibold">{{ verification?.email }}</span> untuk membuka semua fitur.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-700"
                    @click="router.post(route('verification.send'))"
                >
                    <Icon icon="ri:mail-send-line" :width="14" :height="14" />
                    Kirim Ulang Verifikasi
                </button>
            </div>
        </div>

        <!-- Top KPIs -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:gap-8">
            <!-- Total Created -->
            <div class="group relative rounded-[2rem] border border-slate-200 bg-white p-10 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-3xl font-semibold text-slate-900">{{ formatCurrency(kpis?.total_created) }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest leading-none">Total created</span>
                            <span class="group/info relative inline-flex">
                                <Icon icon="ri:information-line" :width="14" :height="14" class="text-slate-300" />
                                <span class="pointer-events-none absolute left-1/2 top-full z-20 mt-2 w-44 -translate-x-1/2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[10px] font-medium normal-case tracking-normal text-slate-600 opacity-0 shadow-lg transition-opacity duration-150 group-hover/info:opacity-100">
                                    Total nominal invoice yang pernah dibuat.
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Paid -->
            <div class="group relative rounded-[2rem] border border-slate-200 bg-white p-10 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-3xl font-semibold text-emerald-500">{{ formatCurrency(kpis?.total_paid) }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest leading-none">Total paid</span>
                            <span class="group/info relative inline-flex">
                                <Icon icon="ri:information-line" :width="14" :height="14" class="text-slate-300" />
                                <span class="pointer-events-none absolute left-1/2 top-full z-20 mt-2 w-44 -translate-x-1/2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[10px] font-medium normal-case tracking-normal text-slate-600 opacity-0 shadow-lg transition-opacity duration-150 group-hover/info:opacity-100">
                                    Total nominal invoice dengan status paid.
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats Bar -->
        <div class="mt-8 rounded-[2rem] border border-slate-200 bg-white px-10 py-8 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                <!-- Outstanding -->
                <div class="pb-6 md:pb-0 md:pr-10">
                    <div class="text-xl font-semibold text-slate-900 mb-1">{{ formatCurrency(kpis?.outstanding) }}</div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Outstanding ({{ kpis?.outstanding_count || 0 }})</span>
                        <span class="group/info relative inline-flex">
                            <Icon icon="ri:information-line" :width="12" :height="12" class="text-slate-300" />
                            <span class="pointer-events-none absolute left-1/2 top-full z-20 mt-2 w-44 -translate-x-1/2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[10px] font-medium normal-case tracking-normal text-slate-600 opacity-0 shadow-lg transition-opacity duration-150 group-hover/info:opacity-100">
                                Invoice terkirim dan belum dibayar.
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Overdue -->
                <div class="py-6 md:py-0 md:px-10">
                    <div class="text-xl font-semibold text-rose-500 mb-1">{{ formatCurrency(kpis?.overdue) }}</div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Overdue ({{ kpis?.overdue_count || 0 }})</span>
                        <span class="group/info relative inline-flex">
                            <Icon icon="ri:information-line" :width="12" :height="12" class="text-slate-300" />
                            <span class="pointer-events-none absolute left-1/2 top-full z-20 mt-2 w-44 -translate-x-1/2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[10px] font-medium normal-case tracking-normal text-slate-600 opacity-0 shadow-lg transition-opacity duration-150 group-hover/info:opacity-100">
                                Invoice lewat jatuh tempo dan belum dibayar.
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Unpaid -->
                <div class="pt-6 md:pt-0 md:pl-10">
                    <div class="text-xl font-semibold text-slate-400 mb-1">{{ formatCurrency(kpis?.unpaid) }}</div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Unpaid ({{ kpis?.unpaid_count || 0 }})</span>
                        <span class="group/info relative inline-flex">
                            <Icon icon="ri:information-line" :width="12" :height="12" class="text-slate-300" />
                            <span class="pointer-events-none absolute left-1/2 top-full z-20 mt-2 w-44 -translate-x-1/2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-[10px] font-medium normal-case tracking-normal text-slate-600 opacity-0 shadow-lg transition-opacity duration-150 group-hover/info:opacity-100">
                                Invoice draft dan sent yang belum paid.
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="mt-8 rounded-[2rem] border border-slate-200 bg-white p-10 shadow-sm">
            <div class="mb-10 flex items-center justify-between">
                <div class="flex items-center gap-8 w-full md:w-auto">
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total Recap</h3>
                </div>
                <div class="hidden md:block">
                    <span class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-2 text-xs font-semibold text-slate-600 shadow-sm">
                        {{ periodLabel }}
                    </span>
                </div>
            </div>

            <div>
                <div class="mb-4">
                    <span class="text-3xl font-semibold text-slate-900">
                        {{ formatCurrency(kpis?.total_created) }}
                    </span>
                </div>
                <!-- Chart Container -->
                <div class="h-80 w-full">
                    <Bar :data="barData" :options="barOptions" />
                </div>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div v-for="item in dashboardStats" :key="item.title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ item.title }}</p>
                    <span class="inline-flex" :title="item.hint">
                        <Icon icon="ri:information-line" :width="14" :height="14" class="text-slate-300" />
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold text-slate-900">{{ item.value }}</p>
            </div>
        </div>
    </AppLayout>
</template>
