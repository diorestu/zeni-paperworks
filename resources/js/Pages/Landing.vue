<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    modules: {
        type: Array,
        default: () => [],
    },
    plans: {
        type: Array,
        default: () => [],
    },
    isAuthenticated: {
        type: Boolean,
        default: false,
    },
});

const primaryAction = computed(() =>
    props.isAuthenticated
        ? { label: 'Buka Dashboard', href: route('dashboard') }
        : { label: 'Coba Gratis', href: route('login') }
);

const secondaryAction = computed(() =>
    props.isAuthenticated
        ? { label: 'Billing', href: route('settings.billing') }
        : { label: 'Lihat Demo', href: route('login') }
);

const navLinks = [
    { label: 'Fitur', href: '#fitur' },
    { label: 'Harga', href: '#harga' },
    { label: 'Testimoni', href: '#testimoni' },
];

const partnerIndustries = [
    'Finance',
    'Hospitality',
    'Retail',
    'Healthcare',
    'Education',
    'Technology',
    'F&B',
    'Manufacturing',
];

const heroChecks = ['Invoice & Quotation', 'Pelacakan Pembayaran', 'Data Client Terpusat'];
const currentYear = new Date().getFullYear();
const showMobileMenu = ref(false);

const topStats = computed(() => [
    { label: 'Invoices Diproses', value: Number(props.stats?.invoices || 0).toLocaleString('id-ID') },
    { label: 'Quotation Terkirim', value: Number(props.stats?.quotations || 0).toLocaleString('id-ID') },
    { label: 'Client Aktif', value: Number(props.stats?.clients || 0).toLocaleString('id-ID') },
]);

const spotlightFeatures = computed(() => {
    if (!props.modules?.length) return [];
    return props.modules.slice(0, 6);
});

const closeMenu = () => {
    showMobileMenu.value = false;
};
</script>

