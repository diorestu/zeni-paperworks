<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    currency: {
        type: String,
        default: 'IDR',
    },
});

const form = useForm({
    name: '',
    sku: '',
    price: '',
    description: '',
});

const editForm = useForm({
    id: null,
    name: '',
    sku: '',
    price: '',
    description: '',
});

const showCreate = ref(false);
const showEdit = ref(false);
const searchQuery = ref('');
const sortConfig = ref({ key: 'name', direction: 'asc' });

const processing = computed(() => form.processing || editForm.processing);
const currencyCode = computed(() => (props.currency || 'IDR').toUpperCase());
const currencyLocale = computed(() => {
    const localeByCurrency = {
        IDR: 'id-ID',
        USD: 'en-US',
        EUR: 'de-DE',
        SGD: 'en-SG',
    };

    return localeByCurrency[currencyCode.value] || 'id-ID';
});

const filteredProducts = computed(() => {
    let result = [...(props.products || [])];

    // Search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(product => 
            product.name?.toLowerCase().includes(query) ||
            product.sku?.toLowerCase().includes(query) ||
            product.description?.toLowerCase().includes(query)
        );
    }

    // Sort
    result.sort((a, b) => {
        let aVal = a[sortConfig.value.key];
        let bVal = b[sortConfig.value.key];

        // Handle numeric values for price
        if (sortConfig.value.key === 'price') {
            aVal = Number(aVal) || 0;
            bVal = Number(bVal) || 0;
        } else {
            aVal = (aVal || '').toString().toLowerCase();
            bVal = (bVal || '').toString().toLowerCase();
        }

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

const startEdit = (product) => {
    editForm.id = product.id;
    editForm.name = product.name;
    editForm.sku = product.sku;
    editForm.price = product.price;
    editForm.description = product.description;
    showEdit.value = true;
};

const closeModals = () => {
    showCreate.value = false;
    showEdit.value = false;
    form.clearErrors();
    editForm.clearErrors();
};

const submitCreate = () => {
    form.post('/products', {
        onSuccess: () => {
            form.reset();
            showCreate.value = false;
        },
    });
};

const submitUpdate = () => {
    if (!editForm.id) return;
    editForm.put(`/products/${editForm.id}`, {
        onSuccess: () => {
            showEdit.value = false;
            editForm.reset();
        },
    });
};

const deleteProduct = () => {
    if (!editForm.id) return;
    if (!confirm('Delete this product?')) return;
    router.delete(`/products/${editForm.id}`, {
        onSuccess: () => {
            showEdit.value = false;
            editForm.reset();
        },
    });
};

const formatPrice = (value) =>
    new Intl.NumberFormat(currencyLocale.value, { style: 'currency', currency: currencyCode.value }).format(Number(value || 0));
</script>

<template>
    <AppLayout>
        <Head title="Products" />
        
        <!-- Header Section -->
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.3em] text-[#07304a]/60">Stock management</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900 tracking-tight">Product Catalog</h1>
            </div>
            <button
                class="group relative flex items-center gap-2 overflow-hidden rounded-xl bg-[#07304a] px-8 py-4 text-sm font-semibold text-white shadow-2xl shadow-[#07304a]/30 transition-all hover:-translate-y-1 hover:bg-[#002d66] active:scale-95"
                @click="startCreate"
            >
                <span class="relative z-10 flex items-center gap-2">
                    <Icon icon="si:inventory-line" :width="18" :height="18"  />
                    <span>Create New Item</span>
                </span>
                <div class="absolute inset-0 z-0 bg-gradient-to-r from-transparent via-white/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
            </button>
        </div>

        <!-- Controls Section -->
        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="relative w-full md:w-96 group">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search by name, SKU or details..."
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
                <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Inventory Count</span>
                <span class="text-sm font-bold text-[#07304a]">{{ filteredProducts.length }}</span>
            </div>
        </div>

        <!-- Product Grid / Table -->
        <div class="overflow-hidden rounded-[1.5rem] border border-slate-100 bg-slate-50 shadow-xl shadow-slate-200/20">
            <div class="overflow-x-auto bg-white/50 backdrop-blur-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100/30">
                            <th @click="toggleSort('name')" class="group cursor-pointer px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400 select-none hover:text-[#07304a] transition-colors">
                                <div class="flex items-center gap-2">
                                    Product Name
                                    <span class="transition-all" :class="sortConfig.key === 'name' ? 'opacity-100 text-[#07304a]' : 'opacity-0 group-hover:opacity-100'">
                                        <Icon icon="si:arrow-upward-line" v-if="sortConfig.key === 'name' && sortConfig.direction === 'asc'" :width="14" :height="14"  />
                                        <Icon icon="si:arrow-downward-line" v-else-if="sortConfig.key === 'name' && sortConfig.direction === 'desc'" :width="14" :height="14"  />
                                        <Icon icon="si:sort-line" v-else :width="14" :height="14"  />
                                    </span>
                                </div>
                            </th>
                            <th @click="toggleSort('sku')" class="group cursor-pointer px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400 select-none hover:text-[#07304a] transition-colors">
                                <div class="flex items-center gap-2">
                                    SKU Code
                                    <span class="transition-all" :class="sortConfig.key === 'sku' ? 'opacity-100 text-[#07304a]' : 'opacity-0 group-hover:opacity-100'">
                                        <Icon icon="si:arrow-upward-line" v-if="sortConfig.key === 'sku' && sortConfig.direction === 'asc'" :width="14" :height="14"  />
                                        <Icon icon="si:arrow-downward-line" v-else-if="sortConfig.key === 'sku' && sortConfig.direction === 'desc'" :width="14" :height="14"  />
                                        <Icon icon="si:sort-line" v-else :width="14" :height="14"  />
                                    </span>
                                </div>
                            </th>
                            <th @click="toggleSort('price')" class="group cursor-pointer px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400 select-none hover:text-[#07304a] transition-colors">
                                <div class="flex items-center gap-2">
                                    Unit Price
                                    <span class="transition-all" :class="sortConfig.key === 'price' ? 'opacity-100 text-[#07304a]' : 'opacity-0 group-hover:opacity-100'">
                                        <Icon icon="si:arrow-upward-line" v-if="sortConfig.key === 'price' && sortConfig.direction === 'asc'" :width="14" :height="14"  />
                                        <Icon icon="si:arrow-downward-line" v-else-if="sortConfig.key === 'price' && sortConfig.direction === 'desc'" :width="14" :height="14"  />
                                        <Icon icon="si:sort-line" v-else :width="14" :height="14"  />
                                    </span>
                                </div>
                            </th>
                            <th class="px-8 py-5 text-[10px] font-semibold uppercase tracking-widest text-slate-400">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/50 bg-white">
                        <tr
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="group relative cursor-pointer hover:bg-slate-50 transition-colors"
                            @click="startEdit(product)"
                        >
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-50 font-semibold text-[#07304a] group-hover:bg-[#07304a] group-hover:text-white transition-all duration-300">
                                        {{ product.name.charAt(0) }}
                                    </div>
                                    <span class="font-semibold text-slate-900 group-hover:text-[#07304a] transition-colors">{{ product.name }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="bg-slate-100 text-[10px] font-semibold px-2 py-1 rounded text-slate-500 font-mono">
                                    {{ product.sku }}
                                </span>
                            </td>
                            <td class="px-8 py-6 font-semibold text-slate-900">{{ formatPrice(product.price) }}</td>
                            <td class="px-8 py-6 text-sm text-slate-500 font-normal truncate max-w-xs">{{ product.description || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!filteredProducts.length" class="flex flex-col items-center justify-center py-20 px-4 bg-white">
                    <Icon icon="si:inventory-line" :width="48" :height="48" class="text-slate-200 mb-4"  />
                    <p class="text-lg font-semibold text-slate-400">No matching products found.</p>
                    <p class="text-sm text-slate-300 mt-1">Try refining your search terms.</p>
                </div>
            </div>
        </div>

        <!-- Modals -->
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
                class="fixed inset-0 z-[70] flex items-center justify-center px-4 pointer-events-none"
            >
                <div class="pointer-events-auto w-full max-w-2xl rounded-[2rem] bg-white p-10 shadow-2xl relative border border-slate-100 flex flex-col gap-8">
                    <div v-if="processing" class="absolute inset-0 rounded-[2rem] bg-white/70 backdrop-blur-sm flex items-center justify-center z-10 animate-fade-in">
                        <div class="h-12 w-12 animate-spin rounded-full border-4 border-[#07304a] border-t-transparent"></div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.3em] text-[#07304a]/60">
                                {{ showCreate ? 'Inventory addition' : 'Asset modification' }}
                            </p>
                            <h3 class="mt-1 text-2xl font-semibold text-slate-900 tracking-tight">
                                {{ showCreate ? 'New Asset' : 'Edit Product' }}
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
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Display Name</label>
                            <input v-model="form.name" required class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="e.g. Premium Consultation" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">SKU Identification</label>
                            <input v-model="form.sku" required class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="SKU-001" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Selling Price ({{ currencyCode }})</label>
                            <input v-model="form.price" type="number" step="0.01" min="0" required class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="0.00" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Detailed Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none resize-none" placeholder="Brief details for indexing..."></textarea>
                        </div>
                        <div class="col-span-2 flex justify-end pt-4">
                            <button type="submit" class="flex items-center gap-3 rounded-xl bg-[#07304a] px-10 py-5 text-sm font-semibold text-white shadow-2xl shadow-[#07304a]/30 transition-all hover:bg-[#002d66] hover:-translate-y-1 active:scale-95">
                                <Icon icon="si:archive-line" :width="18" :height="18"  />
                                <span>Add to Catalog</span>
                            </button>
                        </div>
                    </form>

                    <form
                        v-else
                        class="grid grid-cols-2 gap-x-6 gap-y-8"
                        @submit.prevent="submitUpdate"
                    >
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Display Name</label>
                            <input v-model="editForm.name" required class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-200 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="e.g. Premium Consultation" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">SKU Identification</label>
                            <input v-model="editForm.sku" required class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="SKU-001" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Selling Price ({{ currencyCode }})</label>
                            <input v-model="editForm.price" type="number" step="0.01" min="0" required class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none" placeholder="0.00" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2 px-1">Detailed Description</label>
                            <textarea v-model="editForm.description" rows="3" class="w-full rounded-xl border-none bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#07304a] transition-all outline-none resize-none" placeholder="Brief details for indexing..."></textarea>
                        </div>
                        <div class="col-span-2 flex justify-between pt-4">
                            <button
                                type="button"
                                class="flex items-center gap-3 rounded-xl bg-rose-50 px-8 py-5 text-sm font-semibold text-rose-500 hover:bg-rose-100 transition-all font-sans"
                                @click="deleteProduct"
                            >
                                <Icon icon="si:bin-line" :width="18" :height="18"  />
                                <span>Retire Asset</span>
                            </button>
                            <button type="submit" class="flex items-center gap-3 rounded-xl bg-[#07304a] px-10 py-5 text-sm font-semibold text-white shadow-2xl shadow-[#07304a]/30 transition-all hover:bg-[#002d66] hover:-translate-y-1 active:scale-95">
                                <Icon icon="si:archive-line" :width="18" :height="18"  />
                                <span>Save Item Changes</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
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
