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

const selectedLanguage = ref('id');

const i18n = {
    id: {
        nav: { feature: 'Fitur', solution: 'Solusi', help: 'Bantuan', price: 'Harga' },
        mobileNavTitle: 'NAVIGASI',
        language: 'Bahasa',
        primaryAuth: 'Buka Dashboard',
        primaryGuest: 'Coba Gratis',
        secondaryAuth: 'Tagihan',
        secondaryGuest: 'Hubungi Penjualan',
        heroBadge: 'Solusi Seperti Jurnal, Fokus untuk Tim Bertumbuh',
        heroTitle: 'Kelola Invoice, Penawaran, dan Pembayaran dalam Satu Platform',
        heroDesc: 'Paperwork membantu tim keuangan dan pemilik bisnis memproses dokumen lebih cepat, lebih rapi, dan minim input berulang.',
        heroChecks: ['Invoice & Penawaran', 'Pelacakan Pembayaran', 'Data Klien Terpusat'],
        summaryTitle: 'Ringkasan Keuangan',
        active: 'Aktif',
        weeklyFlow: 'Arus Transaksi Mingguan',
        usedByIndustries: 'Digunakan Berbagai Industri',
        whyPaperwork: 'Kenapa Paperwork',
        featureSectionTitle: 'Alur Kerja Keuangan Lebih Cepat dan Terukur',
        featureSectionDesc: 'Satu ruang kerja untuk dokumen transaksi, status pembayaran, dan manajemen data klien.',
        solutionSectionTag: 'Solusi',
        solutionSectionTitle: 'Dirancang untuk Setiap Peran di Bisnis',
        pricingTag: 'Harga',
        pricingTitle: 'Pilih Paket Sesuai Tahap Bisnis',
        pricingDesc: 'Mulai dari gratis untuk kebutuhan dasar hingga enterprise untuk volume transaksi tinggi.',
        choosePlan: 'Pilih Paket',
        testimonialTag: 'Kisah Pelanggan',
        testimonialDotLabel: 'Testimoni',
        helpTag: 'Bantuan',
        helpTitle: 'Butuh Bantuan Implementasi?',
        helpDesc: 'Tim kami siap bantu orientasi awal, migrasi data, dan pengaturan alur kerja sesuai kebutuhan bisnismu.',
        liveChat: 'Obrolan Langsung',
        whatsappConsultation: 'Konsultasi lewat WhatsApp',
        emailSupport: 'Dukungan Email',
        productTour: 'Tur Produk',
        viewCoreFeatures: 'Lihat fitur inti Paperwork',
        contactSales: 'Hubungi Penjualan',
        footerTestimonial: 'Testimoni',
        featureMegaMenu: [
            { title: 'Invoice Otomatis', description: 'Buat invoice profesional dalam hitungan detik.', href: '#fitur', icon: 'ri:file-text-line' },
            { title: 'Penawaran Cepat', description: 'Konversi penawaran ke invoice tanpa input ulang.', href: '#fitur', icon: 'ri:briefcase-line' },
            { title: 'Manajemen Klien', description: 'Semua data klien tersimpan rapi dan terpusat.', href: '#fitur', icon: 'ri:account-circle-line' },
            { title: 'Laporan & Tracking', description: 'Pantau status pembayaran dan performa tim.', href: '#fitur', icon: 'ri:bar-chart-line' },
        ],
        solutionMegaMenu: [
            { title: 'Untuk Tim Keuangan', description: 'Otomasi alur penagihan dan kontrol arus kas.', href: '#solusi', icon: 'ri:wallet-3-line' },
            { title: 'Untuk Pemilik Bisnis', description: 'Lihat performa bisnis dan persetujuan lebih cepat.', href: '#solusi', icon: 'ri:building-line' },
            { title: 'Untuk Tim Penjualan', description: 'Kirim penawaran cepat dan tindak lanjut terukur.', href: '#solusi', icon: 'ri:focus-3-line' },
            { title: 'Untuk Multi Industri', description: 'Template fleksibel untuk berbagai jenis bisnis.', href: '#solusi', icon: 'ri:stack-line' },
        ],
        topStats: [
            { label: 'Inv Proses', value: '1000+' },
            { label: 'Quo Terkirim', value: '100+' },
            { label: 'Client Aktif', value: '30+' },
        ],
        partnerIndustries: ['Keuangan', 'Hospitalitas', 'Ritel', 'Kesehatan', 'Pendidikan', 'Teknologi', 'F&B', 'Manufaktur'],
        testimonials: [
            { quote: '“Tim kami sekarang tidak lagi bolak-balik spreadsheet. Penawaran dan invoice berjalan dalam satu alur, serta status pembayaran langsung terlihat.”', author: 'Lead Keuangan', company: 'Perusahaan Jasa' },
            { quote: '“Waktu pembuatan invoice turun drastis. Tim sales bisa langsung lanjut ke follow-up tanpa tunggu admin input ulang data.”', author: 'Manajer Operasional', company: 'Grup Ritel' },
            { quote: '“Sistemnya rapi untuk banyak klien. Pengingat pembayaran juga membuat arus kas lebih terkontrol tiap minggu.”', author: 'Pemilik Bisnis', company: 'Perusahaan Hospitalitas' },
        ],
        startNow: 'Mulai Sekarang',
        whatsapp: 'WhatsApp',
    },
    en: {
        nav: { feature: 'Features', solution: 'Solutions', help: 'Support', price: 'Pricing' },
        mobileNavTitle: 'NAVIGATION',
        language: 'Language',
        primaryAuth: 'Open Dashboard',
        primaryGuest: 'Try Free',
        secondaryAuth: 'Billing',
        secondaryGuest: 'Contact Sales',
        heroBadge: 'Jurnal-like solution, focused for growing teams',
        heroTitle: 'Manage Invoices, Quotations, and Payments in One Platform',
        heroDesc: 'Paperwork helps finance teams and business owners process documents faster, cleaner, and with less repetitive input.',
        heroChecks: ['Invoices & Quotations', 'Payment Tracking', 'Centralized Client Data'],
        summaryTitle: 'Financial Summary',
        active: 'Active',
        weeklyFlow: 'Weekly Transaction Flow',
        usedByIndustries: 'Used Across Industries',
        whyPaperwork: 'Why Paperwork',
        featureSectionTitle: 'Faster and More Measurable Finance Workflow',
        featureSectionDesc: 'One workspace for transaction documents, payment status, and client data management.',
        solutionSectionTag: 'Solutions',
        solutionSectionTitle: 'Designed for Every Business Role',
        pricingTag: 'Pricing',
        pricingTitle: 'Choose a Plan for Your Business Stage',
        pricingDesc: 'Start free for basics, then scale to enterprise for high transaction volume.',
        choosePlan: 'Choose Plan',
        testimonialTag: 'Customer Stories',
        testimonialDotLabel: 'Testimonial',
        helpTag: 'Support',
        helpTitle: 'Need Implementation Support?',
        helpDesc: 'Our team can help with onboarding, data migration, and workflow setup tailored to your business.',
        liveChat: 'Live Chat',
        whatsappConsultation: 'Consult via WhatsApp',
        emailSupport: 'Email Support',
        productTour: 'Product Tour',
        viewCoreFeatures: 'See Paperwork core features',
        contactSales: 'Contact Sales',
        footerTestimonial: 'Testimonials',
        featureMegaMenu: [
            { title: 'Automated Invoices', description: 'Create professional invoices in seconds.', href: '#fitur', icon: 'ri:file-text-line' },
            { title: 'Fast Quotations', description: 'Convert quotations to invoices without re-entry.', href: '#fitur', icon: 'ri:briefcase-line' },
            { title: 'Client Management', description: 'Keep all client data organized and centralized.', href: '#fitur', icon: 'ri:account-circle-line' },
            { title: 'Reports & Tracking', description: 'Monitor payment status and team performance.', href: '#fitur', icon: 'ri:bar-chart-line' },
        ],
        solutionMegaMenu: [
            { title: 'For Finance Teams', description: 'Automate billing flow and control cash flow.', href: '#solusi', icon: 'ri:wallet-3-line' },
            { title: 'For Business Owners', description: 'See business performance and approvals faster.', href: '#solusi', icon: 'ri:building-line' },
            { title: 'For Sales Teams', description: 'Send quotations quickly and follow up with clarity.', href: '#solusi', icon: 'ri:focus-3-line' },
            { title: 'For Multi-Industry', description: 'Flexible templates for many business types.', href: '#solusi', icon: 'ri:stack-line' },
        ],
        topStats: [
            { label: 'Inv In Progress', value: '1000+' },
            { label: 'Quo Sent', value: '100+' },
            { label: 'Active Clients', value: '30+' },
        ],
        partnerIndustries: ['Finance', 'Hospitality', 'Retail', 'Healthcare', 'Education', 'Technology', 'F&B', 'Manufacturing'],
        testimonials: [
            { quote: '“Our team no longer jumps between spreadsheets. Quotations and invoices run in one flow and payment status is instantly visible.”', author: 'Finance Lead', company: 'Service Company' },
            { quote: '“Invoice creation time dropped significantly. Sales can continue follow-ups without waiting for repeated manual entry.”', author: 'Operations Manager', company: 'Retail Group' },
            { quote: '“It is well-structured for many clients. Payment reminders keep our weekly cash flow under control.”', author: 'Business Owner', company: 'Hospitality Company' },
        ],
        startNow: 'Start Now',
        whatsapp: 'WhatsApp',
    },
    zh: {
        nav: { feature: '功能', solution: '方案', help: '帮助', price: '价格' },
        mobileNavTitle: '导航',
        language: '语言',
        primaryAuth: '打开仪表盘',
        primaryGuest: '免费试用',
        secondaryAuth: '账单',
        secondaryGuest: '联系销售',
        heroBadge: '类似 Jurnal 的方案，专为成长型团队打造',
        heroTitle: '在一个平台中管理发票、报价和付款',
        heroDesc: 'Paperwork 帮助财务团队和企业主更快、更整洁地处理文档，并减少重复输入。',
        heroChecks: ['发票与报价', '付款跟踪', '客户数据集中管理'],
        summaryTitle: '财务摘要',
        active: '活跃',
        weeklyFlow: '每周交易流',
        usedByIndustries: '覆盖多种行业',
        whyPaperwork: '为什么选择 Paperwork',
        featureSectionTitle: '更快且可衡量的财务工作流',
        featureSectionDesc: '一个工作区即可管理交易文档、付款状态和客户数据。',
        solutionSectionTag: '方案',
        solutionSectionTitle: '为每种业务角色而设计',
        pricingTag: '价格',
        pricingTitle: '按业务阶段选择套餐',
        pricingDesc: '可从免费版起步，高交易量可升级到企业版。',
        choosePlan: '选择套餐',
        testimonialTag: '客户故事',
        testimonialDotLabel: '评价',
        helpTag: '帮助',
        helpTitle: '需要实施支持吗？',
        helpDesc: '我们的团队可协助你完成上手培训、数据迁移和流程配置。',
        liveChat: '在线沟通',
        whatsappConsultation: '通过 WhatsApp 咨询',
        emailSupport: '邮件支持',
        productTour: '产品导览',
        viewCoreFeatures: '查看 Paperwork 核心功能',
        contactSales: '联系销售',
        footerTestimonial: '评价',
        featureMegaMenu: [
            { title: '自动发票', description: '几秒内创建专业发票。', href: '#fitur', icon: 'ri:file-text-line' },
            { title: '快速报价', description: '报价可直接转换为发票，无需重复录入。', href: '#fitur', icon: 'ri:briefcase-line' },
            { title: '客户管理', description: '客户数据统一整理与集中管理。', href: '#fitur', icon: 'ri:account-circle-line' },
            { title: '报表与追踪', description: '实时监控付款状态与团队绩效。', href: '#fitur', icon: 'ri:bar-chart-line' },
        ],
        solutionMegaMenu: [
            { title: '面向财务团队', description: '自动化收款流程并控制现金流。', href: '#solusi', icon: 'ri:wallet-3-line' },
            { title: '面向企业主', description: '更快掌握经营表现与审批进度。', href: '#solusi', icon: 'ri:building-line' },
            { title: '面向销售团队', description: '快速发送报价并高效跟进。', href: '#solusi', icon: 'ri:focus-3-line' },
            { title: '适配多行业', description: '灵活模板适用于多种业务类型。', href: '#solusi', icon: 'ri:stack-line' },
        ],
        topStats: [
            { label: '处理中发票', value: '1000+' },
            { label: '已发送报价', value: '100+' },
            { label: '活跃客户', value: '30+' },
        ],
        partnerIndustries: ['金融', '酒店', '零售', '医疗', '教育', '科技', '餐饮', '制造'],
        testimonials: [
            { quote: '“我们的团队不再来回切换表格。报价和发票在同一流程中完成，付款状态一目了然。”', author: '财务负责人', company: '服务公司' },
            { quote: '“发票制作时间大幅下降。销售团队无需等待重复录入即可继续跟进。”', author: '运营经理', company: '零售集团' },
            { quote: '“系统对多客户场景非常清晰。付款提醒让每周现金流更可控。”', author: '企业负责人', company: '酒店企业' },
        ],
        startNow: '立即开始',
        whatsapp: 'WhatsApp',
    },
};

