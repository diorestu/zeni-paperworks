<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    taxes: Array,
    modelValue: Array, // Array of selected tax IDs
});

const emit = defineEmits(['update:modelValue', 'close']);

const selectedTaxes = ref([...(props.modelValue || [])]);

const toggleTax = (taxId) => {
    const index = selectedTaxes.value.indexOf(taxId);
    if (index > -1) {
        selectedTaxes.value.splice(index, 1);
    } else {
        selectedTaxes.value.push(taxId);
    }
};

const isTaxSelected = (taxId) => {
    return selectedTaxes.value.includes(taxId);
};

const applySelection = () => {
    emit('update:modelValue', selectedTaxes.value);
    emit('close');
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="$emit('close')">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden" @click.stop>
            <!-- Header -->
            <div class="bg-[#023e8a] px-6 py-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Icon icon="si:checklist-line" :width="24" :height="24" class="text-white"  />
                    <h3 class="text-lg font-semibold text-white">Select Taxes</h3>
                </div>
                <button @click="$emit('close')" class="text-white/70 hover:text-white transition-colors">
                    <Icon icon="si:close-line" :width="24" :height="24"  />
                </button>
            </div>

            <!-- Tax List -->
            <div class="p-6 max-h-96 overflow-y-auto">
                <div v-if="taxes.length === 0" class="text-center py-12">
                    <Icon icon="si:checklist-line" :width="48" :height="48" class="mx-auto text-slate-300 mb-4"  />
                    <p class="text-sm font-semibold text-slate-900">No taxes available</p>
                    <p class="text-xs text-slate-400 mt-1">Configure taxes in Settings first.</p>
                </div>
                <div v-else class="space-y-3">
                    <button
                        v-for="tax in taxes"
                        :key="tax.id"
                        @click="toggleTax(tax.id)"
                        :class="[
                            'w-full p-4 rounded-xl border-2 transition-all text-left',
                            isTaxSelected(tax.id)
                                ? 'border-[#023e8a] bg-[#023e8a]/5'
                                : 'border-slate-100 hover:border-slate-200'
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-semibold text-slate-900">{{ tax.name }}</h4>
                                    <span 
                                        class="px-2 py-0.5 rounded-md text-xs font-semibold"
                                        :class="tax.type === 'add' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                                    >
                                        {{ tax.type === 'add' ? '+' : '-' }}{{ tax.rate }}%
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">
                                    {{ tax.type === 'add' ? 'Penambah' : 'Pengurang' }}
                                </p>
                            </div>
                            <div 
                                :class="[
                                    'flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all',
                                    isTaxSelected(tax.id)
                                        ? 'border-[#023e8a] bg-[#023e8a]'
                                        : 'border-slate-300'
                                ]"
                            >
                                <Icon icon="si:check-line" v-if="isTaxSelected(tax.id)" :width="14" :height="14" class="text-white"  />
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                <button 
                    @click="$emit('close')"
                    class="flex-1 px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all"
                >
                    Cancel
                </button>
                <button 
                    @click="applySelection"
                    class="flex-1 px-4 py-3 text-sm font-semibold text-white bg-[#023e8a] hover:bg-[#002d66] rounded-xl transition-all shadow-lg shadow-[#023e8a]/20"
                >
                    Apply
                </button>
            </div>
        </div>
    </div>
</template>
