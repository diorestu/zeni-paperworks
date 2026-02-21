<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Autocomplete from '@/Components/Autocomplete.vue';
import { ref, computed } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    clients: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    industry_sector: '',
    address: '',
});

const editForm = useForm({
    id: null,
    name: '',
    email: '',
    phone: '',
    company: '',
    industry_sector: '',
    address: '',
});

const showCreate = ref(false);
const showEdit = ref(false);
const searchQuery = ref('');
const sortConfig = ref({ key: 'name', direction: 'asc' });

const processing = computed(() => form.processing || editForm.processing);

const filteredClients = computed(() => {
    let result = [...(props.clients || [])];

    // Search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(client => 
            client.name?.toLowerCase().includes(query) ||
            client.email?.toLowerCase().includes(query) ||
            client.company?.toLowerCase().includes(query)
        );
    }

    // Sort
    result.sort((a, b) => {
        const aVal = a[sortConfig.value.key] || '';
        const bVal = b[sortConfig.value.key] || '';
        if (aVal < bVal) return sortConfig.value.direction === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortConfig.value.direction === 'asc' ? 1 : -1;
        return 0;
    });

    return result;
});

const toggleSort = (key) => {
    if (sortConfig.value.key === key) {
        sortConfig.value.direction = sortConfig.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sortConfig.value.key = key;
        sortConfig.value.direction = 'asc';
    }
};

const startCreate = () => {
    form.reset();
    showCreate.value = true;
};

const startEdit = (client) => {
    editForm.id = client.id;
    editForm.name = client.name;
    editForm.email = client.email;
    editForm.phone = client.phone;
    editForm.company = client.company;
    editForm.industry_sector = client.industry_sector || '';
    editForm.address = client.address;
    showEdit.value = true;
};

const industryOptions = [
    'Farm',
    'Health',
    'Finance',
    'Hospitality',
    'Retail',
    'Education',
    'Technology',
    'Manufacturing',
    'Construction',
    'Transportation',
    'Real Estate',
    'Professional Services',
    'Government',
    'Non-Profit',
    'Other',
];

const closeModals = () => {
    showCreate.value = false;
    showEdit.value = false;
    form.clearErrors();
    editForm.clearErrors();
};

const submitCreate = () => {
    form.post('/clients', {
        onSuccess: () => {
            form.reset();
            showCreate.value = false;
        },
    });
};

const submitUpdate = () => {
    if (!editForm.id) return;
    editForm.put(`/clients/${editForm.id}`, {
        onSuccess: () => {
            showEdit.value = false;
            editForm.reset();
        },
    });
};

