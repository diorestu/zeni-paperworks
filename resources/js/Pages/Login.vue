<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#fcfcfd] flex flex-col items-center justify-center pt-6 px-6 pb-5 font-sans">
        <Head title="Login" />
        <!-- Logo -->
        <div class="mb-[15px]">
            <img src="/img/logo/logo_text_blue.png" alt="Paperwork Logo" class="h-14 w-auto">
        </div>

        <!-- Main Card -->
        <div class="w-full max-w-[480px] bg-white rounded-[2.5rem] border border-slate-200/60 p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-slate-100">
            <div class="text-center mb-10">
                <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-3">
                    Sign in to your Paperwork
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
                        class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-5 py-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
                        :class="{'border-rose-500': form.errors.email}"
                        tabindex="1"
                        required
                    />
                    <p v-if="form.errors.email" class="text-[10px] font-semibold text-rose-500 ml-1">{{ form.errors.email }}</p>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between px-1">
                        <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Password</label>
                        <a class="text-[10px] font-semibold uppercase tracking-widest text-[#07304a] hover:underline" href="#" tabindex="4">Forgot?</a>
                    </div>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="••••••••"
                            class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-5 py-4 pr-12 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
                            :class="{'border-rose-500': form.errors.password}"
                            tabindex="2"
                            required
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            tabindex="-1"
                        >
                            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.477 10.48a3 3 0 004.243 4.243M9.88 9.88A3 3 0 0114.12 14.12M6.228 6.228C4.484 7.676 3.146 9.5 2 12c2.667 5.333 6.667 8 10 8 1.772 0 3.544-.622 5.186-1.866M9.12 4.36A9.93 9.93 0 0112 4c3.333 0 7.333 2.667 10 8-.722 1.443-1.54 2.69-2.44 3.75" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2 12c2.667-5.333 6.667-8 10-8s7.333 2.667 10 8c-2.667 5.333-6.667 8-10 8s-7.333-2.667-10-8z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="text-[10px] font-semibold text-rose-500 ml-1">{{ form.errors.password }}</p>
                </div>

                <div class="flex items-center gap-3 px-1">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        id="remember"
                        class="h-4 w-4 rounded-lg border-slate-200 text-[#07304a] focus:ring-[#07304a] cursor-pointer"
                    />
                    <label for="remember" class="text-xs font-semibold text-slate-500 cursor-pointer">Remember me for 30 days</label>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-2xl bg-[#07304a] px-6 py-4.5 text-sm font-semibold text-white shadow-xl shadow-[#07304a]/20 transition-all hover:bg-[#012b60] hover:-translate-y-0.5 active:scale-95 disabled:opacity-50"
                    tabindex="3"
                >
                    {{ form.processing ? 'Signing in...' : 'Sign in to Account' }}
                </button>
            </form>

        </div>

        <!-- Footer Info -->
        <div class="mt-10 max-w-[480px] text-center space-y-4">
            <p class="text-xs font-semibold text-slate-500">
                New to Paperwork?
                <Link :href="route('register')" class="text-[#07304a] hover:underline">Create account</Link>
            </p>
            <p class="text-[11px] font-normal text-slate-400 px-10 leading-relaxed">
                By signing in, you agree to create an account and accept Paperwork's 
                <span class="text-[#07304a] font-semibold cursor-pointer hover:underline">Terms of Use</span> and 
                <span class="text-[#07304a] font-semibold cursor-pointer hover:underline">Privacy Policy</span>.
            </p>
        </div>
    </div>
</template>
