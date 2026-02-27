<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    status: {
        type: Number,
        default: 500,
    },
});

const title = computed(() => ({
    403: 'Access Denied',
    404: 'Page Not Found',
    419: 'Session Expired',
    429: 'Too Many Requests',
    500: 'Server Error',
    503: 'Service Unavailable',
}[props.status] || 'Error'));

const description = computed(() => ({
    403: 'You do not have permission to access this page.',
    404: 'The page you are looking for does not exist.',
    419: 'Your session has expired. Please refresh and try again.',
    429: 'Too many requests. Please wait a moment and retry.',
    500: 'Something went wrong on our side.',
    503: 'Service is temporarily unavailable.',
}[props.status] || 'An unexpected error occurred.'));
</script>

<template>
    <Head :title="`${status} ${title}`" />

    <div class="min-h-screen bg-slate-50 px-6 py-16" style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">
        <div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Paperwork</p>
            <p class="mt-4 text-4xl font-semibold text-[#0b2d6b]">{{ status }}</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ title }}</h1>
            <p class="mt-3 text-sm text-slate-600">{{ description }}</p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <Link :href="route('landing')" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <Icon icon="ri:home-4-line" :width="14" :height="14" />
                    Back to Home
                </Link>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#0b2d6b] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#0a2558]" @click="window.history.back()">
                    <Icon icon="ri:arrow-left-line" :width="14" :height="14" />
                    Go Back
                </button>
            </div>
        </div>
    </div>
</template>