const deleteClient = () => {
    if (!editForm.id) return;
    if (!confirm('Delete this client permanently?')) return;
    router.delete(`/clients/${editForm.id}`, {
        onSuccess: () => {
            showEdit.value = false;
            editForm.reset();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Clients" />
        
        <!-- Header Section -->
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900 tracking-tight">Clients</h1>
                <p class="text-slate-500 font-normal">Manage and track all your registered clients.</p>
            </div>
            <button
                class="group relative flex items-center gap-2 overflow-hidden rounded-xl bg-[#07304a] px-8 py-4 text-sm font-semibold text-white shadow-2xl shadow-[#07304a]/30 transition-all hover:-translate-y-1 hover:bg-[#002d66] active:scale-95"
                @click="startCreate"
            >
                <span class="relative z-10 flex items-center gap-2">
                    <Icon icon="si:add-line" :width="18" :height="18"  />
                    <span>Add New Client</span>
                </span>
                <div class="absolute inset-0 z-0 bg-gradient-to-r from-transparent via-white/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
            </button>
        </div>

        <div v-if="$page.props.flash.status" class="mb-8 rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-xs font-semibold text-emerald-600">
            {{ $page.props.flash.status }}
        </div>
        <div v-if="$page.props.flash.error" class="mb-8 rounded-xl bg-rose-50 border border-rose-100 p-4 text-xs font-semibold text-rose-600">
            {{ $page.props.flash.error }}
        </div>

        <!-- Controls Section -->
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="relative w-full md:w-96 group">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by name, email or tax number..."
                    class="w-full rounded-xl border-none bg-slate-50 px-12 py-4 text-sm font-normal text-slate-700 shadow-sm ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] focus:bg-white transition-all outline-none"
                />
                <Icon icon="si:search-line" :width="18" :height="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#07304a] transition-colors"  />
                <button 
                    v-if="searchQuery" 
                    @click="searchQuery = ''"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-semibold px-2 py-1 bg-white rounded-md text-xs border border-slate-100 shadow-sm"
                >
                    CLEAR
                </button>
            </div>

            <div class="flex items-center gap-2 px-4 py-2 bg-slate-50 rounded-lg border border-slate-100">
                <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Total</span>
                <span class="text-sm font-semibold text-[#07304a]">{{ filteredClients.length }}</span>
            </div>
        </div>

        <!-- Table Card -->
        <div class="overflow-hidden rounded-[1.5rem] border border-slate-100 bg-slate-50 shadow-xl shadow-slate-200/20">
            <div class="overflow-x-auto bg-white/50 backdrop-blur-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100/30">
                            <th @click="toggleSort('name')" class="group cursor-pointer px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400 select-none hover:text-[#07304a] transition-colors">
                                <div class="flex items-center gap-2">
                                    Name
                                    <span class="transition-all" :class="sortConfig.key === 'name' ? 'opacity-100 text-[#07304a]' : 'opacity-0 group-hover:opacity-100'">
                                        <Icon icon="si:arrow-upward-line" v-if="sortConfig.key === 'name' && sortConfig.direction === 'asc'" :width="14" :height="14"  />
                                        <Icon icon="si:arrow-downward-line" v-else-if="sortConfig.key === 'name' && sortConfig.direction === 'desc'" :width="14" :height="14"  />
                                        <Icon icon="si:sort-line" v-else :width="14" :height="14"  />
                                    </span>
                                </div>
                            </th>
                            <th @click="toggleSort('email')" class="group cursor-pointer px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400 select-none hover:text-[#07304a] transition-colors">
                                <div class="flex items-center gap-2">
                                    Email Address
                                    <span class="transition-all" :class="sortConfig.key === 'email' ? 'opacity-100 text-[#07304a]' : 'opacity-0 group-hover:opacity-100'">
                                        <Icon icon="si:arrow-upward-line" v-if="sortConfig.key === 'email' && sortConfig.direction === 'asc'" :width="14" :height="14"  />
                                        <Icon icon="si:arrow-downward-line" v-else-if="sortConfig.key === 'email' && sortConfig.direction === 'desc'" :width="14" :height="14"  />
                                        <Icon icon="si:sort-line" v-else :width="14" :height="14"  />
                                    </span>
                                </div>
                            </th>
                            <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Phone</th>
                            <th @click="toggleSort('company')" class="group cursor-pointer px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400 select-none hover:text-[#07304a] transition-colors">
                                <div class="flex items-center gap-2">
                                    Tax Number
                                    <span class="transition-all" :class="sortConfig.key === 'company' ? 'opacity-100 text-[#07304a]' : 'opacity-0 group-hover:opacity-100'">
                                        <Icon icon="si:arrow-upward-line" v-if="sortConfig.key === 'company' && sortConfig.direction === 'asc'" :width="14" :height="14"  />
                                        <Icon icon="si:arrow-downward-line" v-else-if="sortConfig.key === 'company' && sortConfig.direction === 'desc'" :width="14" :height="14"  />
                                        <Icon icon="si:sort-line" v-else :width="14" :height="14"  />
                                    </span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/50 bg-white">
                        <tr
                            v-for="client in filteredClients"
                            :key="client.id"
                            class="group relative cursor-pointer hover:bg-slate-50 transition-colors"
                            @click="startEdit(client)"
                        >
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-50 font-semibold text-[#07304a] group-hover:bg-[#07304a] group-hover:text-white transition-all duration-300">
                                        {{ client.name.charAt(0) }}
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900 group-hover:text-[#07304a] transition-colors">{{ client.name }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-sm text-slate-500 font-normal">{{ client.email || '—' }}</td>
                            <td class="px-8 py-6 text-sm text-slate-500 font-normal">{{ client.phone || '—' }}</td>
                            <td class="px-8 py-6">
                                <span class="rounded-md bg-sky-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-sky-600 border border-sky-100 group-hover:bg-sky-100 transition-colors">
                                    {{ client.company || '—' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!filteredClients.length" class="flex flex-col items-center justify-center py-20 px-4 bg-white">
                    <Icon icon="si:archive-line" :width="48" :height="48" class="text-slate-200 mb-4"  />
                    <p class="text-sm font-semibold text-slate-400">No matching clients found.</p>
                    <p class="text-xs text-slate-300 mt-1">Try adjusting your search query.</p>
                </div>
            </div>
        </div>

        <!-- Modals and Transitions -->
        <Teleport to="body">
            <transition name="fade">
                <div
                    v-if="showCreate || showEdit"
                    class="fixed inset-0 z-[60] bg-slate-900/40 backdrop-blur-sm"
                    @click.self="closeModals"
                ></div>
            </transition>

            <transition name="scale-fade">
                <div
                    v-if="showCreate || showEdit"
                    class="fixed inset-0 z-[70] flex items-center justify-center overflow-y-auto px-4 py-4 pointer-events-none"
                >
                    <div class="pointer-events-auto w-full max-w-xl rounded-xl bg-white p-6 shadow-2xl relative border border-slate-100 flex flex-col gap-6 max-h-[calc(100vh-7rem)] overflow-y-auto">
                        <div v-if="processing" class="absolute inset-0 rounded-xl bg-white/70 backdrop-blur-sm flex items-center justify-center z-10 animate-fade-in">
                            <div class="h-12 w-12 animate-spin rounded-full border-4 border-[#07304a] border-t-transparent"></div>
                        </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.3em] text-[#07304a]/60">
                                {{ showCreate ? 'Creation flow' : 'Update records' }}
                            </p>
                            <h3 class="mt-1 text-2xl font-semibold text-slate-900 tracking-tight">
                                {{ showCreate ? 'New Profile' : 'Edit Client' }}
                            </h3>
                        </div>
                        <button class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all active:scale-90" @click="closeModals">
                            <Icon icon="si:close-line" :width="20" :height="20"  />
                        </button>
                    </div>

                    <form
                        v-if="showCreate"
                        class="grid grid-cols-2 gap-x-6 gap-y-8"
                        @submit.prevent="submitCreate"
                    >
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Full Legal Name</label>
                            <input v-model="form.name" required class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="e.g. Christopher Nolan" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Email Address</label>
                            <input v-model="form.email" type="email" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="name@domain.com" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Phone Number</label>
                            <input v-model="form.phone" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="+1 (555) 000-0000" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Tax Number</label>
                            <input v-model="form.company" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="Tax number" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Industry Sector</label>
                            <Autocomplete
                                v-model="form.industry_sector"
                                :items="industryOptions.map(option => ({ name: option }))"
                                item-label="name"
                                placeholder="Select industry"
                                input-class="rounded-xl px-6 pr-10 py-4 text-sm font-semibold"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Physical Address</label>
                            <textarea v-model="form.address" rows="3" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none resize-none" placeholder="123 Street Ave."></textarea>
                        </div>
                        <div class="col-span-2 flex justify-end pt-4">
                            <button type="submit" class="flex items-center gap-3 rounded-xl bg-[#07304a] px-10 py-5 text-sm font-semibold text-white shadow-2xl shadow-[#07304a]/30 transition-all hover:bg-[#002d66] hover:-translate-y-1 active:scale-95">
                                <Icon icon="si:archive-line" :width="18" :height="18"  />
                                <span>Save Profile</span>
                            </button>
                        </div>
                    </form>

                    <form
                        v-else
                        class="grid grid-cols-2 gap-x-6 gap-y-8"
                        @submit.prevent="submitUpdate"
                    >
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Full Legal Name</label>
                            <input v-model="editForm.name" required class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="e.g. Christopher Nolan" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Email Address</label>
                            <input v-model="editForm.email" type="email" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="name@domain.com" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Phone Number</label>
                            <input v-model="editForm.phone" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="+1 (555) 000-0000" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Tax Number</label>
                            <input v-model="editForm.company" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="Tax number" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Industry Sector</label>
                            <Autocomplete
                                v-model="editForm.industry_sector"
                                :items="industryOptions.map(option => ({ name: option }))"
                                item-label="name"
                                placeholder="Select industry"
                                input-class="rounded-xl px-6 pr-10 py-4 text-sm font-semibold"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Physical Address</label>
                            <textarea v-model="editForm.address" rows="3" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none resize-none" placeholder="123 Street Ave."></textarea>
                        </div>
                        <div class="col-span-2 flex justify-between pt-4">
                            <button
                                type="button"
                                class="flex items-center gap-3 rounded-xl bg-rose-50 px-8 py-5 text-sm font-semibold text-rose-500 hover:bg-rose-100 transition-all"
                                @click="deleteClient"
                            >
                                <Icon icon="si:bin-line" :width="18" :height="18"  />
                                <span>Delete Client</span>
                            </button>
                            <button type="submit" class="flex items-center gap-3 rounded-xl bg-[#07304a] px-10 py-5 text-sm font-semibold text-white shadow-2xl shadow-[#07304a]/30 transition-all hover:bg-[#002d66] hover:-translate-y-1 active:scale-95">
                                <Icon icon="si:archive-line" :width="18" :height="18"  />
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
            </transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 300ms ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
.scale-fade-enter-active,
.scale-fade-leave-active {
    transition: opacity 300ms cubic-bezier(0.16, 1, 0.3, 1), transform 500ms cubic-bezier(0.16, 1, 0.3, 1);
}
.scale-fade-enter-from,
.scale-fade-leave-to {
    opacity: 0;
    transform: translateY(40px) scale(0.95) rotateX(10deg);
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
.animate-fade-in {
    animation: fade-in 200ms ease-out forwards;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #07304a;
}
</style>
