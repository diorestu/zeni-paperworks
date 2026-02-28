<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    plan: String,
    billingCycle: String,
    unitPrice: Number,
    totalAmount: Number,
    midtransEnabled: Boolean,
});

const processing = ref(false);
const errorMessage = ref('');
const snapLoading = ref(false);

const isYearly = computed(() => props.billingCycle === 'yearly');

const formatCurrency = (amount) => `Rp${Number(amount || 0).toLocaleString('id-ID')}`;

const waitForSnapReady = async (timeoutMs = 8000) => {
    const startedAt = Date.now();

    while (Date.now() - startedAt < timeoutMs) {
        if (typeof window.snap?.pay === 'function') {
            return true;
        }

        await new Promise((resolve) => setTimeout(resolve, 100));
    }

    return false;
};

const ensureSnapLoaded = async () => {
    if (typeof window.snap?.pay === 'function') {
        return true;
    }

    if (snapLoading.value) {
        return waitForSnapReady();
    }

    snapLoading.value = true;

    try {
        const { data } = await window.axios.get(route('settings.billing.snap-config'));
        const snapUrl = String(data?.snap_url || '');
        const clientKey = String(data?.client_key || '');

        if (!snapUrl || !clientKey) {
            throw new Error('Midtrans Snap configuration is missing.');
        }

        const existingScript = document.getElementById('midtrans-snap-script');
        if (!existingScript) {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.id = 'midtrans-snap-script';
                script.src = snapUrl;
                script.async = true;
                script.setAttribute('data-client-key', clientKey);
                script.onload = () => {
                    script.removeAttribute('data-client-key');
                    resolve(true);
                };
                script.onerror = () => reject(new Error('Failed to load Midtrans Snap.js'));
                document.head.appendChild(script);
            });
        }

        return waitForSnapReady();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Failed to load Midtrans Snap.js.';
        return false;
    } finally {
        snapLoading.value = false;
    }
};

const startPayment = async () => {
    errorMessage.value = '';

    if (!props.midtransEnabled) {
        errorMessage.value = 'Midtrans is not configured. Please set MIDTRANS_SERVER_KEY and MIDTRANS_CLIENT_KEY.';
        return;
    }

    const snapReady = await ensureSnapLoaded();
    if (!snapReady) {
        errorMessage.value = errorMessage.value || 'Midtrans Snap.js is not loaded. Please try again.';
        return;
    }

    processing.value = true;

    try {
        const { data } = await window.axios.post(route('settings.billing.checkout'), {
            plan: props.plan,
            billing_cycle: props.billingCycle,
        });

        if (!data?.snap_token || !data?.order_id) {
            throw new Error('Snap token is not available.');
        }

        window.snap.pay(data.snap_token, {
            onSuccess: async () => {
                await confirmAndRedirect(data.order_id);
            },
            onPending: async () => {
                await confirmAndRedirect(data.order_id, true);
            },
            onError: () => {
                processing.value = false;
                errorMessage.value = 'Payment could not be processed. Please try again shortly.';
            },
            onClose: () => {
                processing.value = false;
            },
        });
    } catch (error) {
        processing.value = false;
        errorMessage.value = error?.response?.data?.message ?? 'Failed to create Midtrans transaction.';
    }
};

const confirmAndRedirect = async (orderId, pending = false) => {
    try {
        const { data } = await window.axios.post(route('settings.billing.confirm'), {
            order_id: orderId,
        });

        if (pending || !data?.is_paid) {
            router.visit(route('settings.billing'), {
                preserveState: false,
                replace: true,
            });
            return;
        }

        router.visit(route('settings.billing.success', { order_id: orderId, status: 'paid' }), {
            preserveState: false,
            replace: true,
        });
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Transaction created, but status confirmation failed.';
    } finally {
        processing.value = false;
    }
};

onMounted(async () => {
    if (!props.midtransEnabled) return;
    await ensureSnapLoaded();
});
</script>

<template>
    <AppLayout>
        <Head title="Checkout Payment" />

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('settings.billing')" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-all">
                    <Icon icon="ri:arrow-left-line" :width="18" :height="18" />
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Payment Checkout</h1>
                    <p class="text-sm text-slate-500">Review your plan details before continuing to payment.</p>
                </div>
            </div>

            <div v-if="errorMessage" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ errorMessage }}
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Plan</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ plan }}</p>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-[#07304a]">
                            {{ isYearly ? 'Annually' : 'Monthly' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Unit Price</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ formatCurrency(unitPrice) }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ isYearly ? 'per month (billed annually)' : 'per month' }}</p>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-[#07304a] px-5 py-4 text-white">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-widest text-white/70">Total Payment</p>
                        <p class="text-2xl font-semibold text-white">{{ formatCurrency(totalAmount) }}</p>
                    </div>
                    <p class="mt-1 text-xs text-white/70">{{ isYearly ? 'Billed yearly' : 'Billed monthly' }}</p>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        @click="startPayment"
                        :disabled="processing || snapLoading"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#07304a] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#0a3f61] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <Icon icon="ri:wallet-3-line" :width="16" :height="16" />
                        {{ snapLoading ? 'Preparing Payment...' : (processing ? 'Processing...' : 'Pay Now') }}
                    </button>
                    <Link :href="route('settings.billing')" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Back to Billing
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
