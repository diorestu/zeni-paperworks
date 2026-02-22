<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
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
        : { label: 'Hubungi Sales', href: '#bantuan' }
);

const navLinks = [
    { label: 'Fitur', href: '#fitur' },
    { label: 'Solusi', href: '#solusi' },
    { label: 'Bantuan', href: '#bantuan' },
    { label: 'Harga', href: '#harga' },
];

const fiturMegaMenu = [
    {
        title: 'Invoice Otomatis',
        description: 'Buat invoice profesional dalam hitungan detik.',
        href: '#fitur',
        icon: 'si:file-document-line',
    },
    {
        title: 'Quotation Cepat',
        description: 'Konversi quotation ke invoice tanpa input ulang.',
        href: '#fitur',
        icon: 'si:briefcase-line',
    },
    {
        title: 'Manajemen Client',
        description: 'Semua data client tersimpan rapi dan terpusat.',
        href: '#fitur',
        icon: 'si:user-circle-line',
    },
    {
        title: 'Laporan & Tracking',
        description: 'Pantau status pembayaran dan performa tim.',
        href: '#fitur',
        icon: 'si:chart-bar-line',
    },
];

const solusiMegaMenu = [
    {
        title: 'Untuk Finance Team',
        description: 'Automasi alur billing dan kontrol cashflow.',
        href: '#solusi',
        icon: 'si:wallet-line',
    },
    {
        title: 'Untuk Owner',
        description: 'Lihat performa bisnis dan approval lebih cepat.',
        href: '#solusi',
        icon: 'si:building-line',
    },
    {
        title: 'Untuk Sales',
        description: 'Kirim quotation cepat dan follow-up terukur.',
        href: '#solusi',
        icon: 'si:target-line',
    },
    {
        title: 'Untuk Multi-Industry',
        description: 'Template fleksibel untuk berbagai jenis bisnis.',
        href: '#solusi',
        icon: 'si:layers-line',
    },
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
const heroSectionRef = ref(null);
const isPastHero = ref(false);
const heroCursorStyle = ref({
    '--x': '50%',
    '--y': '50%',
});
const testimonialIndex = ref(0);
let testimonialTimer = null;

const testimonials = [
    {
        quote: '“Tim kami sekarang tidak lagi bolak-balik spreadsheet. Quotation dan invoice jalan dalam satu flow, serta status pembayaran langsung kebaca.”',
        author: 'Finance Lead',
        company: 'SME Services',
    },
    {
        quote: '“Waktu pembuatan invoice turun drastis. Tim sales bisa langsung lanjut ke follow-up tanpa tunggu admin input ulang data.”',
        author: 'Operations Manager',
        company: 'Retail Group',
    },
    {
        quote: '“Sistemnya rapi untuk multi-client. Reminder pembayaran juga membantu cashflow jadi lebih terkontrol tiap minggu.”',
        author: 'Business Owner',
        company: 'Hospitality Co',
    },
];

const topStats = computed(() => [
    { label: 'Invoices Diproses', value: Number(props.stats?.invoices || 0).toLocaleString('id-ID') },
    { label: 'Quotation Terkirim', value: Number(props.stats?.quotations || 0).toLocaleString('id-ID') },
    { label: 'Client Aktif', value: Number(props.stats?.clients || 0).toLocaleString('id-ID') },
]);

const spotlightFeatures = computed(() => {
    if (!props.modules?.length) return [];
    return props.modules.slice(0, 6);
});

const headerClass = computed(() =>
    isPastHero.value
        ? 'border-slate-100/95 bg-white/95 shadow-sm backdrop-blur'
        : 'border-white/20 bg-white/10 backdrop-blur-xl'
);

const navLinkClass = computed(() =>
    isPastHero.value
        ? 'text-slate-500 hover:text-[#0b2d6b]'
        : 'text-white/80 hover:text-white'
);

const secondaryButtonClass = computed(() =>
    isPastHero.value
        ? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
        : 'border-white/30 bg-white/10 text-white hover:bg-white/20'
);

const mobileMenuButtonClass = computed(() =>
    isPastHero.value
        ? 'border-slate-200 bg-white text-slate-600'
        : 'border-white/35 bg-white/10 text-white'
);

const closeMenu = () => {
    showMobileMenu.value = false;
};

const scrollToSection = (event, href) => {
    if (!href || !href.startsWith('#')) return;

    const target = document.querySelector(href);
    if (!target) return;

    event.preventDefault();

    const headerOffset = 88;
    const targetPosition = target.getBoundingClientRect().top + window.scrollY - headerOffset;

    window.scrollTo({
        top: targetPosition,
        behavior: 'smooth',
    });
};

const handleHeroPointerMove = (event) => {
    const target = event.currentTarget;
    if (!target) return;

    const rect = target.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    heroCursorStyle.value = {
        '--x': `${Math.min(100, Math.max(0, x))}%`,
        '--y': `${Math.min(100, Math.max(0, y))}%`,
    };
};

const handleHeroPointerLeave = () => {
    heroCursorStyle.value = {
        '--x': '50%',
        '--y': '50%',
    };
};

const updateHeaderState = () => {
    if (!heroSectionRef.value) return;
    const heroBottom = heroSectionRef.value.getBoundingClientRect().bottom;
    isPastHero.value = heroBottom <= 88;
};

onMounted(() => {
    testimonialTimer = window.setInterval(() => {
        testimonialIndex.value = (testimonialIndex.value + 1) % testimonials.length;
    }, 4200);

    updateHeaderState();
    window.addEventListener('scroll', updateHeaderState, { passive: true });
    window.addEventListener('resize', updateHeaderState);
});

onBeforeUnmount(() => {
    if (testimonialTimer) {
        window.clearInterval(testimonialTimer);
    }
    window.removeEventListener('scroll', updateHeaderState);
    window.removeEventListener('resize', updateHeaderState);
});
</script>

<template>
    <Head title="Paperwork" />

    <div class="min-h-screen bg-[#f4f8ff] text-slate-900">
        <header class="fixed inset-x-0 top-0 z-50 border-b transition-all duration-500" :class="headerClass">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <Link :href="route('landing')" class="flex items-center gap-3">
                    <img
                        :src="isPastHero ? '/img/logo/logo.png' : '/img/logo/logo_white.png'"
                        alt="Paperwork"
                        class="h-10 w-auto transition-all duration-500"
                        :class="isPastHero ? '' : 'drop-shadow-[0_2px_8px_rgba(0,0,0,0.35)]'"
                    >
                </Link>

                <nav class="hidden items-center gap-6 md:flex">
                    <div class="group relative flex items-center">
                        <button type="button" class="inline-flex h-10 items-center gap-1 text-xs font-semibold uppercase tracking-widest leading-none transition" :class="navLinkClass">
                            Fitur
                            <Icon icon="si:arrow-down-line" :width="12" :height="12" />
                        </button>
                        <div class="pointer-events-none absolute left-0 top-full pt-4 opacity-0 transition-all duration-200 group-hover:pointer-events-auto group-hover:opacity-100">
                            <div class="w-[560px] rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl">
                                <div class="mb-3 grid grid-cols-2 gap-3">
                                    <a
                                        v-for="item in fiturMegaMenu"
                                        :key="item.title"
                                        :href="item.href"
                                        class="rounded-xl border border-slate-200 bg-slate-50 p-3 transition hover:border-[#0b2d6b]/30 hover:bg-white"
                                        @click="(event) => scrollToSection(event, item.href)"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-lg bg-[#e9f1ff] text-[#0b2d6b]">
                                                <Icon :icon="item.icon" :width="14" :height="14" />
                                            </span>
                                            <span>
                                                <span class="block text-xs font-semibold text-slate-900">{{ item.title }}</span>
                                                <span class="mt-1 block text-xs text-slate-500">{{ item.description }}</span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="group relative flex items-center">
                        <button type="button" class="inline-flex h-10 items-center gap-1 text-xs font-semibold uppercase tracking-widest leading-none transition" :class="navLinkClass">
                            Solusi
                            <Icon icon="si:arrow-down-line" :width="12" :height="12" />
                        </button>
                        <div class="pointer-events-none absolute left-0 top-full pt-4 opacity-0 transition-all duration-200 group-hover:pointer-events-auto group-hover:opacity-100">
                            <div class="w-[560px] rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl">
                                <div class="mb-3 grid grid-cols-2 gap-3">
                                    <a
                                        v-for="item in solusiMegaMenu"
                                        :key="item.title"
                                        :href="item.href"
                                        class="rounded-xl border border-slate-200 bg-slate-50 p-3 transition hover:border-[#0b2d6b]/30 hover:bg-white"
                                        @click="(event) => scrollToSection(event, item.href)"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-lg bg-[#e9f1ff] text-[#0b2d6b]">
                                                <Icon :icon="item.icon" :width="14" :height="14" />
                                            </span>
                                            <span>
                                                <span class="block text-xs font-semibold text-slate-900">{{ item.title }}</span>
                                                <span class="mt-1 block text-xs text-slate-500">{{ item.description }}</span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a
                        href="#bantuan"
                        class="inline-flex h-10 items-center text-xs font-semibold uppercase tracking-widest leading-none transition"
                        :class="navLinkClass"
                        @click="(event) => scrollToSection(event, '#bantuan')"
                    >
                        Bantuan
                    </a>
                    <a
                        href="#harga"
                        class="inline-flex h-10 items-center text-xs font-semibold uppercase tracking-widest leading-none transition"
                        :class="navLinkClass"
                        @click="(event) => scrollToSection(event, '#harga')"
                    >
                        Harga
                    </a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    <a
                        :href="secondaryAction.href"
                        class="rounded-xl border px-4 py-2.5 text-xs font-semibold uppercase tracking-widest transition"
                        :class="secondaryButtonClass"
                        @click="(event) => scrollToSection(event, secondaryAction.href)"
                    >
                        {{ secondaryAction.label }}
                    </a>
                    <Link
                        :href="primaryAction.href"
                        class="rounded-xl bg-[#0b2d6b] px-5 py-2.5 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#0a2558]"
                    >
                        {{ primaryAction.label }}
                    </Link>
                </div>

                <button
                    type="button"
                    class="md:hidden flex h-10 w-10 items-center justify-center rounded-xl border transition-all duration-500"
                    :class="mobileMenuButtonClass"
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
                        @click="(event) => { scrollToSection(event, item.href); closeMenu(); }"
                    >
                        <span>{{ item.label }}</span>
                        <Icon icon="si:arrow-right-line" :width="14" :height="14" />
                    </a>
                </div>

                <div class="mt-6 space-y-2">
                    <a
                        :href="secondaryAction.href"
                        class="block rounded-xl border border-slate-200 px-4 py-3 text-center text-xs font-semibold uppercase tracking-widest text-slate-700"
                        @click="(event) => { scrollToSection(event, secondaryAction.href); closeMenu(); }"
                    >
                        {{ secondaryAction.label }}
                    </a>
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
            <section
                ref="heroSectionRef"
                class="gradient-background relative overflow-hidden pb-20 pt-28 text-white md:pt-32"
                :style="heroCursorStyle"
                @pointermove="handleHeroPointerMove"
                @pointerleave="handleHeroPointerLeave"
            >
                <div class="hero-cursor-blob"></div>
                <div class="hero-gradient-overlay"></div>
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute -top-20 left-1/3 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute bottom-6 right-8 h-52 w-52 rounded-full bg-[#6fd2ff]/30 blur-3xl"></div>
                </div>

                <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 lg:grid-cols-2">
                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/15 px-4 py-1 text-[10px] font-semibold uppercase tracking-widest text-white">
                            <Icon icon="si:flash-line" :width="12" :height="12" />
                            Solusi Seperti Jurnal, Fokus Untuk Tim Growing
                        </p>
                        <h1 class="hero-title mt-5 text-4xl font-extrabold leading-tight md:text-5xl">
                            Kelola Invoice, Quotation, dan Pembayaran dalam Satu Platform
                        </h1>
                        <p class="mt-4 max-w-xl text-sm leading-relaxed text-white">
                            Paperwork membantu tim finance dan owner bisnis memproses dokumen lebih cepat, lebih rapi, dan lebih minim input berulang.
                        </p>

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <Link
                                :href="primaryAction.href"
                                class="rounded-xl bg-[#ff8a00] px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#ec7d00]"
                            >
                                {{ primaryAction.label }}
                            </Link>
                            <a
                                :href="secondaryAction.href"
                                class="rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-white/20"
                                @click="(event) => scrollToSection(event, secondaryAction.href)"
                            >
                                {{ secondaryAction.label }}
                            </a>
                        </div>

                        <div class="mt-8 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div v-for="check in heroChecks" :key="check" class="flex items-center gap-2 text-xs font-semibold text-white">
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
                    <div class="mt-5 overflow-hidden">
                        <div class="industry-track">
                            <div
                                v-for="(industry, index) in [...partnerIndustries, ...partnerIndustries]"
                                :key="`${industry}-${index}`"
                                class="industry-pill"
                            >
                                {{ industry }}
                            </div>
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

            <section id="solusi" class="mx-auto mt-16 max-w-7xl px-6">
                <div class="rounded-[28px] border border-slate-200 bg-white p-7 shadow-sm md:p-10">
                    <div class="text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0b2d6b]">Solusi</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Dirancang untuk Setiap Peran di Bisnis</h2>
                    </div>
                    <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <article v-for="item in solusiMegaMenu" :key="`section-${item.title}`" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e9f1ff] text-[#0b2d6b]">
                                    <Icon :icon="item.icon" :width="16" :height="16" />
                                </span>
                                <div>
                                    <h3 class="text-md font-semibold text-slate-900">{{ item.title }}</h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ item.description }}</p>
                                </div>
                            </div>
                        </article>
                    </div>
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
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-white/90">Customer Story</p>
                    <transition name="fade" mode="out-in">
                        <div :key="testimonialIndex" class="mt-4">
                            <p class="text-lg font-semibold leading-relaxed text-white md:text-xl">
                                {{ testimonials[testimonialIndex].quote }}
                            </p>
                            <p class="mt-5 text-xs font-semibold uppercase tracking-widest text-white">
                                {{ testimonials[testimonialIndex].author }} • {{ testimonials[testimonialIndex].company }}
                            </p>
                        </div>
                    </transition>

                    <div class="mt-5 flex items-center gap-2">
                        <button
                            v-for="(item, index) in testimonials"
                            :key="`dot-${index}`"
                            type="button"
                            class="h-1.5 transition-all"
                            :class="testimonialIndex === index ? 'w-6 rounded-full bg-white' : 'w-2 rounded-full bg-white/40'"
                            @click="testimonialIndex = index"
                            :aria-label="`Testimonial ${index + 1}`"
                        ></button>
                    </div>

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

            <section id="bantuan" class="mx-auto mt-2 max-w-7xl px-6 pb-16">
                <div class="rounded-[28px] border border-slate-200 bg-white p-7 shadow-sm md:p-10">
                    <div class="text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0b2d6b]">Bantuan</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Butuh Bantuan Implementasi?</h2>
                        <p class="mx-auto mt-3 max-w-2xl text-sm text-slate-600">Tim kami siap bantu onboarding, migrasi data, dan setup workflow sesuai kebutuhan bisnis kamu.</p>
                    </div>
                    <div class="mt-7 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <a href="https://wa.me/6285183440300" target="_blank" rel="noopener noreferrer" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-[#0b2d6b]/30 hover:bg-white">
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Live Chat</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">Konsultasi via WhatsApp</p>
                        </a>
                        <a href="mailto:support@paperwork.local" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-[#0b2d6b]/30 hover:bg-white">
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Email Support</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">support@paperwork.local</p>
                        </a>
                        <a href="#fitur" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-[#0b2d6b]/30 hover:bg-white" @click="(event) => scrollToSection(event, '#fitur')">
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Product Tour</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">Lihat fitur inti Paperwork</p>
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/15 bg-[#001845] text-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-6 px-6 py-10 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <img src="/img/logo/logo_white.png" alt="Paperwork" class="h-9 w-auto">
                </div>
                <div class="flex flex-wrap gap-4 text-xs font-semibold uppercase tracking-widest text-white/80">
                    <a href="#fitur" class="hover:text-white" @click="(event) => scrollToSection(event, '#fitur')">Fitur</a>
                    <a href="#harga" class="hover:text-white" @click="(event) => scrollToSection(event, '#harga')">Harga</a>
                    <a href="#testimoni" class="hover:text-white" @click="(event) => scrollToSection(event, '#testimoni')">Testimoni</a>
                </div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-white/70">© {{ currentYear }} Paperwork</p>
            </div>
        </footer>

        <a
            href="https://wa.me/085710999144"
            target="_blank"
            rel="noopener noreferrer"
            class="fixed bottom-6 right-6 z-[70] inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-4 py-3 text-xs font-semibold uppercase tracking-widest text-white shadow-xl shadow-[#25D366]/35 transition hover:scale-[1.02] hover:bg-[#1fb85a]"
            aria-label="Chat WhatsApp"
        >
            <Icon icon="logos:whatsapp-icon" :width="18" :height="18" />
            <span>WhatsApp</span>
        </a>
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

.gradient-background {
    background: linear-gradient(48deg, deepskyblue, #022576, #1a4cc0);
    background-size: 360% 360%;
    animation: gradient-animation 14s ease infinite;
    position: relative;
    isolation: isolate;
}

.hero-cursor-blob {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(
            circle at var(--x, 50%) var(--y, 50%),
            rgba(255, 255, 255, 0.34) 0%,
            rgba(173, 216, 255, 0.2) 16%,
            rgba(15, 75, 190, 0.12) 32%,
            rgba(15, 75, 190, 0) 54%
        );
    transition: background-position 80ms linear;
    z-index: 0;
}

.hero-gradient-overlay {
    position: absolute;
    inset: -40%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.03), rgba(175, 220, 255, 0.09), rgba(255, 255, 255, 0.02));
    animation: hero-diagonal 13s ease-in-out infinite alternate;
    mix-blend-mode: screen;
    pointer-events: none;
    z-index: 1;
}

.hero-title {
    color: #ffffff;
    background: linear-gradient(120deg, #ffffff, #e7f4ff, #ffffff);
    background-size: 220% 220%;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: title-shimmer 5.5s ease-in-out infinite;
}

.industry-track {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: max-content;
    animation: industry-scroll 24s linear infinite;
}

.industry-pill {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    padding: 0.5rem 0.9rem;
    font-size: 12px;
    font-weight: 600;
    color: #33415c;
    white-space: nowrap;
}

@keyframes industry-scroll {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

@keyframes hero-diagonal {
    0% {
        transform: translate(-8%, 14%) rotate(0deg);
    }
    100% {
        transform: translate(16%, -18%) rotate(5deg);
    }
}

@keyframes title-shimmer {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes gradient-animation {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}
</style>
