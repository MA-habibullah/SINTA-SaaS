<?php
/**
 * View: Peminjaman Buku Paket Pelajaran per Kelas / Semester
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">📦 Peminjaman Buku Paket Pelajaran</h2>
        <p class="text-muted fs-7 mb-0">Distribusi Massal 1 Set Buku Paket Pelajaran untuk Dibawa Pulang 1 Semester / 1 Tahun Ajaran.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-7" data-bs-toggle="modal" data-bs-target="#modalDistribusiPaket">
            <i class="bi bi-box-seam me-1"></i> Distribusi Paket Baru
        </button>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-info border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-3 shadow-sm">
    <i class="bi bi-info-circle-fill text-info fs-3"></i>
    <div class="fs-7">
        <strong>Fitur Buku Paket Sekolah:</strong> Sistem mencatat peminjaman paket buku teks pelajaran per kelas/siswa secara otomatis untuk durasi 1 semester/tahun ajaran. Laporan peminjaman paket per siswa dapat dicetak saat kenaikan kelas atau kelulusan.
    </div>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <form class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Pilih Kelas</label>
            <select class="form-select rounded-3">
                <option value="">— Semua Kelas —</option>
                <option value="X-IPA-1">X IPA 1</option>
                <option value="X-IPA-2">X IPA 2</option>
                <option value="XI-IPA-1">XI IPA 1</option>
                <option value="XII-IPA-1">XII IPA 1</option>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Tahun Ajaran / Semester</label>
            <select class="form-select rounded-3">
                <option value="2026/2027 Ganjil">2026/2027 Ganjil</option>
                <option value="2026/2027 Genap">2026/2027 Genap</option>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <button type="submit" class="btn btn-secondary rounded-3 w-100 py-2">
                <i class="bi bi-search me-1"></i> Tampilkan Data Paket
            </button>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama Paket</th>
                    <th>Kelas / Tingkat</th>
                    <th>Tahun Ajaran</th>
                    <th>Total Judul</th>
                    <th>Siswa Penerima</th>
                    <th>Status Pengembalian</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['paket_list'])): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-box-seam fs-3 d-block mb-2"></i> Belum ada rekaman distribusi buku paket pelajaran. Klik <strong>Distribusi Paket Baru</strong> untuk memulai.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['paket_list'] as $idx => $p): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($p['nama_paket'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($p['kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars($p['tahun_ajaran'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)($p['total_buku'] ?? 0) ?> Judul</td>
                            <td><?= (int)($p['total_siswa'] ?? 0) ?> Siswa</td>
                            <td><span class="badge bg-success">Berjalan (Semester 1)</span></td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm rounded-2 me-1" title="Cetak Laporan Peminjaman Per Siswa">
                                    <i class="bi bi-printer me-1"></i> Cetak Laporan
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
