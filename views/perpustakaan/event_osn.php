<?php
/**
 * View: Event Khusus & Peminjaman OSN / Olimpiade
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">🏆 Event Khusus & Peminjaman Buku OSN / Kontingen</h2>
        <p class="text-muted fs-7 mb-0">Fasilitas Peminjaman Buku Referensi Tambahan Khusus Siswa Peserta Olimpiade / Lomba.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button type="button" class="btn btn-warning btn-sm rounded-3 px-3 py-2 fs-7 text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#modalEventOSN">
            <i class="bi bi-trophy me-1"></i> Tambah Event OSN / Lomba
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama Event / Lomba</th>
                    <th>Bidang Studi / Cabang</th>
                    <th>Siswa Peserta (Kontingen)</th>
                    <th>Buku Referensi Diberikan</th>
                    <th>Batas Pengembalian</th>
                    <th>Status Event</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['event_list'])): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-trophy fs-3 d-block mb-2 text-warning"></i> Belum ada event khusus/OSN terdaftar. Klik <strong>Tambah Event OSN / Lomba</strong> untuk mendaftarkan kontingen.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['event_list'] as $idx => $ev): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($ev['nama_event'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><span class="badge bg-warning-subtle text-dark"><?= htmlspecialchars($ev['bidang'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars($ev['nama_siswa'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($ev['judul_buku'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($ev['tanggal_kembali_rencana'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge bg-success">Aktif / Berjalan</span></td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm rounded-2">
                                    <i class="bi bi-file-earmark-text me-1"></i> Cetak Berita Acara
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
