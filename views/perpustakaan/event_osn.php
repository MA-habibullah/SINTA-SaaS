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

<?php include __DIR__ . '/_tenant_filter.php'; ?>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Sekolah / Tenant</th>
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
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-trophy fs-3 d-block mb-2 text-warning"></i> Belum ada event khusus/OSN terdaftar. Klik <strong>Tambah Event OSN / Lomba</strong> untuk mendaftarkan kontingen.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['event_list'] as $idx => $ev): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($ev['tenant_name'] ?? 'Sekolah Aktif') ?>
                                </span>
                            </td>
                            <td><strong><?= htmlspecialchars($ev['nama_event'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><span class="badge bg-warning-subtle text-dark"><?= htmlspecialchars($ev['bidang'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars($ev['nama_siswa'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($ev['judul_buku'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($ev['tanggal_kembali_rencana'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge bg-success">Aktif / Berjalan</span></td>
                            <td class="text-center">
                                <a href="/SINTA-SaaS/perpustakaan/cetak-laporan-peminjaman" class="btn btn-outline-primary btn-sm rounded-2">
                                    <i class="bi bi-file-earmark-text me-1"></i> Cetak Berita Acara
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Event OSN -->
<div class="modal fade" id="modalEventOSN" tabindex="-1" aria-labelledby="modalEventOSNLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalEventOSNLabel"><i class="bi bi-trophy me-2"></i> Pendaftaran Event OSN / Kontingen Lomba</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/perpustakaan/event" method="POST" data-turbo="false">
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <?php if ($data['is_super_admin'] ?? false): ?>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Target Sekolah / Tenant <span class="text-danger">*</span></label>
                                <select name="tenant_id" class="form-select rounded-3 bg-light border-warning" required>
                                    <?php foreach ($data['tenants'] as $t): ?>
                                        <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === ($data['active_tenant_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['nama_sekolah']) ?> (<?= htmlspecialchars($t['npsn']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nama Event / Olimpiade <span class="text-danger">*</span></label>
                            <input type="text" name="nama_event" class="form-control rounded-3" placeholder="Contoh: OSN Fisika Tingkat Provinsi" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Bidang Studi / Subjek</label>
                            <input type="text" name="bidang" class="form-control rounded-3" placeholder="Contoh: Fisika / Matematika / Biologi">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-3 px-4 fw-semibold"><i class="bi bi-save me-1"></i> Simpan Event</button>
                </div>
            </form>
        </div>
    </div>
</div>
