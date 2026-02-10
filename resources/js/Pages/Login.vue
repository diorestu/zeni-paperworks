<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#fcfcfd] flex flex-col items-center justify-center p-6 font-sans">
        <Head title="Login" />
        <!-- Logo -->
        <div class="mb-12">
            <img src="/img/logo/logo_text_blue.png" alt="Paperwork Logo" class="h-10 w-auto">
        </div>

        <!-- Main Card -->
        <div class="w-full max-w-[480px] bg-white rounded-[2.5rem] border border-slate-200/60 p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-slate-100">
            <div class="text-center mb-10">
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-[#023e8a] mb-6">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#023e8a]"></span>
                    Trusted workspace access
                </div>
                <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-3">
                    Sign in to your Paperwork workspace
                </h1>
                <p class="text-sm font-normal text-slate-500 leading-relaxed">
                    Keep your teams in sync with a single dashboard. Continue with your email to unlock personalized insights.
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Email address</label>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="you@company.com"
                        class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-5 py-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#023e8a] focus:ring-4 focus:ring-[#023e8a]/5"
                        :class="{'border-rose-500': form.errors.email}"
                        required
                    />
                    <p v-if="form.errors.email" class="text-[10px] font-semibold text-rose-500 ml-1">{{ form.errors.email }}</p>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Password</label>
                        <a class="text-[10px] font-semibold uppercase tracking-widest text-[#023e8a] hover:underline" href="#">Forgot?</a>
                    </div>
                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="••••••••"
                        class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-5 py-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#023e8a] focus:ring-4 focus:ring-[#023e8a]/5"
                        :class="{'border-rose-500': form.errors.password}"
                        required
                    />
                    <p v-if="form.errors.password" class="text-[10px] font-semibold text-rose-500 ml-1">{{ form.errors.password }}</p>
                </div>

                <div class="flex items-center gap-3 px-1">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        id="remember"
                        class="h-4 w-4 rounded-lg border-slate-200 text-[#023e8a] focus:ring-[#023e8a] cursor-pointer"
                    />
                    <label for="remember" class="text-xs font-semibold text-slate-500 cursor-pointer">Remember me for 30 days</label>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-2xl bg-[#023e8a] px-6 py-4.5 text-sm font-semibold text-white shadow-xl shadow-[#023e8a]/20 transition-all hover:bg-[#012b60] hover:-translate-y-0.5 active:scale-95 disabled:opacity-50"
                >
                    {{ form.processing ? 'Signing in...' : 'Sign in to Account' }}
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-slate-50 flex items-center justify-center gap-6">
                <div class="text-center">
                    <p class="text-xs font-semibold text-slate-900">24</p>
                    <p class="text-[9px] font-semibold uppercase tracking-widest text-slate-400 mt-0.5">Active teams</p>
                </div>
                <div class="h-6 w-px bg-slate-100"></div>
                <div class="text-center">
                    <p class="text-xs font-semibold text-slate-900">99.9%</p>
                    <p class="text-[9px] font-semibold uppercase tracking-widest text-slate-400 mt-0.5">Uptime</p>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="mt-10 max-w-[480px] text-center space-y-4">
            <p class="text-[11px] font-normal text-slate-400 px-10 leading-relaxed">
                By signing in, you agree to create an account and accept Paperwork's 
                <span class="text-[#023e8a] font-semibold cursor-pointer hover:underline">Terms of Use</span> and 
                <span class="text-[#023e8a] font-semibold cursor-pointer hover:underline">Privacy Policy</span>.
            </p>

            <div class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-100 px-4 py-2.5 text-[10px] font-semibold uppercase tracking-widest text-slate-500 shadow-sm">
                <span class="bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded text-[8px]">TIP</span>
                Explore the <a href="/dashboard" class="text-[#023e8a] hover:underline">dashboard preview</a>.
            </div>
        </div>
    </div>
</template>
