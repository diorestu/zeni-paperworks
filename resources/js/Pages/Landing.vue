<script setup>
import { computed, ref } from 'vue';
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

const heroFeatures = [
    'Invoicing',
    'Quotation',
    'Payment Tracking',
];

const partnerNames = [
    'VISA', 'Mastercard', 'JCB', 'AMEX', 'BCA', 'Mandiri',
    'BRI', 'BNI', 'UOB', 'DigiPay', 'Kredivo', 'QRIS',
];

const partnerIndustries = [
    'Finance',
    'Hospitality',
    'Retail',
    'Healthcare',
    'Education',
    'Technology',
    'Manufacturing',
    'Logistics',
    'F&B',
    'Real Estate',
    'Professional Services',
    'Construction',
];

const currentYear = new Date().getFullYear();
const showMobileMenu = ref(false);

const mobileMenuItems = computed(() => {
    const items = [
        { label: 'Home', href: route('landing') },
        { label: primaryAction.value.label, href: primaryAction.value.href },
    ];

    if (secondaryAction.value.href !== primaryAction.value.href) {
        items.push({ label: secondaryAction.value.label, href: secondaryAction.value.href });
    }

    return items;
});

const openMobileMenu = () => {
    showMobileMenu.value = true;
};

const closeMobileMenu = () => {
    showMobileMenu.value = false;
};
</script>

