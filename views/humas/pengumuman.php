<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Manajemen Pengumuman</h1>
            <p class="text-muted mb-0">Kelola pengumuman dan kategori untuk seluruh warga sekolah.</p>
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

    <style>
        /* Styling for Nav Pills with horizontal scroll */
        .nav-scroll-wrapper {
            position: relative;
        }
        /* Gradient fade indicators on left/right edges */
        .nav-scroll-wrapper::before,
        .nav-scroll-wrapper::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 8px;
            width: 32px;
            z-index: 5;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .nav-scroll-wrapper::before {
            left: 0;
            background: linear-gradient(to right, rgba(248, 249, 250, 0.95), transparent);
        }
        .nav-scroll-wrapper::after {
            right: 0;
            background: linear-gradient(to left, rgba(248, 249, 250, 0.95), transparent);
        }
        .nav-scroll-wrapper.at-start::before { opacity: 0; }
        .nav-scroll-wrapper.at-end::after   { opacity: 0; }

        .custom-nav-pills {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 6px;
            gap: 0.5rem;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            /* Thin visible scrollbar */
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        .custom-nav-pills::-webkit-scrollbar {
            height: 4px;
        }
        .custom-nav-pills::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-nav-pills::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 50rem;
        }
        .custom-nav-pills::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }

        .custom-nav-pills .nav-link {
            border-radius: 50rem;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            color: #64748b;
            background-color: #f1f5f9;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .custom-nav-pills .nav-link:hover {
            background-color: #e2e8f0;
            color: #334155;
            transform: translateY(-1px);
        }
        .custom-nav-pills .nav-link.active {
            background-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }
    </style>

    <!-- Nav Tabs -->
    <div class="nav-scroll-wrapper mb-4">
        <ul class="nav custom-nav-pills" id="pengumumanTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pengumuman-tab" data-bs-toggle="pill" data-bs-target="#pengumuman-content" type="button" role="tab" aria-controls="pengumuman-content" aria-selected="true">
                    <i class="bi bi-megaphone me-1"></i> Daftar Pengumuman
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="kategori-tab" data-bs-toggle="pill" data-bs-target="#kategori-content" type="button" role="tab" aria-controls="kategori-content" aria-selected="false">
                    <i class="bi bi-tags me-1"></i> Manajemen Kategori
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="pengumumanTabsContent">
        <!-- Tab Daftar Pengumuman -->
        <div class="tab-pane fade show active" id="pengumuman-content" role="tabpanel" aria-labelledby="pengumuman-tab">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>TANGGAL</th>
                                    <th>JUDUL</th>
                                    <th>KATEGORI</th>
                                    <th>SEKOLAH</th>
                                    <th>LAMPIRAN</th>
                                    <th>VISIBILITAS</th>
                                    <th>STATUS</th>
                                    <th class="text-end">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pengumuman)): ?>
                                    <tr><td colspan="8" class="text-center py-4">Belum ada data pengumuman.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pengumuman as $row): ?>
                                        <tr>
                                            <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['judul']) ?></div>
                                                <small class="text-muted">Oleh: <?= htmlspecialchars($row['nama_pembuat']) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($row['kategori_id']): ?>
                                                    <span class="badge bg-info text-dark"><i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($row['nama_kategori']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted fs-8">Tanpa Kategori</span>
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

        <!-- Tab Manajemen Kategori -->
        <div class="tab-pane fade" id="kategori-content" role="tabpanel" aria-labelledby="kategori-tab">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold">Daftar Kategori</h5>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addKategoriModal">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>NO</th>
                                    <th>NAMA KATEGORI</th>
                                    <th class="text-end">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($kategoriList)): ?>
                                    <tr><td colspan="3" class="text-center py-4">Belum ada data kategori.</td></tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($kategoriList as $k): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><span class="fw-bold"><?= htmlspecialchars($k['nama_kategori']) ?></span></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary" onclick="editKategori(<?= htmlspecialchars(json_encode($k)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="/SINTA-SaaS/informasi/pengumuman/kategori/delete" method="POST" class="d-inline" onsubmit="return confirm('Menghapus kategori akan mengosongkan kategori pada pengumuman terkait. Lanjutkan?');">
                                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
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

<!-- Modal Tambah/Edit Kategori -->
<div class="modal fade" id="addKategoriModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <form action="/SINTA-SaaS/informasi/pengumuman/kategori/store" method="POST" id="formKategori">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="kategoriModalTitle">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="k_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Kategori</label>
                        <input type="text" class="form-control" name="nama_kategori" id="k_nama" required placeholder="Contoh: Info Akademik, Kegiatan Ekstrakurikuler">
                    </div>
                    
                    <?php if (isset($isSuperAdmin) && $isSuperAdmin): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Sekolah (Khusus Admin)</label>
                        <select class="form-select" name="tenant_id" id="k_tenant_id">
                            <option value="global">Kategori Global (Semua Sekolah)</option>
                            <?php foreach ($tenants as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah/Edit Pengumuman -->
<div class="modal fade" id="addPengumumanModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
                                <label class="form-label fw-bold">Kategori Pengumuman</label>
                                <select class="form-select" name="kategori_id" id="p_kategori">
                                    <option value="">-- Tanpa Kategori --</option>
                                    <?php foreach ($kategoriList as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
// Prevent multiple instances of CKEditor by destroying the old one on modal hide
// and initializing properly when needed.

window.editorInstance = null;

window.initEditor = function() {
    if (typeof ClassicEditor !== 'undefined') {
        const el = document.querySelector('#p_isi');
        // Only initialize if not already initialized
        if (el && !window.editorInstance) {
            ClassicEditor
                .create(el, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
                    table: {
                        contentToolbar: [ 'tableColumn', 'tableRow', 'mergeTableCells' ]
                    }
                })
                .then(editor => {
                    window.editorInstance = editor;
                    // Auto-update the textarea on change so we don't have to wait for form submit
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

// Use Turbo load to manage the script loading
document.addEventListener('turbo:load', function() {
    // If there's an existing instance left over from before turbo transition, destroy it to be safe
    if (window.editorInstance) {
        window.editorInstance.destroy().then(() => {
            window.editorInstance = null;
        }).catch(e => console.log(e));
    }
    
    // We only initialize the editor when the modal opens! This prevents multiple instances 
    // from being created if Turbo somehow renders multiple textareas.
});

// Initialize editor on modal show to ensure clean state
document.getElementById('addPengumumanModal').addEventListener('shown.bs.modal', function () {
    window.loadEditorScript();
});

// Destroy editor on modal hide to ensure it doesn't duplicate on next open
document.getElementById('addPengumumanModal').addEventListener('hidden.bs.modal', function () {
    if (window.editorInstance) {
        window.editorInstance.destroy().then(() => {
            window.editorInstance = null;
        }).catch(e => console.log(e));
    }
    
    // Reset Form Pengumuman
    document.getElementById('modalTitle').innerText = 'Buat Pengumuman Baru';
    document.getElementById('formPengumuman').action = '/SINTA-SaaS/informasi/pengumuman/store';
    document.getElementById('p_id').value = '';
    document.getElementById('p_judul').value = '';
    document.getElementById('p_isi').value = '';
    
    let tid = document.getElementById('p_tenant_id');
    if (tid) tid.value = 'global';
    
    document.getElementById('p_kategori').value = '';
    document.getElementById('p_visibilitas').value = 'public';
    document.getElementById('p_active').checked = true;
    document.querySelectorAll('.role-check').forEach(cb => cb.checked = false);
    document.getElementById('lampiranInfo').style.display = 'none';
    toggleRoles();
});

// Reset Form Kategori on hide
document.getElementById('addKategoriModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('kategoriModalTitle').innerText = 'Tambah Kategori Baru';
    document.getElementById('formKategori').action = '/SINTA-SaaS/informasi/pengumuman/kategori/store';
    document.getElementById('k_id').value = '';
    document.getElementById('k_nama').value = '';
    
    let tid = document.getElementById('k_tenant_id');
    if (tid) tid.value = 'global';
});


window.toggleRoles = function() {
    const val = document.getElementById('p_visibilitas').value;
    document.getElementById('roleSelection').style.display = (val === 'private') ? 'block' : 'none';
};

window.editPengumuman = function(data) {
    document.getElementById('modalTitle').innerText = 'Edit Pengumuman';
    document.getElementById('formPengumuman').action = '/SINTA-SaaS/informasi/pengumuman/update';
    
    document.getElementById('p_id').value = data.id;
    document.getElementById('p_judul').value = data.judul;
    
    // We set the textarea value directly. The editor will pick it up when shown.bs.modal fires.
    document.getElementById('p_isi').value = data.isi_pengumuman;
    if (window.editorInstance) {
        window.editorInstance.setData(data.isi_pengumuman);
    }
    
    document.getElementById('p_kategori').value = data.kategori_id || '';
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

window.editKategori = function(data) {
    document.getElementById('kategoriModalTitle').innerText = 'Edit Kategori';
    document.getElementById('formKategori').action = '/SINTA-SaaS/informasi/pengumuman/kategori/update';
    
    document.getElementById('k_id').value = data.id;
    document.getElementById('k_nama').value = data.nama_kategori;
    
    let tid = document.getElementById('k_tenant_id');
    if (tid) {
        tid.value = data.tenant_id ? data.tenant_id : 'global';
    }
    
    var modal = new bootstrap.Modal(document.getElementById('addKategoriModal'));
    modal.show();
}
</script>
