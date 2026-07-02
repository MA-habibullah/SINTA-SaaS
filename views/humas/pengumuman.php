<style>
    /* Custom Animations & Micro-interactions */
    .glass-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08);
    }
    .hover-lift {
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
    }
    .hover-lift:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.12);
    }
    .gradient-text {
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .btn-gradient {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        transition: all 0.3s ease;
    }
    .btn-gradient:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        transform: translateY(-2px);
    }
    .nav-pills .nav-link {
        transition: all 0.3s ease;
        border-radius: 999px;
        color: #64748b;
        font-weight: 600;
        padding: 0.6rem 1.25rem;
    }
    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        box-shadow: 0 4px 15px rgba(37,99,235,0.25);
        color: white !important;
    }
</style>

<div class="container-fluid py-4 bg-slate-50 min-h-screen font-sans">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <h1 class="h3 fw-bolder gradient-text mb-1">Manajemen Pengumuman</h1>
            <p class="text-slate-500 mb-0">Kelola pengumuman dan kategori untuk seluruh warga sekolah.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="d-flex justify-content-md-end gap-2">
                <button class="btn btn-gradient text-white fw-bold rounded-pill px-4 shadow-sm border-0 flex items-center gap-2" data-bs-toggle="modal" data-bs-target="#addPengumumanModal">
                    <i class="bi bi-plus-lg"></i> PENGUMUMAN BARU
                </button>
            </div>
        </div>
    </div>

    <!-- Error/Success Flash -->
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger bg-rose-50 border-rose-200 text-rose-700 rounded-xl shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-3"></i>
            <div><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Navigation Pills -->
    <div class="glass-card rounded-3xl p-2 mb-6 shadow-sm border border-white flex justify-between items-center gap-4 flex-wrap">
        <ul class="nav nav-pills flex-nowrap overflow-x-auto hide-scrollbar m-0" id="pengumumanTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active flex items-center gap-2" id="pengumuman-tab" data-bs-toggle="pill" data-bs-target="#pengumuman-content" type="button" role="tab">
                    <i class="bi bi-megaphone-fill"></i> Daftar Pengumuman
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link flex items-center gap-2" id="kategori-tab" data-bs-toggle="pill" data-bs-target="#kategori-content" type="button" role="tab">
                    <i class="bi bi-tags-fill"></i> Manajemen Kategori
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="pengumumanTabsContent">
        <!-- Tab Daftar Pengumuman -->
        <div class="tab-pane fade show active" id="pengumuman-content" role="tabpanel">
            <div class="glass-card rounded-3xl p-6 hover-lift mb-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b border-slate-100 pb-4 gap-4">
                    <h5 class="font-bold text-slate-800 text-lg m-0 flex items-center gap-2">
                        <i class="bi bi-megaphone text-blue-500"></i>
                        Daftar Pengumuman
                    </h5>
                    
                    <form method="GET" action="/SINTA-SaaS/informasi/pengumuman" class="d-flex flex-wrap gap-2 w-full md:w-auto">
                        <select name="kategori" class="form-select shadow-sm rounded-xl border-slate-200 text-sm py-2" style="width: auto; min-width: 140px;">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategoriList as $kat): ?>
                                <option value="<?= $kat['id'] ?>" <?= ($filters['kategori_id'] ?? '') === (string)$kat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="date" name="tanggal" class="form-control shadow-sm rounded-xl border-slate-200 text-sm py-2" style="width: auto;" value="<?= htmlspecialchars($filters['tanggal'] ?? '') ?>">
                        
                        <?php if ($isSuperAdmin): ?>
                        <select name="tenant" class="form-select shadow-sm rounded-xl border-slate-200 text-sm py-2" style="width: auto; min-width: 140px;">
                            <option value="">Semua Sekolah</option>
                            <?php foreach ($tenants as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= ($filters['tenant_id'] ?? '') === (string)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                        
                        <button class="btn btn-primary rounded-xl px-4 shadow-sm py-2 font-bold text-sm" type="submit">Filter</button>
                        
                        <?php if (!empty(array_filter($filters))): ?>
                            <a href="/SINTA-SaaS/informasi/pengumuman" class="btn btn-light rounded-xl px-3 border border-slate-200 shadow-sm text-rose-500 hover:bg-rose-50 hover:border-rose-200 transition-colors py-2" title="Reset"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-responsive">
                    <table id="pengumumanTable" class="table w-100 align-middle">
                        <thead>
                            <tr class="text-slate-400 text-xs uppercase tracking-wider font-semibold">
                                <th class="pb-3 w-40">Tanggal & Waktu</th>
                                <th class="pb-3">Judul Pengumuman</th>
                                <th class="pb-3 text-center">Kategori</th>
                                <th class="pb-3 text-center">Status</th>
                                <th class="text-center pb-3 w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($pengumuman)): ?>
                                <?php foreach ($pengumuman as $row): ?>
                                    <tr class="hover:bg-slate-50 transition-colors group">
                                        <td class="py-4 text-slate-700">
                                            <div class="font-bold text-sm"><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                            <div class="text-slate-500 text-xs font-medium mt-1 flex items-center gap-1">
                                                <i class="bi bi-clock"></i> <?= date('H:i', strtotime($row['created_at'])) ?>
                                            </div>
                                        </td>
                                        <td class="py-4">
                                            <div class="font-bold text-slate-700 text-sm mb-1">
                                                <?= htmlspecialchars($row['judul']) ?>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <div class="text-slate-500 text-xs font-medium flex items-center gap-1">
                                                    <i class="bi bi-person-fill text-slate-400"></i>
                                                    <?= htmlspecialchars($row['nama_pembuat']) ?>
                                                </div>
                                                <?php if ($row['lampiran_file']): ?>
                                                    <a href="/SINTA-SaaS/storage/app/public/<?= htmlspecialchars($row['lampiran_file']) ?>" target="_blank" class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100 font-semibold hover:bg-blue-100 transition-colors flex items-center gap-1 text-decoration-none">
                                                        <i class="bi bi-paperclip"></i> File
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if (empty($row['tenant_id'])): ?>
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-indigo-50 text-indigo-600 border border-indigo-100"><i class="bi bi-globe"></i> Global</span>
                                                <?php else: ?>
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200"><i class="bi bi-building"></i> <?= htmlspecialchars($row['nama_sekolah'] ?? 'Unknown') ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-4 text-center">
                                            <?php if ($row['kategori_id']): ?>
                                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-slate-700 border bg-slate-50 border-slate-200">
                                                    <?= htmlspecialchars($row['nama_kategori']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted fs-8">Tanpa Kategori</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4">
                                            <div class="flex flex-col gap-1.5 items-center">
                                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border <?= $row['is_active'] ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' ?> text-center w-28">
                                                    <?= $row['is_active'] ? 'Aktif' : 'Non-Aktif' ?>
                                                </span>
                                                
                                                <?php 
                                                    $visBg = 'bg-slate-50 text-slate-600 border-slate-200';
                                                    if ($row['visibilitas'] == 'public') $visBg = 'bg-blue-50 text-blue-600 border-blue-200';
                                                    if ($row['visibilitas'] == 'private') $visBg = 'bg-rose-50 text-rose-600 border-rose-200';
                                                ?>
                                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border w-28 text-center <?= $visBg ?>">
                                                    <i class="bi <?= $row['visibilitas'] == 'public' ? 'bi-globe2' : ($row['visibilitas'] == 'private' ? 'bi-lock-fill' : 'bi-shield') ?> mr-1"></i>
                                                    <?= strtoupper($row['visibilitas']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-blue-600 hover:bg-blue-50 hover:border-blue-200 flex items-center justify-center transition-colors" onclick='editPengumuman(<?= json_encode($row) ?>)' title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form action="/SINTA-SaaS/informasi/pengumuman/delete" method="POST" class="m-0" onsubmit="return confirm('Hapus pengumuman ini secara permanen?');">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 flex items-center justify-center transition-colors" title="Hapus">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Manajemen Kategori -->
        <div class="tab-pane fade" id="kategori-content" role="tabpanel">
            <div class="glass-card rounded-3xl p-6 hover-lift mb-8">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                    <h5 class="font-bold text-slate-800 text-lg m-0 flex items-center gap-2">
                        <i class="bi bi-tags text-blue-500"></i>
                        Manajemen Kategori
                    </h5>
                    <button class="btn bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl px-4 py-2 text-sm flex items-center gap-2 transition-colors border-0" data-bs-toggle="modal" data-bs-target="#addKategoriModal">
                        <i class="bi bi-plus-lg"></i> KATEGORI BARU
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="kategoriTable" class="table w-100 align-middle">
                        <thead>
                            <tr class="text-slate-400 text-xs uppercase tracking-wider font-semibold">
                                <th class="pb-3 w-16 text-center">No</th>
                                <th class="pb-3">Nama Kategori</th>
                                <th class="text-center pb-3 w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($kategoriList)): ?>
                                <?php $no = 1; foreach ($kategoriList as $k): ?>
                                    <tr class="hover:bg-slate-50 transition-colors group">
                                        <td class="py-4 text-center font-medium text-slate-500"><?= $no++ ?></td>
                                        <td class="py-4 font-bold text-slate-700"><?= htmlspecialchars($k['nama_kategori']) ?></td>
                                        <td class="py-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-blue-600 hover:bg-blue-50 hover:border-blue-200 flex items-center justify-center transition-colors" onclick='editKategori(<?= json_encode($k) ?>)' title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form action="/SINTA-SaaS/informasi/pengumuman/kategori/delete" method="POST" class="m-0" onsubmit="return confirm('Menghapus kategori akan mengosongkan kategori pada pengumuman terkait. Lanjutkan?');">
                                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                    <button type="submit" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 flex items-center justify-center transition-colors" title="Hapus">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </div>
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

function initPengumumanTables() {
    if (typeof simpleDatatables !== 'undefined') {
        if (document.getElementById('pengumumanTable') && !document.getElementById('pengumumanTable').classList.contains('dataTable-table')) {
            new simpleDatatables.DataTable("#pengumumanTable", {
                searchable: true,
                fixedHeight: false,
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                labels: {
                    placeholder: "Cari Pengumuman...",
                    perPage: "data per halaman",
                    noRows: "Belum ada data pengumuman.",
                    info: "Menampilkan {start} - {end} dari {rows} pengumuman"
                }
            });
        }
        
        if (document.getElementById('kategoriTable') && !document.getElementById('kategoriTable').classList.contains('dataTable-table')) {
            new simpleDatatables.DataTable("#kategoriTable", {
                searchable: true,
                fixedHeight: false,
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                labels: {
                    placeholder: "Cari Kategori...",
                    perPage: "data per halaman",
                    noRows: "Belum ada data kategori.",
                    info: "Menampilkan {start} - {end} dari {rows} kategori"
                }
            });
        }
    }
}

if (typeof Turbo !== 'undefined') {
    document.addEventListener('turbo:load', initPengumumanTables);
} else {
    document.addEventListener('DOMContentLoaded', initPengumumanTables);
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initPengumumanTables();
}

</script>
