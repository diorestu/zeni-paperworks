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
    <div class="min-h-screen bg-[#fcfcfd] flex flex-col items-center justify-center pt-6 px-6 pb-5 font-sans">
        <Head title="Register" />

        <div class="mb-[15px]">
            <img src="/img/logo/logo_colorful.png" alt="Paperwork Logo" class="h-14 w-auto">
        </div>

        <div class="w-full max-w-[520px] bg-white rounded-[2.5rem] border border-slate-200/60 p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-1 ring-slate-100">
            <div class="text-center mb-10">
                <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-3">
                    Create your Paperwork account
                </h1>
                <p class="text-sm font-normal text-slate-500 leading-relaxed">
                    Start managing quotations and invoices in one workspace.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Full Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Your full name"
                        class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-5 py-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
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
                        class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-5 py-4 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
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
                            class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-5 py-4 pr-12 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
                            :class="{ 'border-rose-500': form.errors.password }"
                            required
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
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
                            class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-5 py-4 pr-12 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-[#07304a] focus:ring-4 focus:ring-[#07304a]/5"
                            required
                        />
                        <button
                            type="button"
                            @click="showPasswordConfirmation = !showPasswordConfirmation"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
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
                    class="w-full rounded-2xl bg-[#07304a] px-6 py-4.5 text-sm font-semibold text-white shadow-xl shadow-[#07304a]/20 transition-all hover:bg-[#012b60] hover:-translate-y-0.5 active:scale-95 disabled:opacity-50"
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
