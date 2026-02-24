<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-50 px-6 py-12" style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">
        <Head title="Privacy Policy" />

        <div class="mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-slate-900">Privacy Policy</h1>
                <Link :href="route('landing')" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Back to Home
                </Link>
            </div>

            <div class="space-y-6 text-sm leading-relaxed text-slate-600">
                <section>
                    <h2 class="text-base font-semibold text-slate-900">1. Information We Collect</h2>
                    <p class="mt-2">We collect account information such as name, email, company details, and transactional data required to provide Paperwork services.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">2. How We Use Information</h2>
                    <p class="mt-2">Your data is used to operate the platform, generate invoices and quotations, support billing, security, and customer support.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">3. Data Security</h2>
                    <p class="mt-2">We apply reasonable technical and organizational safeguards to protect data from unauthorized access and misuse.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">4. Third-Party Services</h2>
                    <p class="mt-2">We may use trusted third-party providers (such as payment gateways and email providers) to deliver parts of our services.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-900">5. Contact</h2>
                    <p class="mt-2">If you have questions about this policy, contact us at <a href="mailto:support@paperwork.local" class="font-semibold text-[#0b2d6b]">support@paperwork.local</a>.</p>
                </section>
            </div>
        </div>
    </div>
</template>
