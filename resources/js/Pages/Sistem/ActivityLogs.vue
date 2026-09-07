<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    logs: Object
})
</script>

<template>
    <AppLayout title="Sistem & Audit Trail">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Audit Trail & Log Aktivitas</h1>
                    <p class="text-sm text-slate-500">Pemantauan riwayat aktivitas seluruh pengguna platform</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Aktivitas</th>
                            <th class="px-4 py-3">User ID</th>
                            <th class="px-4 py-3">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="log in logs?.data || []" :key="log.id" class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 text-xs text-slate-500">{{ log.created_at }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ log.action || log.deskripsi || '-' }}</td>
                            <td class="px-4 py-3 text-xs font-mono">{{ log.user_id || '-' }}</td>
                            <td class="px-4 py-3 text-xs font-mono">{{ log.ip_address || '-' }}</td>
                        </tr>
                        <tr v-if="!logs?.data?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">Belum ada riwayat log aktivitas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
