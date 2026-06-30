<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Manajemen Pengumuman</h1>
            <p class="text-muted mb-0">Kelola pengumuman untuk seluruh warga sekolah.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPengumumanModal">
            <i class="bi bi-plus-lg me-1"></i> Buat Pengumuman Baru
        </button>
    </div>

    <!-- Error/Success Flash -->
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>TANGGAL</th>
                            <th>JUDUL</th>
                            <th>SEKOLAH</th>
                            <th>LAMPIRAN</th>
                            <th>VISIBILITAS</th>
                            <th>STATUS</th>
                            <th class="text-end">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pengumuman)): ?>
                            <tr><td colspan="7" class="text-center py-4">Belum ada data pengumuman.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pengumuman as $row): ?>
                                <tr>
                                    <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['judul']) ?></div>
                                        <small class="text-muted">Oleh: <?= htmlspecialchars($row['nama_pembuat']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (empty($row['tenant_id'])): ?>
                                            <span class="badge bg-primary">Global</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($row['nama_sekolah'] ?? 'Unknown') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['lampiran_file']): ?>
                                            <a href="/SINTA-SaaS/storage/app/public/<?= htmlspecialchars($row['lampiran_file']) ?>" target="_blank" class="badge bg-primary text-decoration-none">
                                                <i class="bi bi-file-earmark-pdf"></i> Lihat File
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $row['visibilitas'] == 'public' ? 'success' : ($row['visibilitas'] == 'private' ? 'danger' : 'info') ?>">
                                            <?= strtoupper($row['visibilitas']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $row['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $row['is_active'] ? 'Aktif' : 'Non-Aktif' ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editPengumuman(<?= htmlspecialchars(json_encode($row)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="/SINTA-SaaS/informasi/pengumuman/delete" method="POST" class="d-inline" onsubmit="return confirm('Hapus pengumuman ini?');">
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

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="addPengumumanModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form action="/SINTA-SaaS/informasi/pengumuman/store" method="POST" enctype="multipart/form-data" id="formPengumuman">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Buat Pengumuman Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="p_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Judul Pengumuman</label>
                                <input type="text" class="form-control" name="judul" id="p_judul" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Isi Pengumuman</label>
                                <textarea name="isi_pengumuman" id="p_isi" class="form-control" rows="15"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <?php if (isset($isSuperAdmin) && $isSuperAdmin): ?>
                            <div class="card bg-light border-0 mb-3">
                                <div class="card-body">
                                    <div class="mb-3 mb-0">
                                        <label class="form-label fw-bold">Target Sekolah (Khusus Admin)</label>
                                        <select class="form-select" name="tenant_id" id="p_tenant_id">
                                            <option value="global">Pengumuman Global</option>
                                            <?php foreach ($tenants as $t): ?>
                                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card bg-light border-0 mb-3">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Visibilitas</label>
                                        <select class="form-select" name="visibilitas" id="p_visibilitas" onchange="toggleRoles()">
                                            <option value="public">Publik (Semua)</option>
                                            <option value="guru">Hanya Guru/Staf</option>
                                            <option value="siswa">Hanya Siswa</option>
                                            <option value="private">Private (Pilih Role)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="roleSelection" style="display:none;">
                                        <label class="form-label fw-bold">Pilih Role Khusus</label>
                                        <div class="border p-2 bg-white" style="max-height:150px; overflow-y:auto; border-radius:6px;">
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
                            
                            <div class="card bg-light border-0 mb-3">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Upload Lampiran (PDF/IMG)</label>
                                        <input type="file" class="form-control" name="lampiran" accept=".pdf,.jpg,.png">
                                        <div id="lampiranInfo" class="mt-2" style="display:none;">
                                            <small class="text-success"><i class="bi bi-check-circle"></i> File sudah terlampir.</small>
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" name="hapus_lampiran" value="1" id="delLampiran">
                                                <label class="form-check-label text-danger" for="delLampiran">Hapus file lampiran lama</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="p_active" checked>
                                <label class="form-check-label fw-bold" for="p_active">Publikasikan Sekarang</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pengumuman</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.ck-editor__editable_inline {
    min-height: 350px;
}
</style>
<script>
window.editorInstance = null;

window.initEditor = function() {
    if (typeof ClassicEditor !== 'undefined' && !window.editorInstance) {
        const el = document.querySelector('#p_isi');
        if (el && !el.classList.contains('ck-hidden')) {
            ClassicEditor
                .create(el, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
                    table: {
                        contentToolbar: [ 'tableColumn', 'tableRow', 'mergeTableCells' ]
                    }
                })
                .then(editor => {
                    window.editorInstance = editor;
                    editor.model.document.on('change:data', () => {
                        el.value = editor.getData();
                    });
                })
                .catch(error => {
                    console.error(error);
                });
        }
    }
}

window.loadEditorScript = function() {
    if (typeof ClassicEditor === 'undefined') {
        if (!document.querySelector('script[src*="ckeditor.js"]')) {
            const script = document.createElement('script');
            script.src = '/SINTA-SaaS/assets/js/ckeditor.js';
            script.onload = window.initEditor;
            document.head.appendChild(script);
        }
    } else {
        window.initEditor();
    }
};

document.addEventListener('turbo:load', function() {
    if (window.editorInstance) {
        window.editorInstance.destroy().catch(e => console.log(e));
        window.editorInstance = null;
    }
    window.loadEditorScript();
});
// Hapus inisialisasi ganda untuk mencegah 3 editor sekaligus


window.toggleRoles = function() {
    const val = document.getElementById('p_visibilitas').value;
    document.getElementById('roleSelection').style.display = (val === 'private') ? 'block' : 'none';
};

window.editPengumuman = function(data) {
    document.getElementById('modalTitle').innerText = 'Edit Pengumuman';
    document.getElementById('formPengumuman').action = '/SINTA-SaaS/informasi/pengumuman/update';
    
    document.getElementById('p_id').value = data.id;
    document.getElementById('p_judul').value = data.judul;
    
    if (window.editorInstance) {
        window.editorInstance.setData(data.isi_pengumuman);
    } else {
        document.getElementById('p_isi').value = data.isi_pengumuman;
    }
    
    document.getElementById('p_visibilitas').value = data.visibilitas;
    document.getElementById('p_active').checked = data.is_active == 1;
    
    let tid = document.getElementById('p_tenant_id');
    if (tid) {
        tid.value = data.tenant_id ? data.tenant_id : 'global';
    }
    
    // Reset roles
    document.querySelectorAll('.role-check').forEach(cb => cb.checked = false);
    if(data.visibilitas === 'private' && data.target_roles) {
        let roles = JSON.parse(data.target_roles);
        roles.forEach(r => {
            let el = document.getElementById('role_' + r);
            if(el) el.checked = true;
        });
    }
    toggleRoles();
    
    // File info
    const lInfo = document.getElementById('lampiranInfo');
    if(data.lampiran_file) {
        lInfo.style.display = 'block';
        document.getElementById('delLampiran').checked = false;
    } else {
        lInfo.style.display = 'none';
    }
    
    var modal = new bootstrap.Modal(document.getElementById('addPengumumanModal'));
    modal.show();
}

// Clear modal on hidden
document.getElementById('addPengumumanModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerText = 'Buat Pengumuman Baru';
    document.getElementById('formPengumuman').action = '/SINTA-SaaS/informasi/pengumuman/store';
    document.getElementById('p_id').value = '';
    document.getElementById('p_judul').value = '';
    
    if (window.editorInstance) {
        window.editorInstance.setData('');
    } else {
        document.getElementById('p_isi').value = '';
    }
    
    let tid = document.getElementById('p_tenant_id');
    if (tid) tid.value = 'global';
    
    document.getElementById('p_visibilitas').value = 'public';
    document.getElementById('p_active').checked = true;
    document.querySelectorAll('.role-check').forEach(cb => cb.checked = false);
    document.getElementById('lampiranInfo').style.display = 'none';
    toggleRoles();
});
</script>
