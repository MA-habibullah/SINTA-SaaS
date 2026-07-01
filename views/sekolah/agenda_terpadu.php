<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Agenda & Timeline Sekolah</h1>
            <p class="text-muted mb-0">Kelola jadwal kegiatan publik dan timeline kerja internal sekolah.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgendaModal">
            <i class="bi bi-calendar-plus me-1"></i> Tambah Kegiatan
        </button>
    </div>

    <!-- Error/Success Flash -->
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4" id="agendaTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-pane" type="button" role="tab">Daftar Kegiatan</button>
                </li>
            </ul>
            
            <div class="tab-content" id="agendaTabContent">
                <div class="tab-pane fade show active" id="list-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>TANGGAL & WAKTU</th>
                                    <th>KEGIATAN</th>
                                    <th>SEKOLAH</th>
                                    <th>TIPE</th>
                                    <th>STATUS</th>
                                    <th>VISIBILITAS</th>
                                    <th class="text-end">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($agenda)): ?>
                                    <tr><td colspan="7" class="text-center py-4">Belum ada agenda kegiatan.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($agenda as $row): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= date('d M Y', strtotime($row['tanggal_mulai'])) ?></div>
                                                <small class="text-muted">
                                                    <?= $row['tanggal_selesai'] !== $row['tanggal_mulai'] ? ' s.d ' . date('d M Y', strtotime($row['tanggal_selesai'])) : '' ?>
                                                    <?= $row['waktu'] ? ' • ' . substr($row['waktu'], 0, 5) : '' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($row['judul']) ?></div>
                                                <?php if ($row['lampiran_file']): ?>
                                                    <a href="/SINTA-SaaS/storage/app/public/<?= htmlspecialchars($row['lampiran_file']) ?>" target="_blank" class="badge bg-secondary text-decoration-none mt-1">
                                                        <i class="bi bi-paperclip"></i> Lampiran
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (empty($row['tenant_id'])): ?>
                                                    <span class="badge bg-primary">Global</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($row['nama_sekolah'] ?? 'Unknown') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $row['tipe'] == 'Agenda Umum' ? 'primary' : 'dark' ?>">
                                                    <?= htmlspecialchars($row['tipe']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $bg = 'secondary';
                                                    if ($row['status_kegiatan'] == 'Sedang Berjalan') $bg = 'warning';
                                                    if ($row['status_kegiatan'] == 'Selesai') $bg = 'success';
                                                    if ($row['status_kegiatan'] == 'Batal') $bg = 'danger';
                                                ?>
                                                <span class="badge bg-<?= $bg ?>"><?= htmlspecialchars($row['status_kegiatan']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge border border-<?= $row['visibilitas'] == 'public' ? 'success' : 'danger' ?> text-<?= $row['visibilitas'] == 'public' ? 'success' : 'danger' ?>">
                                                    <?= strtoupper($row['visibilitas']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary" onclick="editAgenda(<?= htmlspecialchars(json_encode($row)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="/SINTA-SaaS/informasi/agenda/delete" method="POST" class="d-inline" onsubmit="return confirm('Hapus kegiatan ini?');">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="addAgendaModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form action="/SINTA-SaaS/informasi/agenda/store" method="POST" enctype="multipart/form-data" id="formAgenda">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Kegiatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="a_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Judul Kegiatan</label>
                            <input type="text" class="form-control" name="judul" id="a_judul" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipe Kegiatan</label>
                            <select class="form-select" name="tipe" id="a_tipe">
                                <option value="Agenda Umum">Agenda Umum (Kalender)</option>
                                <option value="Timeline Internal">Timeline Kerja (Internal)</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (isset($isSuperAdmin) && $isSuperAdmin): ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Target Sekolah (Khusus Admin)</label>
                            <select class="form-select" name="tenant_id" id="a_tenant_id">
                                <option value="global">Agenda Global</option>
                                <?php foreach ($tenants as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tanggal Mulai</label>
                            <input type="date" class="form-control" name="tanggal_mulai" id="a_tgl_mulai" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tanggal Selesai</label>
                            <input type="date" class="form-control" name="tanggal_selesai" id="a_tgl_selesai" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Waktu (Opsional)</label>
                            <input type="time" class="form-control" name="waktu" id="a_waktu">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi Tambahan</label>
                        <textarea class="form-control" name="deskripsi" id="a_deskripsi" rows="3"></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status Pelaksanaan</label>
                            <select class="form-select" name="status_kegiatan" id="a_status">
                                <option value="Rencana">Rencana / Terjadwal</option>
                                <option value="Sedang Berjalan">Sedang Berjalan</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Batal">Batal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Upload Lampiran PDF/SK (Opsional)</label>
                            <input type="file" class="form-control" name="lampiran" accept=".pdf,.jpg,.png">
                            <div id="lampiranInfo" class="mt-1" style="display:none;">
                                <small class="text-success"><i class="bi bi-check-circle"></i> File terlampir</small>
                                <label class="form-check-label text-danger ms-2" style="font-size:12px;">
                                    <input type="checkbox" name="hapus_lampiran" value="1" id="delLampiran"> Hapus File Lama
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Visibilitas Akses</label>
                                    <select class="form-select" name="visibilitas" id="a_visibilitas" onchange="toggleRoles()">
                                        <option value="public">Publik (Tampil di Dashboard Semua)</option>
                                        <option value="guru">Hanya Guru/Staf</option>
                                        <option value="private">Private (Pilih Role Khusus)</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="roleSelection" style="display:none;">
                                    <label class="form-label fw-bold">Pilih Role Khusus</label>
                                    <div class="border p-2 bg-white" style="max-height:120px; overflow-y:auto; border-radius:6px;">
                                        <?php foreach ($roleList as $r): ?>
                                            <div class="form-check">
                                                <input class="form-check-input role-check" type="checkbox" name="target_roles[]" value="<?= $r['id'] ?>" id="role_<?= $r['id'] ?>">
                                                <label class="form-check-label" for="role_<?= $r['id'] ?>">
                                                    <?= htmlspecialchars($r['nama_role']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Agenda</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
window.toggleRoles = function() {
    const val = document.getElementById('a_visibilitas').value;
    document.getElementById('roleSelection').style.display = (val === 'private') ? 'block' : 'none';
};

window.editAgenda = function(data) {
    document.getElementById('modalTitle').innerText = 'Edit Kegiatan';
    document.getElementById('formAgenda').action = '/SINTA-SaaS/informasi/agenda/update';
    
    document.getElementById('a_id').value = data.id;
    document.getElementById('a_judul').value = data.judul;
    document.getElementById('a_tipe').value = data.tipe;
    document.getElementById('a_tgl_mulai').value = data.tanggal_mulai;
    document.getElementById('a_tgl_selesai').value = data.tanggal_selesai;
    document.getElementById('a_waktu').value = data.waktu ? data.waktu.substring(0,5) : '';
    document.getElementById('a_deskripsi').value = data.deskripsi;
    document.getElementById('a_status').value = data.status_kegiatan;
    document.getElementById('a_visibilitas').value = data.visibilitas;
    
    let tid = document.getElementById('a_tenant_id');
    if (tid) {
        tid.value = data.tenant_id ? data.tenant_id : 'global';
    }
    
    document.querySelectorAll('.role-check').forEach(cb => cb.checked = false);
    if(data.visibilitas === 'private' && data.target_roles) {
        let roles = JSON.parse(data.target_roles);
        roles.forEach(r => {
            let el = document.getElementById('role_' + r);
            if(el) el.checked = true;
        });
    }
    toggleRoles();
    
    const lInfo = document.getElementById('lampiranInfo');
    if(data.lampiran_file) {
        lInfo.style.display = 'block';
        document.getElementById('delLampiran').checked = false;
    } else {
        lInfo.style.display = 'none';
    }
    
    var modal = new bootstrap.Modal(document.getElementById('addAgendaModal'));
    modal.show();
}

document.getElementById('addAgendaModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerText = 'Tambah Kegiatan';
    document.getElementById('formAgenda').action = '/SINTA-SaaS/informasi/agenda/store';
    document.getElementById('a_id').value = '';
    document.getElementById('formAgenda').reset();
    document.getElementById('a_tgl_selesai').value = '';
    document.getElementById('a_waktu').value = '';
    document.getElementById('a_tipe').value = 'Agenda Umum';
    document.getElementById('a_status').value = 'Rencana';
    document.getElementById('a_visibilitas').value = 'public';
    
    let tid = document.getElementById('a_tenant_id');
    if (tid) tid.value = 'global';
    
    document.getElementById('lampiranInfo').style.display = 'none';
    toggleRoles();
});
</script>
