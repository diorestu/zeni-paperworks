<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
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
});

const formatCurrency = (value) =>
    `Rp${Number(value || 0).toLocaleString('id-ID')}`;

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

const barOptions = {
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
            max: 25000,
            grid: {
                color: '#f1f5f9',
                drawBorder: false,
            },
            ticks: {
                stepSize: 5000,
                color: '#94a3b8',
                font: { size: 11, weight: '500' },
                callback: (value) => value === 0 ? '0' : value.toLocaleString('id-ID'),
            },
            title: {
                display: true,
                text: 'Amount',
                color: '#94a3b8',
                font: { size: 10, weight: '700' },
            }
        },
        x: {
            grid: { display: false },
            ticks: { color: '#94a3b8', font: { size: 11, weight: '500' } },
        },
    },
};

let refreshTimer;

onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({ only: ['range', 'kpis', 'chart'], preserveScroll: true });
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
                <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Overview</h1>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">
                    {{ range?.start }} - {{ range?.end }}
                </p>
                <button class="flex items-center gap-2 rounded-xl bg-white border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all">
                    <Icon icon="si:clock-line" :width="16" :height="16" class="text-slate-400"  />
                    <span>Last 12 months</span>
                    <Icon icon="si:expand-more-line" :width="14" :height="14" class="text-slate-400 ml-1"  />
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
                            <Icon icon="si:info-line" :width="14" :height="14" class="text-slate-300"  />
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
                            <Icon icon="si:info-line" :width="14" :height="14" class="text-slate-300"  />
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
                        <Icon icon="si:info-line" :width="12" :height="12" class="text-slate-300"  />
                    </div>
                </div>

                <!-- Overdue -->
                <div class="py-6 md:py-0 md:px-10">
                    <div class="text-xl font-semibold text-rose-500 mb-1">{{ formatCurrency(kpis?.overdue) }}</div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Overdue ({{ kpis?.overdue_count || 0 }})</span>
                        <Icon icon="si:info-line" :width="12" :height="12" class="text-slate-300"  />
                    </div>
                </div>

                <!-- Unpaid -->
                <div class="pt-6 md:pt-0 md:pl-10">
                    <div class="text-xl font-semibold text-slate-400 mb-1">{{ formatCurrency(kpis?.unpaid) }}</div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Unpaid ({{ kpis?.unpaid_count || 0 }})</span>
                        <Icon icon="si:info-line" :width="12" :height="12" class="text-slate-300"  />
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
                    <button class="flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-100 px-4 py-2 text-xs font-semibold text-slate-600 shadow-sm">
                        <span>Monthly</span>
                        <Icon icon="si:expand-more-line" :width="14" :height="14" class="text-slate-400"  />
                    </button>
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
    </AppLayout>
</template>
