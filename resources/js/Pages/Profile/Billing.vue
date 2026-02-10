<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { IconHistory, IconCircleCheck, IconChevronDown } from '@tabler/icons-vue';

const isYearly = ref(false);

const plans = [
    { 
        badge: 'Try for free',
        name: 'Free', 
        monthly: '0', 
        yearly: '0',
        info: 'No credit card needed',
        color: 'bg-slate-900 text-white',
        docs: ['10 Invoices', '10 Clients'],
        features: ['Community Support', 'Basic Exports', 'Web Dashboard'],
        current: true,
        button: 'Current Plan'
    },
    { 
        badge: 'Basic',
        name: 'Basic', 
        monthly: '49.000', 
        yearly: '37.500',
        info: 'Save 23%',
        color: 'bg-[#023e8a] text-white',
        docs: ['50 Invoices', '50 Clients'],
        features: ['Email Support', 'Advanced Exports', 'Team Invites'],
        current: false,
        button: 'Upgrade Basic'
    },
    { 
        badge: 'Pro',
        name: 'Pro', 
        monthly: '139.000', 
        yearly: '105.000',
        info: 'Save 25%',
        color: 'bg-[#023e8a] text-white',
        docs: ['500 Invoices', '150 Clients'],
        features: ['Priority Support', 'Custom Branding', 'API Access'],
        current: false,
        button: 'Upgrade Pro'
    },
    { 
        badge: 'Enterprise',
        name: 'Professional', 
        monthly: '199.000', 
        yearly: '149.000',
        info: 'Maximum value',
        color: 'bg-[#023e8a] text-white',
        docs: ['Unlimited Invoices', 'Unlimited Clients'],
        features: ['Dedicated Manager', 'SSO Login', 'Audit Logs'],
        current: false,
        button: 'Get started'
    },
];

const getPrice = (plan) => isYearly.value ? plan.yearly : plan.monthly;
const getDayPrice = (plan) => {
    if (plan.name === 'Free') return '0';
    const priceNum = parseInt(getPrice(plan).replace('.', ''));
    return Math.floor(priceNum / 30).toLocaleString('id-ID');
};
</script>

<template>
    <AppLayout>
        <Head title="Billing & Plans" />

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
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
                        class="px-6 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-all"
                    >
                        Monthly
                    </button>
                    <button 
                        @click="isYearly = true"
                        :class="[isYearly ? 'bg-white text-slate-900 shadow-md ring-1 ring-black/[0.05]' : 'text-slate-500 hover:text-slate-700']"
                        class="px-6 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest transition-all flex items-center gap-2"
                    >
                        Yearly
                        <span class="bg-emerald-500 text-white text-[9px] px-1.5 py-0.5 rounded-md">SAVE 25%</span>
                    </button>
                </div>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">
                <div v-for="plan in plans" :key="plan.name" 
                    :class="['relative p-8 rounded-[2.5rem] border transition-all duration-300 flex flex-col', 
                        plan.current ? 'border-[#023e8a] bg-white shadow-2xl shadow-[#023e8a]/5 ring-1 ring-[#023e8a]/5' : 'border-slate-100 bg-white hover:border-slate-200 hover:shadow-xl hover:shadow-slate-200/40']"
                >
                    <div v-if="plan.current" class="absolute -top-3 left-10 bg-[#023e8a] text-white text-[10px] font-bold uppercase tracking-widest px-4 py-1.5 rounded-full shadow-lg shadow-[#023e8a]/20">
                        Current
                    </div>
                    
                    <div class="mb-6">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest block mb-1">
                            {{ plan.badge }}
                        </span>
                        <h3 class="text-3xl font-bold text-slate-900">{{ plan.name }}</h3>
                    </div>

                    <button 
                        :class="['w-full py-4.5 rounded-[1.25rem] text-[13px] font-bold tracking-wide transition-all active:scale-95 mb-4', plan.color]"
                    >
                        {{ plan.button }}
                    </button>

                    <p class="text-[11px] font-semibold text-slate-400 mb-8">{{ plan.info }}</p>

                    <div class="h-px bg-slate-100 w-full mb-8"></div>

                    <div class="mb-10">
                        <div v-if="plan.name !== 'Free'" class="flex items-baseline gap-2 mb-1">
                            <span class="text-2xl font-bold text-slate-900 tracking-tight">Rp{{ getDayPrice(plan) }}</span>
                            <span class="text-slate-400 text-[10px] font-bold uppercase">/ day</span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span :class="[plan.name === 'Free' ? 'text-4xl font-bold text-slate-900' : 'text-slate-400 text-sm font-semibold']">
                                Rp{{ getPrice(plan) }}
                            </span>
                            <span class="text-slate-400 text-[10px] font-semibold uppercase">/ month</span>
                        </div>
                        <p v-if="plan.name === 'Free'" class="text-slate-400 text-[10px] font-semibold uppercase mt-1">forever</p>
                    </div>

                    <div class="h-px bg-slate-100 w-full mb-8"></div>

                    <!-- Documents Section -->
                    <div class="mb-8">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-5 px-1">Documents</p>
                        <ul class="space-y-4">
                            <li v-for="doc in plan.docs" :key="doc" class="flex items-center gap-3 text-xs font-semibold text-slate-600">
                                <IconCircleCheck :size="18" class="text-emerald-500 shrink-0" />
                                {{ doc }}
                            </li>
                        </ul>
                    </div>

                    <!-- Features Section -->
                    <div>
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest mb-5 px-1">Features</p>
                        <ul class="space-y-4">
                            <li v-for="feat in plan.features" :key="feat" class="flex items-center gap-3 text-xs font-semibold text-slate-600">
                                <IconCircleCheck :size="18" class="text-emerald-500 shrink-0" />
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
                            <IconHistory :size="24" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900">Payment History</h2>
                    </div>
                    <button class="text-xs font-semibold text-[#023e8a] uppercase tracking-widest hover:underline px-4 py-2 bg-slate-50 rounded-xl transition-all hover:bg-slate-100 border border-slate-100">Download All</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-10 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Date</th>
                                <th class="px-10 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Amount</th>
                                <th class="px-10 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Status</th>
                                <th class="px-10 py-5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-10 py-6 text-sm font-semibold text-slate-900 text-nowrap">Feb 01, 2026</td>
                                <td class="px-10 py-6 text-sm font-normal text-slate-600">Rp12.000</td>
                                <td class="px-10 py-6">
                                    <span class="bg-emerald-50 text-emerald-600 text-[10px] font-semibold uppercase tracking-widest px-3 py-1 rounded-md border border-emerald-100">Paid</span>
                                </td>
                                <td class="px-10 py-6 text-right">
                                    <button class="text-[10px] font-semibold text-[#023e8a] uppercase tracking-widest bg-slate-50 hover:bg-white border border-slate-100 px-4 py-2 rounded-xl transition-all shadow-sm">Receipt</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
</style>
