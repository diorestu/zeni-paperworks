<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    orderId: String,
    status: String,
});
</script>

<template>
    <AppLayout>
        <Head title="Payment Success" />

        <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center shadow-sm">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <Icon icon="si:check-circle-line" :width="34" :height="34" />
                </div>
                <h1 class="text-2xl font-semibold text-emerald-700">Pembayaran Berhasil</h1>
                <p class="mt-3 text-sm text-emerald-700/80">
                    Paket kamu sudah diperbarui. Terima kasih sudah melakukan pembayaran.
                </p>
                <p v-if="orderId" class="mt-4 text-xs font-semibold uppercase tracking-widest text-emerald-700/70">
                    Order ID: {{ orderId }}
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Link :href="route('settings.billing')" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                        <Icon icon="si:wallet-line" :width="16" :height="16" />
                        Kembali ke Billing
                    </Link>
                    <Link :href="route('dashboard')" class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-white px-5 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-100/60">
                        Ke Dashboard
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
