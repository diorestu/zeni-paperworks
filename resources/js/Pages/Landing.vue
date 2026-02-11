<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    stats: Object,
    modules: Array,
    plans: Array,
    isAuthenticated: Boolean,
});

const primaryAction = computed(() => {
    return props.isAuthenticated
        ? { label: 'Open Dashboard', href: route('dashboard') }
        : { label: 'Start Free', href: route('login') };
});

const secondaryAction = computed(() => {
    return props.isAuthenticated
        ? { label: 'Billing', href: route('profile.billing') }
        : { label: 'Sign In', href: route('login') };
});

const spotlightModules = computed(() => props.modules.slice(0, 6));
const operationModules = computed(() => props.modules.slice(6));

const paidInvoices = computed(() => Number(props.stats?.invoice_paid ?? 0).toLocaleString('id-ID'));
</script>

<template>
    <Head title="Paperwork" />

    <div class="min-h-screen bg-[#f7fbff] text-slate-900">
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-[#90e0ef]/40 blur-3xl"></div>
            <div class="absolute top-52 -left-20 h-80 w-80 rounded-full bg-[#caf0f8]/80 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-[#0077b6]/10 blur-3xl"></div>
        </div>

        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/80 backdrop-blur-lg">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <img src="/img/logo/favicon_blue.png" alt="Paperwork" class="h-10 w-10">
                    <div>
                        <p class="text-sm font-bold tracking-wide text-[#07304a]">PAPERWORK</p>
                        <p class="text-[10px] uppercase tracking-widest text-slate-400">Invoice & Quotation Ops</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="secondaryAction.href"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-600 transition hover:bg-slate-100"
                    >
                        {{ secondaryAction.label }}
                    </Link>
                    <Link
                        :href="primaryAction.href"
                        class="rounded-xl bg-[#07304a] px-5 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#0a3f61]"
                    >
                        {{ primaryAction.label }}
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-12">
            <section class="mx-auto max-w-4xl text-center">
                    <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-[#0077b6]/20 bg-white px-4 py-1 text-[10px] font-semibold uppercase tracking-widest text-[#07304a]">
                        <Icon icon="si:flash-line" :width="12" :height="12" />
                        Live Data From Your Project
                    </p>
                    <h1 class="text-4xl font-bold leading-tight text-[#07304a] md:text-5xl">
                        Run quotation-to-invoice workflows in one focused workspace.
                    </h1>
                    <p class="mt-5 max-w-2xl text-sm leading-relaxed text-slate-600">
                        Paperwork combines client records, product catalog, tax presets, invoice generation,
                        quotation conversion, and billing history. The interface is built for teams that need
                        speed without losing financial visibility.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link
                            :href="primaryAction.href"
                            class="rounded-xl bg-[#07304a] px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#0a3f61]"
                        >
                            {{ primaryAction.label }}
                        </Link>
                        <Link
                            :href="route('login')"
                            class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-100"
                        >
                            Product Walkthrough
                        </Link>
                    </div>

                    <div class="relative mt-10 flex items-center justify-center">
                        <div class="absolute h-28 w-28 rounded-full bg-[#00b4d8]/20 blur-2xl"></div>
                        <div class="relative flex h-28 w-28 flex-col items-center justify-center rounded-full border border-[#0077b6]/30 bg-white/80 shadow-xl backdrop-blur-md">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-[#07304a]/70">Billing</p>
                            <p class="mt-1 text-lg font-bold text-[#07304a]">{{ paidInvoices }}</p>
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-slate-500">Paid</p>
                        </div>
                    </div>
            </section>

            <section class="mt-14 rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="rounded-xl bg-[#07304a] p-5 text-white">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-white/70">Step 1</p>
                        <h3 class="mt-2 text-lg font-bold">Create Quotation</h3>
                        <p class="mt-2 text-xs leading-relaxed text-white/80">Generate client-ready quotation documents from your product and tax setup.</p>
                    </div>
                    <div class="rounded-xl bg-[#0077b6] p-5 text-white">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-white/70">Step 2</p>
                        <h3 class="mt-2 text-lg font-bold">Convert To Invoice</h3>
                        <p class="mt-2 text-xs leading-relaxed text-white/80">Turn accepted quotations into invoices with one action and keep consistency.</p>
                    </div>
                    <div class="rounded-xl bg-[#00b4d8] p-5 text-[#07304a]">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-[#07304a]/70">Step 3</p>
                        <h3 class="mt-2 text-lg font-bold">Track Payment & Billing</h3>
                        <p class="mt-2 text-xs leading-relaxed text-[#07304a]/80">Monitor payment history and package billing records from one place.</p>
                    </div>
                </div>
            </section>

            <section class="mt-16">
                <div class="mb-6 flex items-end justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-[#07304a]">Core Modules</h2>
                        <p class="mt-1 text-sm text-slate-600">Built from what currently exists in your application.</p>
                    </div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ modules.length }} Total Modules</p>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <article v-for="module in spotlightModules" :key="module.title" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="mb-3 inline-flex rounded-lg bg-[#caf0f8] px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-[#07304a]">
                            Module
                        </div>
                        <h3 class="text-base font-bold text-slate-900">{{ module.title }}</h3>
                        <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ module.description }}</p>
                    </article>
                </div>
                <div v-if="operationModules.length > 0" class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Additional Modules</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="module in operationModules" :key="module.title" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-600">
                            {{ module.title }}
                        </span>
                    </div>
                </div>
            </section>

            <section class="mt-16 rounded-[30px] border border-slate-200 bg-gradient-to-br from-white via-[#f5fbff] to-[#eef8ff] p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-[#07304a]">Plans & Capacity</h2>
                        <p class="mt-1 text-sm text-slate-600">Current package matrix from your billing configuration.</p>
                    </div>
                    <Link
                        :href="isAuthenticated ? route('profile.billing') : route('login')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-[10px] font-semibold uppercase tracking-widest text-[#07304a] transition hover:bg-slate-100"
                    >
                        Open Billing
                    </Link>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="(plan, idx) in plans"
                        :key="plan.name"
                        class="rounded-xl border bg-white p-5 shadow-sm"
                        :class="idx === 2 ? 'border-[#07304a] ring-1 ring-[#07304a]/20' : 'border-slate-200'"
                    >
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ plan.name }}</p>
                        <div class="mt-2 flex items-end gap-1">
                            <span class="text-2xl font-bold text-slate-900">{{ plan.price }}</span>
                            <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ plan.period }}</span>
                        </div>
                        <ul class="mt-4 space-y-2">
                            <li v-for="item in plan.highlights" :key="item" class="flex items-center gap-2 text-xs text-slate-700">
                                <Icon icon="si:check-circle-line" :width="14" :height="14" class="text-[#07304a]" />
                                {{ item }}
                            </li>
                        </ul>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>
