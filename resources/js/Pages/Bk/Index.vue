<script setup>
import KedisiplinanIndex from './Kedisiplinan/Index.vue';

defineProps({
    pelanggaranList: Object,
    masterList: Array,
    kpi: Object,
    topStudents: Array,
    monthlyTrend: Array,
    filters: Object,
});
</script>

<template>
    <KedisiplinanIndex
        :pelanggaran-list="pelanggaranList"
        :master-list="masterList"
        :kpi="kpi"
        :top-students="topStudents"
        :monthly-trend="monthlyTrend"
        :filters="filters"
    />
</template>
