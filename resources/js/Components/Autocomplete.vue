<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    },
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    itemLabel: {
        type: String,
        default: 'name'
    },
    showAddOption: {
        type: Boolean,
        default: false
    },
    addOptionLabel: {
        type: String,
        default: 'Add'
    },
    inputClass: {
        type: String,
        default: 'rounded-lg px-3 py-3 text-sm font-semibold'
    }
});

const emit = defineEmits(['update:modelValue', 'select', 'add']);

const searchQuery = ref(props.modelValue);
const isOpen = ref(false);
const activeIndex = ref(-1);
const inputRef = ref(null);
const listRef = ref(null);

watch(() => props.modelValue, (newValue) => {
    if (newValue !== searchQuery.value) {
        searchQuery.value = newValue;
    }
});

const filteredItems = computed(() => {
    if (!searchQuery.value) return [];
    const query = searchQuery.value.toLowerCase();
    
    // exact match check to avoid showing dropdown if already selected exactly? 
    // actually user might want to see other options.
    
    return props.items.filter(item => 
        item[props.itemLabel].toLowerCase().includes(query)
    ).slice(0, 5); // Limit to 5 results
});

const onInput = (e) => {
    searchQuery.value = e.target.value;
    emit('update:modelValue', e.target.value);
    isOpen.value = true;
    activeIndex.value = -1;
};

const selectItem = (item) => {
    searchQuery.value = item[props.itemLabel];
    emit('update:modelValue', item[props.itemLabel]);
    emit('select', item);
    isOpen.value = false;
};

const onKeydown = (e) => {
    if (!isOpen.value) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex.value = (activeIndex.value + 1) % filteredItems.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex.value = (activeIndex.value - 1 + filteredItems.value.length) % filteredItems.value.length;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeIndex.value >= 0 && filteredItems.value[activeIndex.value]) {
            selectItem(filteredItems.value[activeIndex.value]);
        }
    } else if (e.key === 'Escape') {
        isOpen.value = false;
    }
};

// Close on outside click
const closeOnClickOutside = (e) => {
    if (inputRef.value && !inputRef.value.contains(e.target) && listRef.value && !listRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeOnClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', closeOnClickOutside);
});
</script>

<template>
    <div class="relative w-full">
        <div class="relative">
            <input
                ref="inputRef"
                type="text"
                :value="searchQuery"
                @input="onInput"
                @focus="isOpen = true"
                @keydown="onKeydown"
                :placeholder="placeholder"
                :class="['w-full bg-slate-50 border-none text-slate-900 ring-1 ring-slate-100 focus:ring-2 focus:ring-[#023e8a] transition-all outline-none shadow-sm', inputClass]"
            >
             <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                <Icon icon="si:expand-more-line" :width="16" :height="16"  />
            </div>
        </div>

        <div 
            v-if="isOpen && (filteredItems.length > 0 || showAddOption)"
            ref="listRef"
            class="absolute z-10 w-full mt-1 bg-white rounded-xl border border-slate-100 shadow-xl overflow-hidden"
        >
            <ul>
                <li
                    v-if="showAddOption"
                    @click="emit('add')"
                    class="px-4 py-3 cursor-pointer transition-colors text-xs font-semibold uppercase tracking-widest text-[#023e8a] hover:bg-slate-50 border-b border-slate-100"
                >
                    {{ addOptionLabel }}
                </li>
                <li 
                    v-for="(item, index) in filteredItems" 
                    :key="index"
                    @click="selectItem(item)"
                    class="px-4 py-3 cursor-pointer transition-colors text-sm font-semibold text-slate-700"
                    :class="{ 'bg-slate-50 text-[#023e8a]': index === activeIndex, 'hover:bg-slate-50': index !== activeIndex }"
                >
                    {{ item[itemLabel] }}
                    <span v-if="item.price" class="float-right text-xs text-slate-400 font-normal">Rp{{ item.price.toLocaleString('id-ID') }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
