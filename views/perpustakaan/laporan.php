<?php
/**
 * View: Laporan Perpustakaan & Akreditasi Sekolah
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">📊 Laporan Perpustakaan & Standar Akreditasi</h2>
        <p class="text-muted fs-7 mb-0">Cetak Laporan Rekapitulasi Koleksi DDC, Statistik Kunjungan, & Rekap Peminjaman Persiswa / Perkelas.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold text-primary mb-2"><i class="bi bi-journal-bookmark me-2"></i> Laporan Rekap Klasifikasi DDC</h5>
            <p class="text-muted fs-7">Rekapitulasi jumlah judul & eksemplar buku berdasarkan 10 kelas utama Klasifikasi Persepuluhan Dewey (DDC).</p>
            <a href="/SINTA-SaaS/perpustakaan/cetak-laporan-ddc" target="_blank" class="btn btn-outline-primary rounded-3 mt-auto">
                <i class="bi bi-printer me-1"></i> Cetak Laporan DDC
            </a>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold text-success mb-2"><i class="bi bi-person-lines-fill me-2"></i> Laporan Peminjaman Per Siswa / Kelas</h5>
            <p class="text-muted fs-7">Daftar rinci riwayat buku yang pernah dipinjam oleh setiap siswa untuk keperluan kelulusan/kenaikan kelas.</p>
            <a href="/SINTA-SaaS/perpustakaan/cetak-laporan-peminjaman" target="_blank" class="btn btn-outline-success rounded-3 mt-auto">
                <i class="bi bi-printer me-1"></i> Cetak Rekap Per Siswa
            </a>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold text-warning mb-2"><i class="bi bi-graph-up-arrow me-2"></i> Laporan Kunjungan & Duta Baca</h5>
            <p class="text-muted fs-7">Grafik & rekapitulasi statistik pengunjung buku tamu harian serta perangkingan siswa paling rajin membaca.</p>
            <a href="/SINTA-SaaS/perpustakaan/cetak-laporan-kunjungan" target="_blank" class="btn btn-outline-warning text-dark rounded-3 mt-auto fw-semibold">
                <i class="bi bi-printer me-1"></i> Cetak Statistik Kunjungan
            </a>
        </div>
    </div>
</div>