<template>
    <Head title="Paperwork" />

    <div class="min-h-screen bg-[#f3f6fb] text-slate-900">
        <header class="fixed inset-x-0 top-0 z-40 px-4 pt-4">
            <div class="mx-auto flex max-w-7xl items-center justify-between rounded-[60px] border border-white/30 bg-[#0b1e4d]/70 px-6 py-4 shadow-2xl shadow-[#09163b]/50 backdrop-blur-xl" style="border-radius: 60px !important;">
                <div class="flex items-center gap-3">
                    <img src="/img/logo/favicon_blue.png" alt="Paperwork" class="h-10 w-10">
                    <div>
                        <p class="text-sm font-bold tracking-wide text-white">PAPERWORK</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="sm:hidden flex h-10 w-10 items-center justify-center rounded-xl border border-white/25 bg-white/10 text-white transition hover:bg-white/20"
                    @click="openMobileMenu"
                    aria-label="Open menu"
                >
                    <Icon icon="si:menu-hamburger-line" :width="18" :height="18" />
                </button>

                <div class="hidden sm:flex items-center gap-3">
                    <Link
                        :href="secondaryAction.href"
                        class="rounded-xl border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-semibold uppercase tracking-widest text-white transition hover:bg-white/20"
                    >
                        {{ secondaryAction.label }}
                    </Link>
                    <Link
                        :href="primaryAction.href"
                        class="rounded-xl bg-white px-6 py-2.5 text-sm font-semibold uppercase tracking-widest text-[#0b1e4d] transition hover:bg-[#e5efff]"
                    >
                        {{ primaryAction.label }}
                    </Link>
                </div>
            </div>
        </header>

        <transition name="fade">
            <div
                v-if="showMobileMenu"
                class="fixed inset-0 z-50 bg-[#050d24]/65 sm:hidden"
                @click="closeMobileMenu"
            ></div>
        </transition>

        <transition name="slide-panel">
            <aside
                v-if="showMobileMenu"
                class="fixed right-0 top-0 z-[60] h-screen w-[84vw] max-w-xs border-l border-slate-200 bg-white px-5 py-6 shadow-2xl sm:hidden"
            >
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <img src="/img/logo/favicon_blue.png" alt="Paperwork" class="h-8 w-8">
                        <p class="text-sm font-bold tracking-wide text-[#0b1e4d]">PAPERWORK</p>
                    </div>
                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600"
                        @click="closeMobileMenu"
                        aria-label="Close menu"
                    >
                        <Icon icon="si:close-line" :width="16" :height="16" />
                    </button>
                </div>

                <nav class="space-y-2">
                    <Link
                        v-for="item in mobileMenuItems"
                        :key="item.label"
                        :href="item.href"
                        class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-[#0b1e4d] transition hover:bg-slate-50"
                        @click="closeMobileMenu"
                    >
                        <span>{{ item.label }}</span>
                        <Icon icon="si:arrow-right-line" :width="14" :height="14" />
                    </Link>
                </nav>
            </aside>
        </transition>

        <main>
            <section class="relative overflow-hidden bg-gradient-to-b from-[#08173f] via-[#173987] to-[#3f7add] pb-24 pt-36 text-white">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute -top-12 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-[#89d9ff]/20 blur-3xl"></div>
                    <div class="absolute bottom-10 left-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="absolute bottom-16 right-10 h-44 w-44 rounded-full bg-[#7be0ff]/20 blur-2xl"></div>
                </div>

                <div class="relative mx-auto max-w-6xl px-6 text-center">
                    <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1 text-[10px] font-semibold uppercase tracking-widest text-white">
                        <Icon icon="si:flash-line" :width="12" :height="12" />
                        Platform Lengkap untuk Bisnis
                    </p>
                    <h1 class="mx-auto max-w-4xl text-4xl font-bold leading-tight md:text-6xl">
                        Invoicing & Transaksi Bisnis dalam Satu Alur Kerja
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-sm leading-relaxed text-white/80">
                        Praktis, resmi, dan aman untuk tim Anda: dari quotation hingga invoice, serta pelacakan pembayaran.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link
                            :href="primaryAction.href"
                            class="rounded-xl bg-white px-6 py-3 text-xs font-semibold uppercase tracking-widest text-[#0b1e4d] transition hover:bg-[#e9f2ff]"
                        >
                            {{ primaryAction.label }}
                        </Link>
                        <Link
                            :href="route('login')"
                            class="rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-white/20"
                        >
                            Product Walkthrough
                        </Link>
                    </div>

                    <div class="mx-auto mt-10 grid max-w-2xl grid-cols-1 gap-3 sm:grid-cols-3">
                        <div v-for="item in heroFeatures" :key="item" class="rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-white/90">
                            {{ item }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white py-8">
                <div class="mx-auto max-w-6xl px-6">
                    <p class="mb-5 text-center text-[10px] font-semibold uppercase tracking-widest text-slate-400">Dipercaya Tim dari Berbagai Industri</p>
                    <div class="grid grid-cols-2 gap-4 text-center text-xs font-semibold text-slate-500 sm:grid-cols-4 lg:grid-cols-8">
                        <div v-for="name in partnerNames.slice(0, 8)" :key="name" class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">{{ name }}</div>
                    </div>
                </div>
            </section>

            <section class="mx-auto mt-14 max-w-6xl space-y-10 px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-[#07304a]">Sederhanakan Transaksi, Maksimalkan Bisnis</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-sm text-slate-600">Semua fitur inti untuk mengelola transaksi keluar dan pembayaran masuk ada di satu platform.</p>
                </div>

                <div class="grid grid-cols-1 gap-6 rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2 md:items-center">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0077b6]">Praktis, Resmi, Aman</p>
                        <h3 class="mt-2 text-2xl font-bold text-[#07304a]">Buat Invoice Profesional dalam Menit</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">Gunakan data client, produk, pajak, dan akun bank Anda untuk membuat invoice akurat tanpa input berulang.</p>
                        <Link :href="primaryAction.href" class="mt-5 inline-flex rounded-xl bg-[#07304a] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#0a3f61]">Use Invoicing</Link>
                    </div>
                    <div class="rounded-[24px] bg-gradient-to-br from-[#d9f3ff] to-[#eef9ff] p-6">
                        <div class="rounded-xl bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold text-slate-500">Invoice Draft</p>
                            <div class="mt-3 space-y-2">
                                <div class="h-3 rounded bg-slate-100"></div>
                                <div class="h-3 rounded bg-slate-100"></div>
                                <div class="h-3 w-2/3 rounded bg-slate-100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2 md:items-center">
                    <div class="order-2 md:order-1 rounded-[24px] bg-gradient-to-br from-[#d9f3ff] to-[#eef9ff] p-6">
                        <div class="rounded-xl bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold text-slate-500">Quotation To Invoice</p>
                            <div class="mt-3 space-y-2">
                                <div class="h-3 rounded bg-slate-100"></div>
                                <div class="h-3 rounded bg-slate-100"></div>
                                <div class="h-3 w-3/4 rounded bg-slate-100"></div>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0077b6]">Mudah & Cepat</p>
                        <h3 class="mt-2 text-2xl font-bold text-[#07304a]">Konversi Quotation ke Invoice Sekali Klik</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">Kurangi pekerjaan manual dengan alur yang langsung membawa dokumen penawaran ke invoice final.</p>
                        <Link :href="isAuthenticated ? route('quotations.index') : route('login')" class="mt-5 inline-flex rounded-xl bg-[#07304a] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#0a3f61]">Try Workflow</Link>
                    </div>
                </div>
            </section>

            <section class="mt-16 bg-[#eaf6ff] py-14">
                <div class="mx-auto max-w-6xl px-6">
                    <p class="text-center text-[10px] font-semibold uppercase tracking-widest text-slate-400">Powering Your Financial Operation</p>
                    <div class="mt-6 grid grid-cols-1 gap-6 text-center md:grid-cols-3">
                        <div class="border-r border-[#9bc7ea] px-4 md:last:border-r-0">
                            <p class="text-4xl font-bold text-[#07304a]">{{ Number(stats.invoices || 0).toLocaleString('id-ID') }}</p>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-widest text-slate-500">Invoices Created</p>
                        </div>
                        <div class="border-r border-[#9bc7ea] px-4 md:last:border-r-0">
                            <p class="text-4xl font-bold text-[#07304a]">{{ Number(stats.invoice_paid || 0).toLocaleString('id-ID') }}</p>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-widest text-slate-500">Paid Invoices</p>
                        </div>
                        <div class="px-4">
                            <p class="text-4xl font-bold text-[#07304a]">{{ Number(stats.quotation_accepted || 0).toLocaleString('id-ID') }}</p>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-widest text-slate-500">Accepted Quotations</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto mt-14 max-w-6xl px-6">
                <div class="rounded-[28px] border border-slate-200 bg-white p-7 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Enterprise Solution</p>
                    <h3 class="mt-2 text-3xl font-bold text-[#07304a]">Sederhanakan Transaksi Bisnis Rumit dengan Automation</h3>
                    <p class="mt-3 max-w-3xl text-sm text-slate-600">Kelola skala operasional lebih besar dengan workflow approvals, log audit, dan manajemen billing terpusat.</p>
                    <Link :href="isAuthenticated ? route('profile.billing') : route('login')" class="mt-6 inline-flex rounded-xl bg-[#07304a] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white hover:bg-[#0a3f61]">Talk To Sales</Link>
                </div>
            </section>

            <section class="mx-auto mt-14 max-w-6xl px-6 text-center">
                <h2 class="text-3xl font-bold text-[#07304a]">Partner Kami</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-600">Dipakai oleh berbagai bidang industri untuk operasional transaksi bisnis.</p>
                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    <div v-for="industry in partnerIndustries" :key="industry" class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs font-semibold text-slate-600 shadow-sm">
                        {{ industry }}
                    </div>
                </div>
            </section>

            <section class="mx-auto mt-16 max-w-4xl px-6 pb-10 text-center">
                <h2 class="text-2xl font-bold text-[#07304a]">Kata Mereka Tentang Kami</h2>
                <div class="mt-6 rounded-[24px] bg-gradient-to-br from-[#103171] to-[#3a78db] p-7 text-left text-white shadow-2xl shadow-[#1f4f9e]/30">
                    <p class="text-sm leading-relaxed text-white/90">
                        “Paperwork membantu tim kami mempercepat proses dari penawaran hingga pembayaran.
                        Tidak lagi pindah-pindah spreadsheet untuk memantau status transaksi.”
                    </p>
                    <div class="mt-4 text-xs font-semibold uppercase tracking-widest text-white/70">Finance Manager • Enterprise Client</div>
                </div>
            </section>
        </main>

        <footer class="mt-8 border-t border-[#0a3f61] bg-[#07304a] text-white">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-6 py-10 md:grid-cols-3">
                <div>
                    <div class="flex items-center gap-3">
                        <img src="/img/logo/favicon_blue.png" alt="Paperwork" class="h-8 w-8">
                        <p class="text-sm font-bold tracking-wide text-white">PAPERWORK</p>
                    </div>
                    <p class="mt-3 max-w-xs text-xs leading-relaxed text-white/80">
                        Manage quotations, invoices, clients, and billing operations in one workspace.
                    </p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-white/60">Navigation</p>
                    <div class="mt-3 flex flex-col gap-2">
                        <Link :href="route('landing')" class="text-xs font-semibold text-white/85 hover:text-white">Home</Link>
                        <Link :href="isAuthenticated ? route('dashboard') : route('login')" class="text-xs font-semibold text-white/85 hover:text-white">Dashboard</Link>
                        <Link :href="isAuthenticated ? route('profile.billing') : route('login')" class="text-xs font-semibold text-white/85 hover:text-white">Billing</Link>
                    </div>
                </div>

                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-white/60">Contact</p>
                    <div class="mt-3 space-y-2 text-xs text-white/85">
                        <p>support@paperwork.local</p>
                        <p>Denpasar, Indonesia</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/15 px-6 py-4">
                <p class="mx-auto max-w-7xl text-[10px] font-semibold uppercase tracking-widest text-white/60">
                    © {{ currentYear }} Paperwork. All rights reserved.
                </p>
            </div>
        </footer>

        <a
            href="https://wa.me/6285183440300"
            target="_blank"
            rel="noopener noreferrer"
            class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-2xl shadow-[#25D366]/30 transition hover:scale-105 hover:bg-[#1fb95a]"
            aria-label="Chat on WhatsApp"
            title="Chat on WhatsApp"
        >
            <Icon icon="ri:whatsapp-fill" :width="28" :height="28" />
        </a>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 220ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-panel-enter-active,
.slide-panel-leave-active {
    transition: transform 220ms ease, opacity 220ms ease;
}

.slide-panel-enter-from,
.slide-panel-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>
