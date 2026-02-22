<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    userName: {
        type: String,
        default: '',
    },
});

const step = ref(0);
const steps = [
    {
        title: 'Selamat Datang',
        description: 'Kami bantu kamu setup awal supaya langsung siap dipakai.',
        points: ['Atur profil bisnis', 'Tambahkan klien pertama', 'Mulai buat invoice'],
    },
    {
        title: 'Cara Kerja Paperwork',
        description: 'Alur utama: Penawaran -> Invoice -> Pembayaran.',
        points: ['Buat data klien dan produk', 'Susun penawaran atau invoice', 'Pantau status pembayaran'],
    },
    {
        title: 'Siap Digunakan',
        description: 'Kamu bisa lanjut ke dashboard dan mulai operasional harian.',
        points: ['Cek insight di dashboard', 'Kelola dokumen transaksi', 'Atur pengaturan bisnis'],
    },
];

const current = computed(() => steps[step.value]);
const form = useForm({});

const next = () => {
    if (step.value < steps.length - 1) {
        step.value += 1;
    }
};

const prev = () => {
    if (step.value > 0) {
        step.value -= 1;
    }
};

const complete = () => {
    form.post(route('onboarding.complete'));
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 px-4 py-8 font-sans">
        <Head title="Onboarding" />

        <div class="mx-auto max-w-3xl rounded-3xl border border-slate-200 bg-white p-8 shadow-sm md:p-10">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-[#07304a]">Onboarding</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Halo {{ userName || 'Pengguna Baru' }}, selamat datang</h1>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500">
                    Langkah {{ step + 1 }} dari {{ steps.length }}
                </div>
            </div>

            <div class="mb-6 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-[#07304a] transition-all duration-300" :style="{ width: `${((step + 1) / steps.length) * 100}%` }"></div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                <h2 class="text-xl font-semibold text-slate-900">{{ current.title }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ current.description }}</p>

                <ul class="mt-5 space-y-3">
                    <li v-for="point in current.points" :key="point" class="flex items-start gap-2 text-sm text-slate-700">
                        <Icon icon="si:check-line" :width="16" :height="16" class="mt-0.5 text-emerald-500" />
                        <span>{{ point }}</span>
                    </li>
                </ul>
            </div>

            <div class="mt-8 flex items-center justify-between">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                    :disabled="step === 0"
                    @click="prev"
                >
                    Kembali
                </button>

                <button
                    v-if="step < steps.length - 1"
                    type="button"
                    class="rounded-xl bg-[#07304a] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0a3f61]"
                    @click="next"
                >
                    Lanjut
                </button>
                <button
                    v-else
                    type="button"
                    class="rounded-xl bg-[#07304a] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0a3f61] disabled:opacity-60"
                    :disabled="form.processing"
                    @click="complete"
                >
                    {{ form.processing ? 'Memproses...' : 'Selesai & Masuk Dashboard' }}
                </button>
            </div>
        </div>
    </div>
</template>
