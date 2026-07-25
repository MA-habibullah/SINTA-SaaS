<?php
/**
 * View: Dashboard Perpustakaan
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">📚 Dashboard Perpustakaan Digital</h2>
        <p class="text-muted fs-7 mb-0">Sistem Manajemen Perpustakaan Terintegrasi (ILS) Akreditasi Sekolah.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan/opac" target="_blank" class="btn btn-outline-primary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-globe me-1"></i> OPAC Publik
        </a>
        <a href="/SINTA-SaaS/perpustakaan/sirkulasi" class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-repeat me-1"></i> Transaksi Sirkulasi
        </a>
    </div>
</div>

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<!-- Summary Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-primary bg-gradient text-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fs-7 fw-semibold text-white-50">TOTAL BIBLIOGRAFI</span>
                <i class="bi bi-book fs-3 text-white-50"></i>
            </div>
            <h3 class="fw-bold mb-0"><?= number_format($data['summary']['total_koleksi'] ?? 0) ?></h3>
            <small class="text-white-50 fs-8">Buku Fisik & E-Book Terdaftar</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-success bg-gradient text-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fs-7 fw-semibold text-white-50">PINJAMAN AKTIF</span>
                <i class="bi bi-journal-check fs-3 text-white-50"></i>
            </div>
            <h3 class="fw-bold mb-0"><?= number_format($data['summary']['pinjaman_aktif'] ?? 0) ?></h3>
            <small class="text-white-50 fs-8">Sedang Dibawa Siswa/Guru</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-warning bg-gradient text-dark h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fs-7 fw-semibold text-dark-50">JATUH TEMPO</span>
                <i class="bi bi-exclamation-triangle fs-3 text-dark-50"></i>
            </div>
            <h3 class="fw-bold mb-0"><?= number_format($data['summary']['jatuh_tempo'] ?? 0) ?></h3>
            <small class="text-dark-50 fs-8">Memerlukan Notifikasi WA</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-info bg-gradient text-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fs-7 fw-semibold text-white-50">TOTAL ANGGOTA</span>
                <i class="bi bi-people fs-3 text-white-50"></i>
            </div>
            <h3 class="fw-bold mb-0"><?= number_format($data['summary']['total_anggota'] ?? 0) ?></h3>
            <small class="text-white-50 fs-8">Siswa & Guru Terverifikasi</small>
        </div>
    </div>
</div>

<!-- Accreditation Indicator (SNP Perpusnas) -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <div class="row align-items-center">
        <div class="col-12 col-lg-7">
            <h5 class="fw-bold text-dark mb-1">
                <i class="bi bi-patch-check-fill text-primary me-2"></i> Indikator Akreditasi SNP (Perpusnas RI)
            </h5>
            <p class="text-muted fs-7 mb-3">
                Standar Nasional Perpustakaan RI mensyaratkan minimal **60% koleksi non-fiksi** untuk akreditasi optimal.
            </p>
            <div class="mb-2 d-flex justify-content-between fs-7 fw-semibold">
                <span>Rasio Koleksi Non-Fiksi</span>
                <span class="text-primary"><?= $data['ratio']['persen_non_fiksi'] ?? 0 ?>%</span>
            </div>
            <div class="progress rounded-pill mb-3" style="height: 12px;">
                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $data['ratio']['persen_non_fiksi'] ?? 0 ?>%" aria-valuenow="<?= $data['ratio']['persen_non_fiksi'] ?? 0 ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex align-items-center gap-4 text-muted fs-8">
                <div><span class="badge bg-light text-dark border me-1"><?= number_format($data['ratio']['total_non_fiksi'] ?? 0) ?></span> Non-Fiksi / Pengayaan</div>
                <div><span class="badge bg-light text-dark border me-1"><?= number_format($data['ratio']['total_fiksi'] ?? 0) ?></span> Fiksi / Sastra</div>
            </div>
        </div>
        <div class="col-12 col-lg-5 text-center mt-3 mt-lg-0 border-start-lg">
            <div class="p-3 rounded-4 bg-light d-inline-block w-100 max-w-sm">
                <?php if (!empty($data['ratio']['is_layak_akreditasi'])): ?>
                    <div class="text-success fs-1 mb-1"><i class="bi bi-check-circle-fill"></i></div>
                    <h6 class="fw-bold text-success mb-1">Rasio Memenuhi Syarat (A)</h6>
                    <small class="text-muted fs-8">Persentase Non-Fiksi telah mencapai <?= $data['ratio']['persen_non_fiksi'] ?? 0 ?>% (Min 60%)</small>
                <?php else: ?>
                    <div class="text-warning fs-1 mb-1"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <h6 class="fw-bold text-warning mb-1">Peringatan Akreditasi</h6>
                    <small class="text-muted fs-8">Koleksi Non-Fiksi baru <?= $data['ratio']['persen_non_fiksi'] ?? 0 ?>% (Target Min 60%). Segera tambah buku pengayaan!</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Main Submenu Access Grid -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid-fill text-primary me-2"></i> Modul Operasional Perpustakaan</h5>
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <a href="/SINTA-SaaS/perpustakaan/katalog" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark h-100 hover-elevation">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary-subtle text-primary rounded-3">
                        <i class="bi bi-book fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Katalog & Koleksi</h6>
                        <small class="text-muted">Kelola judul buku, rak, eksemplar & E-Book reader.</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="/SINTA-SaaS/perpustakaan/sirkulasi" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark h-100 hover-elevation">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-success-subtle text-success rounded-3">
                        <i class="bi bi-arrow-repeat fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Sirkulasi Reguler</h6>
                        <small class="text-muted">Transaksi pinjam & kembali buku harian via Barcode.</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="/SINTA-SaaS/perpustakaan/sirkulasi" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark h-100 hover-elevation">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-info-subtle text-info rounded-3">
                        <i class="bi bi-box-seam fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Buku Paket Pelajaran</h6>
                        <small class="text-muted">Peminjaman massal 1 set buku pelajaran 1 semester.</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="/SINTA-SaaS/perpustakaan/sirkulasi" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark h-100 hover-elevation">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-warning-subtle text-warning rounded-3">
                        <i class="bi bi-trophy fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Event Khusus (OSN)</h6>
                        <small class="text-muted">Peminjaman buku pengayaan kontingen olimpiade.</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="/SINTA-SaaS/perpustakaan/anggota" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark h-100 hover-elevation">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-danger-subtle text-danger rounded-3">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Keanggotaan & Bebas Pustaka</h6>
                        <small class="text-muted">Verifikasi anggota & cetak Surat Bebas Pustaka.</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="/SINTA-SaaS/perpustakaan/anggota" class="card border-0 shadow-sm rounded-3 p-3 text-decoration-none text-dark h-100 hover-elevation">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-secondary-subtle text-secondary rounded-3">
                        <i class="bi bi-gear fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Pengaturan & WA Toggle</h6>
                        <small class="text-muted">Atur denda & sakelar ON/OFF Notifikasi WhatsApp.</small>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
