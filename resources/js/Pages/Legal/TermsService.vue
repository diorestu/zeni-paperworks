<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-50 px-6 py-12" style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">
        <Head title="Terms of Service" />

        <div class="mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-slate-900">Terms of Service</h1>
                <Link :href="route('landing')" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Back to Home
                </Link>
            </div>

            <div class="space-y-6 text-sm leading-relaxed text-slate-600">
                <section>
                    <h2 class="text-base font-semibold text-slate-900">1. Acceptance of Terms</h2>
                    <p class="mt-2">By accessing and using Paperwork, you agree to comply with these Terms of Service and all applicable laws.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">2. Account Responsibilities</h2>
                    <p class="mt-2">You are responsible for maintaining account confidentiality and ensuring all account activities are authorized.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">3. Service Usage</h2>
                    <p class="mt-2">You agree not to misuse the platform, disrupt operations, or use Paperwork for unlawful activities.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">4. Billing and Subscription</h2>
                    <p class="mt-2">Paid plans are billed based on selected cycles. You are responsible for timely payments and any applicable taxes.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">5. Contact</h2>
                    <p class="mt-2">For legal or service-related concerns, contact us at <a href="mailto:support@paperwork.local" class="font-semibold text-[#0b2d6b]">support@paperwork.local</a>.</p>
                </section>
            </div>
        </div>
    </div>
</template>
