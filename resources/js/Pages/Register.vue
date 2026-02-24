<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Icon } from '@iconify/vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#fcfcfd] flex flex-col items-center justify-center pt-4 px-4 pb-4 font-sans">
        <Head title="Register" />

        <div class="mb-3">
            <img src="/img/logo/logo_colorful.png" alt="Paperwork Logo" class="h-12 w-auto">
        </div>

        <div class="w-full max-w-[500px] bg-white rounded-[2rem] border border-slate-200/60 p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-slate-100">
            <div class="text-center mb-7">
                <h1 class="text-xl font-semibold text-slate-900 tracking-tight mb-2">
                    Create your Paperwork account
                </h1>
                <p class="text-[13px] font-normal text-slate-500 leading-relaxed">
                    Start managing quotations and invoices in one workspace.
                </p>
            </div>

            <a
                :href="route('auth.google.redirect')"
                class="mb-5 inline-flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-[13px] font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                <svg class="h-4 w-4" viewBox="0 0 533.5 544.3" aria-hidden="true">
                    <path fill="#4285F4" d="M533.5 278.4c0-18.5-1.5-36.1-4.7-53.2H272v100.7h146.9c-6.3 34.1-25 63-53.4 82.4v68h86.3c50.6-46.6 81.7-115.3 81.7-197.9z"/>
                    <path fill="#34A853" d="M272 544.3c73.5 0 135.2-24.4 180.2-66.3l-86.3-68c-24 16.1-54.8 25.6-93.9 25.6-72 0-133-48.6-154.8-113.9h-89.2v71.6c44.8 89 137.2 150.9 243.9 150.9z"/>
                    <path fill="#FBBC04" d="M117.2 321.7c-5.5-16.1-8.7-33.4-8.7-51.7s3.2-35.6 8.7-51.7v-71.6h-89.2C10.1 182.8 0 225.3 0 270s10.1 87.2 28 123.3l89.2-71.6z"/>
                    <path fill="#EA4335" d="M272 107.7c40 0 75.9 13.8 104.2 40.8l78.2-78.2C407.1 24.9 345.5 0 272 0 165.3 0 72.9 61.8 28 150.9l89.2 71.6C139 156.2 200 107.7 272 107.7z"/>
                </svg>
                Continue with Google
            </a>

            <div class="mb-5 flex items-center gap-3">
                <span class="h-px flex-1 bg-slate-200"></span>
                <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">or</span>
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Full Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Your full name"
                        class="w-full rounded-xl border border-slate-100 bg-slate-50/50 px-4 py-3 text-[13px] font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
                        :class="{ 'border-rose-500': form.errors.name }"
                        required
                    />
                    <p v-if="form.errors.name" class="text-[10px] font-semibold text-rose-500 ml-1">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Email address</label>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="you@company.com"
                        class="w-full rounded-xl border border-slate-100 bg-slate-50/50 px-4 py-3 text-[13px] font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
                        :class="{ 'border-rose-500': form.errors.email }"
                        required
                    />
                    <p v-if="form.errors.email" class="text-[10px] font-semibold text-rose-500 ml-1">{{ form.errors.email }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Password</label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="••••••••"
                            class="w-full rounded-xl border border-slate-100 bg-slate-50/50 px-4 py-3 pr-11 text-[13px] font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
                            :class="{ 'border-rose-500': form.errors.password }"
                            required
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            tabindex="-1"
                        >
                            <Icon v-if="showPassword" icon="si:eye-off-line" :width="20" :height="20" />
                            <Icon v-else icon="si:eye-line" :width="20" :height="20" />
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="text-[10px] font-semibold text-rose-500 ml-1">{{ form.errors.password }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Confirm Password</label>
                    <div class="relative">
                        <input
                            v-model="form.password_confirmation"
                            :type="showPasswordConfirmation ? 'text' : 'password'"
                            placeholder="••••••••"
                            class="w-full rounded-xl border border-slate-100 bg-slate-50/50 px-4 py-3 pr-11 text-[13px] font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
                            required
                        />
                        <button
                            type="button"
                            @click="showPasswordConfirmation = !showPasswordConfirmation"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            :aria-label="showPasswordConfirmation ? 'Hide password confirmation' : 'Show password confirmation'"
                            tabindex="-1"
                        >
                            <Icon v-if="showPasswordConfirmation" icon="si:eye-off-line" :width="20" :height="20" />
                            <Icon v-else icon="si:eye-line" :width="20" :height="20" />
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-[#07304a] px-5 py-3.5 text-[13px] font-semibold text-white shadow-xl shadow-[#07304a]/20 transition-all hover:bg-[#012b60] hover:-translate-y-0.5 active:scale-95 disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating account...' : 'Create Account' }}
                </button>

                <p class="text-center text-xs font-semibold text-slate-500">
                    Already have an account?
                    <Link :href="route('login')" class="text-[#07304a] hover:underline">Sign in</Link>
                </p>
            </form>
        </div>
    </div>
</template>