const copy = computed(() => i18n[selectedLanguage.value] || i18n.id);

const primaryAction = computed(() =>
    props.isAuthenticated
        ? { label: copy.value.primaryAuth, href: route('dashboard') }
        : { label: copy.value.primaryGuest, href: route('login') }
);

const secondaryAction = computed(() =>
    props.isAuthenticated
        ? { label: copy.value.secondaryAuth, href: route('settings.billing') }
        : { label: copy.value.secondaryGuest, href: '#bantuan' }
);

const navLinks = computed(() => [
    { label: copy.value.nav.feature, href: '#fitur' },
    { label: copy.value.nav.solution, href: '#solusi' },
    { label: copy.value.nav.help, href: '#bantuan' },
    { label: copy.value.nav.price, href: '#harga' },
]);

const fiturMegaMenu = computed(() => copy.value.featureMegaMenu);
const solusiMegaMenu = computed(() => copy.value.solutionMegaMenu);

const partnerIndustries = computed(() => copy.value.partnerIndustries);

const heroChecks = computed(() => copy.value.heroChecks);
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

const testimonials = computed(() => copy.value.testimonials);

const topStats = computed(() => copy.value.topStats);

const translatePlanHighlight = (text) => {
    const value = String(text || '');

    if (selectedLanguage.value === 'id') return value;

    if (selectedLanguage.value === 'en') {
        return value
            .replace(/tanpa batas/gi, 'unlimited')
            .replace(/\bInvoice\b/gi, 'Invoices')
            .replace(/\bPenawaran\b/gi, 'Quotations')
            .replace(/\bKlien\b/gi, 'Clients')
            .replace(/\/\s*bulan/gi, '/ month')
            .replace(/\bbulan\b/gi, 'month');
    }

    return value
        .replace(/tanpa batas/gi, '无限制')
        .replace(/\bInvoice\b/gi, '发票')
        .replace(/\bPenawaran\b/gi, '报价')
        .replace(/\bKlien\b/gi, '客户')
        .replace(/\/\s*bulan/gi, '/ 月')
        .replace(/\bbulan\b/gi, '月');
};

