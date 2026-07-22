<?php
/**
 * View: Keanggotaan Perpustakaan & Bebas Pustaka
 */
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1">👥 Keanggotaan & Surat Bebas Pustaka</h2>
        <p class="text-muted fs-7 mb-0">Verifikasi Anggota Perpustakaan, Cetak Kartu Digital, & Penerbitan Surat Bebas Pustaka Kelulusan.</p>
    </div>
    <div class="btn-toolbar gap-2 mb-2 mb-md-0">
        <a href="/SINTA-SaaS/perpustakaan" class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 py-2 fs-7" data-bs-toggle="modal" data-bs-target="#modalSyncDapodik">
            <i class="bi bi-arrow-repeat me-1"></i> Sync Data Anggota (Dapodik/EMIS)
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
                    <th>Nomor Anggota</th>
                    <th>Nama Anggota</th>
                    <th>Kelas Aktif</th>
                    <th>Tipe / Peran</th>
                    <th>Status Pinjaman</th>
                    <th>Tanggungan Denda</th>
                    <th>Status Bebas Pustaka</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['anggota_list'])): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-person-bounding-box fs-3 d-block mb-2"></i> Belum ada data anggota terdaftar. Klik <strong>Sync Data Anggota</strong> untuk mengimpor dari data pokok siswa/guru.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['anggota_list'] as $idx => $a): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($a['tenant_name'] ?? 'Sekolah Aktif') ?>
                                </span>
                            </td>
                            <td><code><?= htmlspecialchars($a['no_anggota'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><strong><?= htmlspecialchars($a['nama_lengkap'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border">
                                    <i class="bi bi-door-open me-1"></i><?= htmlspecialchars($a['nama_kelas'] ?? ($a['kode_kelas'] ?? 'Umum / Staf'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($a['tipe_anggota'] ?? 'Siswa', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= (int)($a['pinjam_aktif'] ?? 0) ?> Buku</td>
                            <td>Rp <?= number_format((float)($a['total_denda'] ?? 0)) ?></td>
                            <td>
                                <?php if (empty($a['pinjam_aktif']) && empty($a['total_denda'])): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> LULUS (Bebas Pustaka)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i> Ada Tanggungan</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="/SINTA-SaaS/perpustakaan/cetak-label-thermal?barcode=<?= urlencode($a['no_anggota']) ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-2 me-1" title="Cetak Kartu Anggota Digital QR">
                                    <i class="bi bi-qr-code me-1"></i> Kartu
                                </a>
                                <a href="/SINTA-SaaS/perpustakaan/cetak-laporan-peminjaman" class="btn btn-outline-success btn-sm rounded-2" title="Cetak Surat Bebas Pustaka">
                                    <i class="bi bi-file-earmark-check me-1"></i> Surat Bebas
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Sync Data Anggota -->
<div class="modal fade" id="modalSyncDapodik" tabindex="-1" aria-labelledby="modalSyncDapodikLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="modalSyncDapodikLabel"><i class="bi bi-arrow-repeat me-2"></i> Sinkronisasi Anggota Dapodik/EMIS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-cloud-arrow-down text-primary display-3 d-block mb-3"></i>
                <h5 class="fw-bold text-dark">Impor Data Siswa & Guru</h5>
                <p class="text-muted fs-7">Sistem akan secara otomatis mendaftarkan seluruh siswa dan guru aktif ke dalam basis data perpustakaan digital.</p>
                
                <?php if ($data['is_super_admin'] ?? false): ?>
                    <div class="text-start mt-3">
                        <label class="form-label fw-semibold">Target Sekolah / Tenant <span class="text-danger">*</span></label>
                        <select id="syncTenantSelect" class="form-select rounded-3 bg-light border-primary">
                            <?php foreach ($data['tenants'] as $t): ?>
                                <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === ($data['active_tenant_id'] ?? '')) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nama_sekolah']) ?> (<?= htmlspecialchars($t['npsn']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary rounded-3 px-4" id="btnDoSync"><i class="bi bi-play-fill me-1"></i> Mulai Sync Sekarang</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function handleSyncClick(e) {
        const btn = e.target.closest('#btnDoSync');
        if (!btn) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
        const select = document.getElementById('syncTenantSelect');
        const tid = select ? select.value : '<?= htmlspecialchars($data['active_tenant_id'] ?? '') ?>';
        
        fetch('/SINTA-SaaS/api/v1/perpustakaan/anggota/sync?tenant_id=' + encodeURIComponent(tid), { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Sinkronisasi berhasil!');
                window.location.reload();
            })
            .catch(err => {
                alert('Gagal melakukan sinkronisasi: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-play-fill me-1"></i> Mulai Sync Sekarang';
            });
    }

    document.removeEventListener('click', handleSyncClick);
    document.addEventListener('click', handleSyncClick);
})();
</script>
