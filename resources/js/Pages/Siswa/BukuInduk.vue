<script setup>
import Index from './BukuInduk/Index.vue'

defineProps({
    activeTab: String,
    siswaList: Object,
    tenants: Array,
    kelasList: Array,
    jenjangList: Array,
    tahunAjaranList: Array,
    kurikulumList: Array,
    bankMapel: Array,
    isSuperAdmin: Boolean,
    userRole: String,
    filters: Object,
})
</script>

<template>
    <Index v-bind="$props" />
</template>
