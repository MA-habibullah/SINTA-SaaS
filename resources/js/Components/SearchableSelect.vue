<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, null],
    default: ''
  },
  options: {
    type: Array,
    default: () => []
  },
  placeholder: {
    type: String,
    default: 'Pilih opsi...'
  },
  searchPlaceholder: {
    type: String,
    default: 'Cari opsi...'
  },
  valueKey: {
    type: String,
    default: 'id'
  },
  labelKey: {
    type: String,
    default: 'nama'
  },
  subLabelKey: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  allowClear: {
    type: Boolean,
    default: false
  },
  customClass: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const searchQuery = ref('')
const searchInputRef = ref(null)
const dropdownRef = ref(null)
const highlightedIndex = ref(-1)

// Normalize options to unified objects
const normalizedOptions = computed(() => {
  return props.options.map(opt => {
    if (typeof opt === 'string' || typeof opt === 'number') {
      return {
        value: opt,
        label: String(opt),
        subLabel: '',
        raw: opt
      }
    }
    const val = opt[props.valueKey] !== undefined ? opt[props.valueKey] : (opt.value !== undefined ? opt.value : opt.id)
    const lbl = opt[props.labelKey] !== undefined ? opt[props.labelKey] : (opt.label || opt.nama || opt.nama_kategori || opt.name || String(val))
    const sub = props.subLabelKey && opt[props.subLabelKey] ? opt[props.subLabelKey] : (opt.deskripsi || opt.sla_hours ? `SLA ≤ ${opt.sla_hours} Jam` : '')
    return {
      value: val,
      label: String(lbl),
      subLabel: String(sub || ''),
      raw: opt
    }
  })
})

// Filtered options based on search query
const filteredOptions = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return normalizedOptions.value
  return normalizedOptions.value.filter(opt =>
    opt.label.toLowerCase().includes(q) ||
    (opt.subLabel && opt.subLabel.toLowerCase().includes(q))
  )
})

// Currently selected option object
const selectedOption = computed(() => {
  if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
    return null
  }
  return normalizedOptions.value.find(opt => String(opt.value) === String(props.modelValue)) || null
})

function toggleDropdown() {
  if (props.disabled) return
  if (isOpen.value) {
    closeDropdown()
  } else {
    openDropdown()
  }
}

function openDropdown() {
  isOpen.value = true
  searchQuery.value = ''
  highlightedIndex.value = -1
  nextTick(() => {
    if (searchInputRef.value) {
      searchInputRef.value.focus()
    }
  })
}

function closeDropdown() {
  isOpen.value = false
  searchQuery.value = ''
  highlightedIndex.value = -1
}

function selectOption(opt) {
  emit('update:modelValue', opt.value)
  emit('change', opt.value, opt.raw)
  closeDropdown()
}

function clearSelection(e) {
  e.stopPropagation()
  emit('update:modelValue', '')
  emit('change', '', null)
}

function handleKeyDown(e) {
  if (!isOpen.value) {
    if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
      e.preventDefault()
      openDropdown()
    }
    return
  }

  if (e.key === 'Escape') {
    e.preventDefault()
    closeDropdown()
  } else if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (highlightedIndex.value < filteredOptions.value.length - 1) {
      highlightedIndex.value++
    }
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (highlightedIndex.value > 0) {
      highlightedIndex.value--
    }
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
      selectOption(filteredOptions.value[highlightedIndex.value])
    }
  }
}

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div ref="dropdownRef" class="relative select-none text-xs" :class="customClass">
    <!-- Trigger Button -->
    <div
      tabindex="0"
      class="w-full py-2 px-3 rounded-xl border bg-white flex items-center justify-between gap-2 cursor-pointer transition focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
      :class="[
        disabled ? 'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200' : 'border-slate-200 hover:border-slate-300 text-slate-800',
        isOpen ? 'ring-2 ring-blue-500/40 border-blue-500 shadow-xs' : ''
      ]"
      @click="toggleDropdown"
      @keydown="handleKeyDown"
    >
      <div class="truncate grow flex items-center gap-1.5">
        <span v-if="selectedOption" class="font-bold text-slate-900 truncate">
          {{ selectedOption.label }}
        </span>
        <span v-if="selectedOption && selectedOption.subLabel" class="text-[10px] text-slate-400 font-normal truncate">
          ({{ selectedOption.subLabel }})
        </span>
        <span v-if="!selectedOption" class="text-slate-400 font-normal truncate">
          {{ placeholder }}
        </span>
      </div>

      <div class="flex items-center gap-1 shrink-0 text-slate-400">
        <button
          v-if="allowClear && selectedOption && !disabled"
          type="button"
          class="hover:text-rose-500 transition p-0.5 rounded"
          @click="clearSelection"
          title="Hapus Pilihan"
        >
          <i class="bi bi-x-circle-fill text-xs"></i>
        </button>
        <i class="bi text-xs transition transform duration-150" :class="isOpen ? 'bi-chevron-up text-blue-600' : 'bi-chevron-down'"></i>
      </div>
    </div>

    <!-- Dropdown Menu Panel -->
    <div
      v-if="isOpen"
      class="absolute left-0 right-0 top-full mt-1.5 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 overflow-hidden animate-dropdown"
      style="min-width: 220px;"
    >
      <!-- Search Input Box -->
      <div class="p-2 border-b border-slate-100 bg-slate-50/70">
        <div class="relative">
          <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
          <input
            ref="searchInputRef"
            type="text"
            v-model="searchQuery"
            :placeholder="searchPlaceholder"
            class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-hidden transition"
            @keydown="handleKeyDown"
          />
        </div>
      </div>

      <!-- Options List Scroll Area -->
      <div class="max-h-52 overflow-y-auto p-1 space-y-0.5 custom-scrollbar">
        <div
          v-if="filteredOptions.length === 0"
          class="py-4 text-center text-slate-400 text-xs font-medium"
        >
          <i class="bi bi-emoji-neutral text-sm mb-1 d-block"></i>
          Tidak ada opsi yang cocok
        </div>

        <div
          v-for="(opt, idx) in filteredOptions"
          :key="opt.value"
          class="px-3 py-2 rounded-xl cursor-pointer transition flex items-center justify-between gap-2 text-xs"
          :class="[
            String(opt.value) === String(modelValue) ? 'bg-blue-600 text-white font-bold shadow-2xs' : 'text-slate-700 hover:bg-slate-100',
            highlightedIndex === idx && String(opt.value) !== String(modelValue) ? 'bg-slate-100 text-blue-600 font-semibold' : ''
          ]"
          @click="selectOption(opt)"
          @mouseenter="highlightedIndex = idx"
        >
          <div class="truncate grow">
            <div class="truncate">{{ opt.label }}</div>
            <div v-if="opt.subLabel" class="text-[10px] truncate" :class="String(opt.value) === String(modelValue) ? 'text-blue-100' : 'text-slate-400'">
              {{ opt.subLabel }}
            </div>
          </div>
          <i v-if="String(opt.value) === String(modelValue)" class="bi bi-check-lg text-sm shrink-0"></i>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes dropdownFade {
  from {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
.animate-dropdown {
  animation: dropdownFade 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 9999px;
}
</style>
