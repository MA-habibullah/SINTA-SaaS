<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    title: String,
    siswaList: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const handleSearch = () => {
    router.get('/buku-induk', { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <AppLayout>
        <Head :title="title" />

        <div class="space-y-4">
            <!-- Header Title Box -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <i class="bi bi-person-vcard-fill text-blue-600"></i>
                        {{ title }}
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">Pangkalan data induk peserta didik digital terintegrasi DAPODIK.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input 
                        v-model="search" 
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Cari nama, NISN, NIK..." 
                        class="form-control form-control-sm text-xs rounded-xl border-slate-200 w-48 sm:w-64"
                    />
                    <button class="btn btn-sm btn-primary rounded-xl px-3 py-2 text-xs font-bold shadow-xs">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Siswa
                    </button>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4" style="width: 50px;">No</th>
                                <th class="py-3 px-4">Nama Lengkap & NISN</th>
                                <th class="py-3 px-4">NIK</th>
                                <th class="py-3 px-4">L/P</th>
                                <th class="py-3 px-4">Tempat, Tgl Lahir</th>
                                <th class="py-3 px-4">Kelas / Rombel</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="(siswa, index) in siswaList.data" :key="siswa.id" class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-semibold text-slate-400">
                                    {{ (siswaList.current_page - 1) * siswaList.per_page + index + 1 }}
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ siswa.nama_lengkap }}
                                    <div class="text-[11px] font-mono text-slate-400 font-normal">NISN: {{ siswa.nisn || '-' }}</div>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600">{{ siswa.nik || '-' }}</td>
                                <td class="py-3 px-4">
                                    <span class="badge" :class="siswa.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'">
                                        {{ siswa.jenis_kelamin }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">{{ siswa.tempat_lahir }}, {{ siswa.tanggal_lahir }}</td>
                                <td class="py-3 px-4 font-semibold text-blue-700">{{ siswa.kelas?.nama_kelas || 'Belum Ada Kelas' }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span v-if="siswa.status_aktif" class="badge bg-emerald-100 text-emerald-800">Aktif</span>
                                    <span v-else class="badge bg-slate-100 text-slate-600">Non-Aktif</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Link :href="`/buku-induk/${siswa.id}`" class="btn btn-sm btn-light border border-slate-200 text-slate-600 hover:text-blue-600 rounded-lg p-1.5" title="Detail Siswa">
                                        <i class="bi bi-eye-fill"></i>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="siswaList.data.length === 0">
                                <td colspan="8" class="text-center py-8 text-slate-400">
                                    <i class="bi bi-inbox text-3xl mb-2 block"></i>
                                    Belum ada data siswa yang tercatat.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="siswaList.links && siswaList.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <div>Menampilkan {{ siswaList.from }} - {{ siswaList.to }} dari {{ siswaList.total }} siswa</div>
                    <div class="flex gap-1">
                        <Link 
                            v-for="link in siswaList.links" 
                            :key="link.label" 
                            :href="link.url || '#'" 
                            v-html="link.label"
                            class="px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition"
                            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-200 text-slate-700 hover:bg-slate-100'"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
