<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
});

const submit = () => {
    form.put(route('profile.update'));
};
</script>

<template>
    <AppLayout>
        <Head title="Edit Profile" />

        <div class="max-w-4xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Personal Settings</h1>
                <p class="text-slate-500 text-sm">Update your personal information and contact details.</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xl shadow-slate-200/20 overflow-hidden">
                <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 rounded-full bg-[#07304a] flex items-center justify-center text-white text-2xl font-bold">
                            {{ form.name.charAt(0) }}
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Profile Information</h2>
                            <p class="text-xs font-semibold text-[#07304a] uppercase tracking-widest">Public profile details</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Display Name</label>
                            <div class="relative group">
                                <input 
                                    v-model="form.name" 
                                    type="text" 
                                    required
                                    class="w-full rounded-xl border-none bg-slate-50 px-12 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                />
                                <Icon icon="si:user-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#07304a]"  />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Email Address</label>
                            <div class="relative group">
                                <input 
                                    v-model="form.email" 
                                    type="email" 
                                    required
                                    class="w-full rounded-xl border-none bg-slate-50 px-12 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none"
                                />
                                <Icon icon="si:mail-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#07304a]"  />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex items-center gap-3 rounded-xl bg-[#07304a] px-10 py-5 text-sm font-bold text-white shadow-2xl shadow-[#07304a]/30 transition-all hover:bg-[#002d66] hover:-translate-y-1 active:scale-95 disabled:opacity-50"
                        >
                            <Icon icon="si:archive-line" :width="18" :height="18"  />
                            <span>Save Profile</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