<template>
    <Head title="Paperwork" />

    <div class="min-h-screen bg-[#f4f8ff] text-slate-900">
        <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <Link :href="route('landing')" class="flex items-center gap-3">
                    <img src="/img/logo/favicon.png" alt="Paperwork" class="h-9 w-9">
                    <div>
                        <p class="text-sm font-extrabold tracking-wide text-[#0b2d6b]">PAPERWORK</p>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Business Operating System</p>
                    </div>
                </Link>

                <nav class="hidden items-center gap-6 md:flex">
                    <a
                        v-for="item in navLinks"
                        :key="item.href"
                        :href="item.href"
                        class="text-xs font-semibold uppercase tracking-widest text-slate-500 transition hover:text-[#0b2d6b]"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    <Link
                        :href="secondaryAction.href"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-slate-600 transition hover:bg-slate-50"
                    >
                        {{ secondaryAction.label }}
                    </Link>
                    <Link
                        :href="primaryAction.href"
                        class="rounded-xl bg-[#0b2d6b] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#0a2558]"
                    >
                        {{ primaryAction.label }}
                    </Link>
                </div>

                <button
                    type="button"
                    class="md:hidden flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600"
                    @click="showMobileMenu = true"
                    aria-label="Open menu"
                >
                    <Icon icon="si:menu-hamburger-line" :width="18" :height="18" />
                </button>
            </div>
        </header>

        <transition name="fade">
            <div
                v-if="showMobileMenu"
                class="fixed inset-0 z-50 bg-[#071634]/45 md:hidden"
                @click="closeMenu"
            ></div>
        </transition>

        <transition name="slide-panel">
            <aside
                v-if="showMobileMenu"
                class="fixed right-0 top-0 z-[60] h-screen w-[82vw] max-w-xs border-l border-slate-100 bg-white px-5 py-6 md:hidden"
            >
                <div class="mb-8 flex items-center justify-between">
                    <p class="text-sm font-bold tracking-wide text-[#0b2d6b]">MENU</p>
                    <button type="button" class="rounded-lg bg-slate-100 p-2 text-slate-600" @click="closeMenu">
                        <Icon icon="si:close-line" :width="16" :height="16" />
                    </button>
                </div>

                <div class="space-y-2">
                    <a
                        v-for="item in navLinks"
                        :key="item.href"
                        :href="item.href"
                        class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-xs font-semibold uppercase tracking-widest text-slate-700"
                        @click="closeMenu"
                    >
                        <span>{{ item.label }}</span>
                        <Icon icon="si:arrow-right-line" :width="14" :height="14" />
                    </a>
                </div>

                <div class="mt-6 space-y-2">
                    <Link
                        :href="secondaryAction.href"
                        class="block rounded-xl border border-slate-200 px-4 py-3 text-center text-xs font-semibold uppercase tracking-widest text-slate-700"
                        @click="closeMenu"
                    >
                        {{ secondaryAction.label }}
                    </Link>
                    <Link
                        :href="primaryAction.href"
                        class="block rounded-xl bg-[#0b2d6b] px-4 py-3 text-center text-xs font-semibold uppercase tracking-widest text-white"
                        @click="closeMenu"
                    >
                        {{ primaryAction.label }}
                    </Link>
                </div>
            </aside>
        </transition>

        <main>
            <section class="relative overflow-hidden bg-gradient-to-br from-[#0b2d6b] via-[#124394] to-[#2e7adf] pb-20 pt-20 text-white">
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute -top-20 left-1/3 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute bottom-6 right-8 h-52 w-52 rounded-full bg-[#6fd2ff]/30 blur-3xl"></div>
                </div>

                <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 lg:grid-cols-2">
                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1 text-[10px] font-semibold uppercase tracking-widest text-white/90">
                            <Icon icon="si:flash-line" :width="12" :height="12" />
                            Solusi Seperti Jurnal, Fokus Untuk Tim Growing
                        </p>
                        <h1 class="mt-5 text-4xl font-extrabold leading-tight md:text-5xl">
                            Kelola Invoice, Quotation, dan Pembayaran dalam Satu Platform
                        </h1>
                        <p class="mt-4 max-w-xl text-sm leading-relaxed text-white/85">
                            Paperwork membantu tim finance dan owner bisnis memproses dokumen lebih cepat, lebih rapi, dan lebih minim input berulang.
                        </p>

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <Link
                                :href="primaryAction.href"
                                class="rounded-xl bg-[#ff8a00] px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#ec7d00]"
                            >
                                {{ primaryAction.label }}
                            </Link>
                            <Link
                                :href="secondaryAction.href"
                                class="rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-white/20"
                            >
                                {{ secondaryAction.label }}
                            </Link>
                        </div>

                        <div class="mt-8 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div v-for="check in heroChecks" :key="check" class="flex items-center gap-2 text-xs font-semibold text-white/90">
                                <Icon icon="si:check-line" :width="14" :height="14" class="text-[#8fffd4]" />
                                <span>{{ check }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-white/20 bg-white/95 p-6 text-slate-900 shadow-2xl shadow-[#0a2558]/40">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold text-[#0b2d6b]">Finance Snapshot</p>
                            <span class="rounded-lg bg-emerald-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-emerald-600">Live</span>
                        </div>
                        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div v-for="stat in topStats" :key="stat.label" class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ stat.label }}</p>
                                <p class="mt-2 text-xl font-extrabold text-[#0b2d6b]">{{ stat.value }}</p>
                            </div>
                        </div>
                        <div class="mt-5 rounded-xl border border-slate-100 bg-white p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-xs font-semibold text-slate-500">Arus Transaksi Mingguan</p>
                                <p class="text-xs font-semibold text-[#0b2d6b]">+18.4%</p>
                            </div>
                            <div class="grid grid-cols-7 items-end gap-2">
                                <div class="h-10 rounded bg-[#d6e8ff]"></div>
                                <div class="h-14 rounded bg-[#c6ddff]"></div>
                                <div class="h-12 rounded bg-[#b5d2ff]"></div>
                                <div class="h-20 rounded bg-[#8eb7ff]"></div>
                                <div class="h-16 rounded bg-[#78a6ff]"></div>
                                <div class="h-24 rounded bg-[#4c84f3]"></div>
                                <div class="h-28 rounded bg-[#0b2d6b]"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white py-8">
                <div class="mx-auto max-w-7xl px-6">
                    <p class="text-center text-[10px] font-semibold uppercase tracking-widest text-slate-400">Digunakan Berbagai Industri</p>
                    <div class="mt-5 grid grid-cols-2 gap-3 text-center sm:grid-cols-4 lg:grid-cols-8">
                        <div v-for="industry in partnerIndustries" :key="industry" class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                            {{ industry }}
                        </div>
                    </div>
                </div>
            </section>

            <section id="fitur" class="mx-auto mt-14 max-w-7xl px-6">
                <div class="text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0b2d6b]">Kenapa Paperwork</p>
                    <h2 class="mt-2 text-3xl font-extrabold text-[#0b2d6b]">Alur Kerja Finance Lebih Cepat dan Terukur</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-sm text-slate-600">Satu workspace untuk dokumen transaksi, status pembayaran, dan manajemen data client.</p>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="module in spotlightFeatures"
                        :key="module.title"
                        class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e9f1ff] text-[#0b2d6b]">
                            <Icon icon="si:ballot-line" :width="18" :height="18" />
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">{{ module.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ module.description }}</p>
                    </article>
                </div>
            </section>

            <section id="harga" class="mx-auto mt-16 max-w-7xl px-6">
                <div class="rounded-[28px] border border-slate-200 bg-white p-7 shadow-sm md:p-10">
                    <div class="mb-8 text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0b2d6b]">Pricing</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Pilih Paket Sesuai Tahap Bisnis</h2>
                        <p class="mt-3 text-sm text-slate-600">Mulai dari gratis untuk kebutuhan dasar hingga enterprise untuk volume transaksi tinggi.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <article
                            v-for="plan in plans"
                            :key="plan.name"
                            class="rounded-2xl border p-5"
                            :class="plan.name === 'Pro' ? 'border-[#0b2d6b] bg-[#f3f7ff] shadow-md' : 'border-slate-200 bg-white'"
                        >
                            <p class="text-xs font-semibold uppercase tracking-widest" :class="plan.name === 'Pro' ? 'text-[#0b2d6b]' : 'text-slate-500'">{{ plan.name }}</p>
                            <div class="mt-3 flex items-end gap-1">
                                <span class="text-2xl font-extrabold text-slate-900">{{ plan.price }}</span>
                                <span class="text-xs font-semibold text-slate-400">{{ plan.period }}</span>
                            </div>

                            <ul class="mt-4 space-y-2">
                                <li v-for="item in plan.highlights" :key="item" class="flex items-start gap-2 text-xs font-semibold text-slate-600">
                                    <Icon icon="si:check-line" :width="14" :height="14" class="mt-0.5 text-emerald-500" />
                                    <span>{{ item }}</span>
                                </li>
                            </ul>

                            <Link
                                :href="primaryAction.href"
                                class="mt-6 inline-flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-xs font-semibold uppercase tracking-widest transition"
                                :class="plan.name === 'Pro' ? 'bg-[#0b2d6b] text-white hover:bg-[#0a2558]' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            >
                                Pilih Paket
                            </Link>
                        </article>
                    </div>
                </div>
            </section>

            <section id="testimoni" class="mx-auto mt-16 max-w-5xl px-6 pb-16">
                <div class="rounded-[28px] bg-gradient-to-br from-[#0b2d6b] to-[#2c79dd] p-8 text-white shadow-2xl shadow-[#1b4e9f]/30 md:p-10">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-white/70">Customer Story</p>
                    <p class="mt-4 text-lg font-semibold leading-relaxed md:text-xl">
                        “Tim kami sekarang tidak lagi bolak-balik spreadsheet. Quotation dan invoice jalan dalam satu flow, serta status pembayaran langsung kebaca.”
                    </p>
                    <p class="mt-5 text-xs font-semibold uppercase tracking-widest text-white/70">Finance Lead • SME Services</p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            :href="primaryAction.href"
                            class="rounded-xl bg-[#ff8a00] px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#ec7d00]"
                        >
                            Mulai Sekarang
                        </Link>
                        <a href="https://wa.me/6285183440300" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-white/20">
                            Chat Sales
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-[#0b2d6b]/10 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-6 px-6 py-10 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-bold tracking-wide text-[#0b2d6b]">PAPERWORK</p>
                    <p class="mt-1 text-xs text-slate-500">Sistem transaksi bisnis untuk tim yang ingin bergerak lebih cepat.</p>
                </div>
                <div class="flex flex-wrap gap-4 text-xs font-semibold uppercase tracking-widest text-slate-500">
                    <a href="#fitur" class="hover:text-[#0b2d6b]">Fitur</a>
                    <a href="#harga" class="hover:text-[#0b2d6b]">Harga</a>
                    <a href="#testimoni" class="hover:text-[#0b2d6b]">Testimoni</a>
                </div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">© {{ currentYear }} Paperwork</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 180ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-panel-enter-active,
.slide-panel-leave-active {
    transition: transform 220ms ease;
}

.slide-panel-enter-from,
.slide-panel-leave-to {
    transform: translateX(100%);
}
</style>
