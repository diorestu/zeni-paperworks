<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
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
        title: 'Welcome',
        description: 'We will help you complete the initial setup so you can get started quickly.',
        points: ['Set up your business profile', 'Add your first client', 'Start creating invoices'],
    },
    {
        title: 'How Paperwork Works',
        description: 'Main flow: Quotation -> Invoice -> Payment.',
        points: ['Create client and product data', 'Prepare quotations or invoices', 'Track payment status'],
    },
    {
        title: 'Ready to Use',
        description: 'You can continue to the dashboard and start your daily operations.',
        points: ['Check insights in the dashboard', 'Manage transaction documents', 'Configure business settings'],
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
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Hello {{ userName || 'New User' }}, welcome</h1>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500">
                    Step {{ step + 1 }} of {{ steps.length }}
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
                    Back
                </button>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('settings.index')"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Settings
                    </Link>

                    <button
                        v-if="step < steps.length - 1"
                        type="button"
                        class="rounded-xl bg-[#07304a] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0a3f61]"
                        @click="next"
                    >
                        Next
                    </button>
                    <button
                        v-else
                        type="button"
                        class="rounded-xl bg-[#07304a] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0a3f61] disabled:opacity-60"
                        :disabled="form.processing"
                        @click="complete"
                    >
                        {{ form.processing ? 'Processing...' : 'Finish & Go to Dashboard' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