const translatePlanPeriod = (period) => {
    const value = String(period || '');

    if (selectedLanguage.value === 'id') return value;
    if (selectedLanguage.value === 'en') {
        return value
            .replace(/\/\s*bulan/gi, '/ month')
            .replace(/\bbulan\b/gi, 'month');
    }

    return value
        .replace(/\/\s*bulan/gi, '/ 月')
        .replace(/\bbulan\b/gi, '月');
};

const splitPlanPrice = (price) => {
    const value = String(price || '').trim();
    const match = value.match(/^([^\d]*)([\d.,]+.*)$/);

    if (!match) {
        return {
            currency: '',
            amount: value,
        };
    }

    return {
        currency: (match[1] || '').trim(),
        amount: (match[2] || '').trim(),
    };
};

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

const languageSelectClass = computed(() =>
    isPastHero.value
        ? '!border-white !bg-white !text-slate-700 shadow-sm'
        : '!border-white/35 !bg-white/10 !text-white backdrop-blur-md shadow-[inset_0_1px_0_rgba(255,255,255,0.28)]'
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
    isPastHero.value = heroBottom <= 0;
};

onMounted(() => {
    testimonialTimer = window.setInterval(() => {
        testimonialIndex.value = (testimonialIndex.value + 1) % testimonials.value.length;
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
                            {{ copy.nav.feature }}
                            <Icon icon="ri:arrow-down-line" :width="12" :height="12" />
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
                            {{ copy.nav.solution }}
                            <Icon icon="ri:arrow-down-line" :width="12" :height="12" />
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
                        {{ copy.nav.help }}
                    </a>
                    <a
                        href="#harga"
                        class="inline-flex h-10 items-center text-xs font-semibold uppercase tracking-widest leading-none transition"
                        :class="navLinkClass"
                        @click="(event) => scrollToSection(event, '#harga')"
                    >
                        {{ copy.nav.price }}
                    </a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    <select
                        v-model="selectedLanguage"
                        class="appearance-none rounded-xl border px-3 py-2.5 pr-8 text-xs font-semibold uppercase tracking-widest transition outline-none"
                        :class="languageSelectClass"
                        style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>'); background-repeat: no-repeat; background-position: right 10px center;"
                    >
                        <option value="id" class="text-slate-700">Indonesia</option>
                        <option value="en" class="text-slate-700">English</option>
                        <option value="zh" class="text-slate-700">Mandarin</option>
                    </select>
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
                    aria-label="Buka menu"
                >
                    <Icon icon="ri:menu-line" :width="18" :height="18" />
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
                    <p class="text-sm font-bold tracking-wide text-[#0b2d6b]">{{ copy.mobileNavTitle }}</p>
                    <button type="button" class="rounded-lg bg-slate-100 p-2 text-slate-600" @click="closeMenu">
                        <Icon icon="ri:close-line" :width="16" :height="16" />
                    </button>
                </div>

                <div class="mb-5">
                    <label class="mb-1 ml-1 block text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ copy.language }}</label>
                    <select
                        v-model="selectedLanguage"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs font-semibold uppercase tracking-widest text-slate-700 outline-none"
                    >
                        <option value="id">Indonesia</option>
                        <option value="en">English</option>
                        <option value="zh">Mandarin</option>
                    </select>
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
                        <Icon icon="ri:arrow-right-line" :width="14" :height="14" />
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
                            <Icon icon="ri:flashlight-line" :width="12" :height="12" />
                            {{ copy.heroBadge }}
                        </p>
                        <h1 class="hero-title mt-5 text-4xl font-extrabold leading-tight md:text-5xl">
                            {{ copy.heroTitle }}
                        </h1>
                        <p class="mt-4 max-w-xl text-sm leading-relaxed text-white">
                            {{ copy.heroDesc }}
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
                                <Icon icon="ri:check-line" :width="14" :height="14" class="text-[#8fffd4]" />
                                <span>{{ check }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-white/20 bg-white/95 p-6 text-slate-900 shadow-2xl shadow-[#0a2558]/40">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold text-[#0b2d6b]">{{ copy.summaryTitle }}</p>
                            <span class="rounded-lg bg-emerald-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-emerald-600">{{ copy.active }}</span>
                        </div>
                        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div v-for="stat in topStats" :key="stat.label" class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ stat.label }}</p>
                                <p class="mt-2 text-xl font-extrabold text-[#0b2d6b]">{{ stat.value }}</p>
                            </div>
                        </div>
                        <div class="mt-5 rounded-xl border border-slate-100 bg-white p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-xs font-semibold text-slate-500">{{ copy.weeklyFlow }}</p>
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
                    <p class="text-center text-[10px] font-semibold uppercase tracking-widest text-slate-400">{{ copy.usedByIndustries }}</p>
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
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0b2d6b]">{{ copy.whyPaperwork }}</p>
                    <h2 class="mt-2 text-3xl font-extrabold text-[#0b2d6b]">{{ copy.featureSectionTitle }}</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-sm text-slate-600">{{ copy.featureSectionDesc }}</p>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="module in spotlightFeatures"
                        :key="module.title"
                        class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#e9f1ff] text-[#0b2d6b]">
                            <Icon icon="ri:file-list-3-line" :width="18" :height="18" />
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">{{ module.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ module.description }}</p>
                    </article>
                </div>
            </section>

            <section id="solusi" class="mx-auto mt-16 max-w-7xl px-6">
                <div class="rounded-[28px] border border-slate-200 bg-white p-7 shadow-sm md:p-10">
                    <div class="text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0b2d6b]">{{ copy.solutionSectionTag }}</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-slate-900">{{ copy.solutionSectionTitle }}</h2>
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
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0b2d6b]">{{ copy.pricingTag }}</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-slate-900">{{ copy.pricingTitle }}</h2>
                        <p class="mt-3 text-sm text-slate-600">{{ copy.pricingDesc }}</p>
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
                                <template v-if="splitPlanPrice(plan.price).currency">
                                    <span class="text-sm font-normal text-slate-500">{{ splitPlanPrice(plan.price).currency }}</span>
                                </template>
                                <span class="text-2xl font-extrabold text-slate-900">{{ splitPlanPrice(plan.price).amount }}</span>
                                <span class="text-xs font-semibold text-slate-400">{{ translatePlanPeriod(plan.period) }}</span>
                            </div>

                            <ul class="mt-4 space-y-2">
                                <li v-for="item in plan.highlights" :key="item" class="flex items-start gap-2 text-xs font-semibold text-slate-600">
                                    <Icon icon="ri:check-line" :width="14" :height="14" class="mt-0.5 text-emerald-500" />
                                    <span>{{ translatePlanHighlight(item) }}</span>
                                </li>
                            </ul>

                            <Link
                                :href="primaryAction.href"
                                class="mt-6 inline-flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-xs font-semibold uppercase tracking-widest transition"
                                :class="plan.name === 'Pro' ? 'bg-[#0b2d6b] text-white hover:bg-[#0a2558]' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            >
                                {{ copy.choosePlan }}
                            </Link>
                        </article>
                    </div>
                </div>
            </section>

            <section id="testimoni" class="mx-auto mt-16 max-w-5xl px-6 pb-16">
                <div class="rounded-[28px] bg-gradient-to-br from-[#0b2d6b] to-[#2c79dd] p-8 text-white shadow-2xl shadow-[#1b4e9f]/30 md:p-10">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-white/90">{{ copy.testimonialTag }}</p>
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
                            class="inline-flex h-5 w-5 items-center justify-center rounded-full transition-all"
                            :class="testimonialIndex === index ? 'bg-white/20' : 'bg-white/10 hover:bg-white/20'"
                            @click="testimonialIndex = index"
                            :aria-label="`${copy.testimonialDotLabel} ${index + 1}`"
                        >
                            <Icon
                                icon="ri:record-circle-fill"
                                :width="testimonialIndex === index ? 10 : 8"
                                :height="testimonialIndex === index ? 10 : 8"
                                :class="testimonialIndex === index ? 'text-white' : 'text-white/40'"
                            />
                        </button>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            :href="primaryAction.href"
                            class="rounded-xl bg-[#ff8a00] px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#ec7d00]"
                        >
                            {{ copy.startNow }}
                        </Link>
                        <a href="https://wa.me/6285183440300" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-white/20">
                            {{ copy.contactSales }}
                        </a>
                    </div>
                </div>
            </section>

            <section id="bantuan" class="mx-auto mt-2 max-w-7xl px-6 pb-16">
                <div class="rounded-[28px] border border-slate-200 bg-white p-7 shadow-sm md:p-10">
                    <div class="text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-[#0b2d6b]">{{ copy.helpTag }}</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-slate-900">{{ copy.helpTitle }}</h2>
                        <p class="mx-auto mt-3 max-w-2xl text-sm text-slate-600">{{ copy.helpDesc }}</p>
                    </div>
                    <div class="mt-7 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <a href="https://wa.me/6285183440300" target="_blank" rel="noopener noreferrer" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-[#0b2d6b]/30 hover:bg-white">
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ copy.liveChat }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ copy.whatsappConsultation }}</p>
                        </a>
                        <a href="mailto:support@paperwork.local" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-[#0b2d6b]/30 hover:bg-white">
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ copy.emailSupport }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">support@paperwork.local</p>
                        </a>
                        <a href="#fitur" class="rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-[#0b2d6b]/30 hover:bg-white" @click="(event) => scrollToSection(event, '#fitur')">
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ copy.productTour }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ copy.viewCoreFeatures }}</p>
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
                    <a href="#fitur" class="hover:text-white" @click="(event) => scrollToSection(event, '#fitur')">{{ copy.nav.feature }}</a>
                    <a href="#harga" class="hover:text-white" @click="(event) => scrollToSection(event, '#harga')">{{ copy.nav.price }}</a>
                    <a href="#testimoni" class="hover:text-white" @click="(event) => scrollToSection(event, '#testimoni')">{{ copy.footerTestimonial }}</a>
                </div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-white/70">© {{ currentYear }} Paperwork</p>
            </div>
        </footer>

        <a
            href="https://wa.me/085710999144"
            target="_blank"
            rel="noopener noreferrer"
            class="fixed bottom-6 right-6 z-[70] inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-4 py-3 text-xs font-semibold uppercase tracking-widest text-white shadow-xl shadow-[#25D366]/35 transition hover:scale-[1.02] hover:bg-[#1fb85a]"
            aria-label="Hubungi via WhatsApp"
        >
            <Icon icon="logos:whatsapp-icon" :width="18" :height="18" />
            <span>{{ copy.whatsapp }}</span>
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
