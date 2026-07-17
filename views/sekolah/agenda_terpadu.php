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
    }
    .table-floating-rows {
        border-collapse: separate;
        border-spacing: 0 8px;
    }
    .table-floating-rows tr td {
        background: white;
        border: none;
    }
    .table-floating-rows tr td:first-child {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
    .table-floating-rows tr td:last-child {
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    .table-floating-rows tbody tr {
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .table-floating-rows tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    
    /* Smooth fade in for tabs */
    .tab-pane {
        animation: fadeIn 0.4s ease-out forwards;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    /* Navigation Tabs Styling */
    .scrollable-nav-tabs {
        padding-bottom: 5px;
        border-bottom: none;
    }
    .scrollable-nav-tabs::-webkit-scrollbar {
        height: 4px;
    }
    .scrollable-nav-tabs::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
    .nav-tabs-wrapper .nav-link {
        font-size: 14px;
        color: #475569;
        background-color: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        font-weight: 600;
        padding: 10px 16px;
        transition: all 0.2s ease-in-out;
    }
    .nav-tabs-wrapper .nav-link:hover {
        color: #2563eb;
    }
    .nav-tabs-wrapper .nav-link.active {
        color: #2563eb !important;
        background-color: transparent !important;
        border-bottom: 2px solid #2563eb !important;
    }
</style>

<div class="container-fluid py-4 bg-slate-50 min-h-screen font-sans">
    
    <!-- Premium Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight gradient-text mb-1 d-flex align-items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-xl">
                    <i class="bi bi-calendar2-range text-blue-600 text-2xl"></i>
                </div>
                Agenda & Timeline
            </h1>
            <p class="text-slate-500 font-medium ml-14">Kelola jadwal kegiatan publik dan timeline sekolah dengan elegan.</p>
        </div>
        
        <div class="flex flex-wrap gap-3 items-center">
            <!-- Filter Form -->
            <form method="GET" action="/SINTA-SaaS/informasi/agenda" class="m-0 flex flex-wrap gap-2 items-center bg-white p-1 rounded-2xl shadow-sm border border-slate-100 w-full md:w-auto justify-center md:justify-start">
                <?php if ($isSuperAdmin): ?>
                    <select name="filter_tenant_id" class="form-select border-0 bg-transparent text-sm font-semibold text-slate-700 cursor-pointer focus:ring-0 w-full md:w-auto" style="min-width: 180px;" onchange="this.form.submit()">
                        <option value="">-- Pilih Sekolah --</option>
                        <option value="global" <?= (isset($selectedTenant) && $selectedTenant === 'global') ? 'selected' : '' ?>>Semua (Global)</option>
                        <?php foreach ($tenants as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (isset($selectedTenant) && $selectedTenant === $t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hidden md:block w-px h-6 bg-slate-200"></div>
                <?php endif; ?>
                <input type="month" name="month" class="form-control border-0 bg-transparent text-sm font-semibold text-slate-700 cursor-pointer focus:ring-0 w-full md:w-auto" value="<?= htmlspecialchars($filterMonth) ?>" onchange="this.form.submit()">
            </form>

            <button class="relative p-2.5 bg-white text-slate-600 rounded-xl shadow-sm hover:shadow-md transition-all border border-slate-100 hover:text-amber-500 hover:border-amber-200 group" data-bs-toggle="modal" data-bs-target="#notifikasiModal">
                <i class="bi bi-bell-fill text-lg group-hover:animate-bounce"></i>
                <?php if (isset($upcoming) && count($upcoming) > 0): ?>
                <span class="absolute top-0 right-0 -mt-1 -mr-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white ring-2 ring-white">
                    <?= count($upcoming) ?>
                </span>
                <?php endif; ?>
            </button>

            <button class="btn btn-gradient text-white font-bold rounded-xl px-4 py-2.5 flex items-center gap-2 border-0" data-bs-toggle="modal" data-bs-target="#addAgendaModal">
                <i class="bi bi-plus-lg"></i>
                TAMBAH AGENDA
            </button>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-xl shadow-sm mb-6 flex items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill text-xl"></i>
            <span class="font-medium"><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    
    <?php if ($isSuperAdmin && empty($selectedTenant)): ?>
        <div class="alert bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-xl shadow-sm mb-6 flex items-center gap-3">
            <i class="bi bi-info-circle-fill text-xl"></i>
            <span class="font-medium">Anda login sebagai Super Admin. Pilih Sekolah di atas untuk melihat agenda spesifik.</span>
        </div>
    <?php endif; ?>

    <!-- Nav Tabs -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2" id="agendaTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition active" id="kalender-tab" data-bs-toggle="pill" data-bs-target="#kalender-pane" type="button" role="tab">
                            <i class="bi bi-calendar3 me-2 fs-6"></i> Kalender
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="list-tab" data-bs-toggle="pill" data-bs-target="#list-pane" type="button" role="tab">
                            <i class="bi bi-list-ul me-2 fs-6"></i> Daftar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition" id="kategori-tab" data-bs-toggle="pill" data-bs-target="#kategori-pane" type="button" role="tab">
                            <i class="bi bi-tags-fill me-2 fs-6"></i> Kategori
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="tab-content" id="agendaTabContent">
        
        <!-- TAB 1: KALENDER & TIMELINE -->
        <div class="tab-pane show active" id="kalender-pane" role="tabpanel">
            <div class="grid grid-cols-1 gap-6">
                
                <!-- Kategori Legenda -->
                <div class="glass-card rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h6 class="font-bold text-slate-600 mb-0 flex items-center gap-2">
                        <i class="bi bi-palette-fill text-slate-400"></i> Legenda
                    </h6>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($kategoriList as $kat): ?>
                            <span class="px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-2 shadow-sm bg-white border border-slate-100 text-slate-700 hover:scale-105 transition-transform cursor-default">
                                <span class="w-2.5 h-2.5 rounded-full shadow-inner" style="background-color: <?= $kat['kode_warna'] ?>;"></span>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Main Calendar -->
                <div class="glass-card rounded-3xl p-5 hover-lift">
                    <div id="calendar" class="fc-theme-standard"></div>
                </div>
                
                <!-- Timeline Horizontal -->
                <div class="glass-card rounded-3xl p-6 mt-4 hover-lift">
                    <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                        <h5 class="font-bold text-slate-800 text-lg m-0 flex items-center gap-2">
                            <i class="bi bi-bar-chart-steps text-blue-500"></i>
                            Garis Waktu Acara Utama
                        </h5>
                        <span class="bg-blue-50 text-blue-600 font-bold px-4 py-1.5 rounded-full text-sm">
                            <?= date('F Y', strtotime($filterMonth . '-01')) ?>
                        </span>
                    </div>
                    
                    <div class="overflow-x-auto pb-4">
                        <?php if (empty($filteredAgenda)): ?>
                            <div class="text-center py-12">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                                    <i class="bi bi-inbox text-3xl text-slate-300"></i>
                                </div>
                                <h6 class="font-bold text-slate-500">Belum ada kegiatan pada bulan ini</h6>
                            </div>
                        <?php else: ?>
                            <div id="gantt-target" class="w-full"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- TAB 2: DAFTAR KEGIATAN -->
        <div class="tab-pane fade" id="list-pane" role="tabpanel">
            <div class="glass-card rounded-3xl p-6 hover-lift mb-8">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                    <h5 class="font-bold text-slate-800 text-lg m-0 flex items-center gap-2">
                        <i class="bi bi-list-task text-blue-500"></i>
                        Daftar Kegiatan Agenda
                    </h5>
                </div>
                <div class="table-responsive">
                    <table id="agendaTable" class="table w-100 align-middle">
                        <thead>
                        <tr class="text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="pb-3 w-40">Tanggal & Waktu</th>
                            <th class="pb-3">Detail Kegiatan</th>
                            <th class="pb-3 text-center">Kategori</th>
                            <th class="pb-3 text-center">Status</th>
                            <th class="text-center pb-3 w-28">Aksi</th>
                        </tr>
                    </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($filteredAgenda)): ?>

                            <?php foreach ($filteredAgenda as $row): ?>
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <td class="py-4 text-slate-700">
                                        <div class="font-bold text-sm"><?= date('d M Y', strtotime($row['waktu_mulai'] ?? $row['tanggal_mulai'])) ?></div>
                                        <div class="text-slate-500 text-xs font-medium mt-1 flex items-center gap-1">
                                            <i class="bi bi-clock"></i> <?= date('H:i', strtotime($row['waktu_mulai'] ?? $row['waktu'] ?? '00:00')) ?>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <div class="font-bold text-slate-700 text-sm mb-1">
                                            <?= htmlspecialchars($row['judul']) ?>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="text-slate-500 text-xs font-medium flex items-center gap-1">
                                                <i class="bi bi-person-fill text-slate-400"></i>
                                                <?= htmlspecialchars($row['nama_pic'] ?? 'PIC Kosong') ?>
                                            </div>
                                            <?php if ($row['lampiran_file']): ?>
                                                <a href="/SINTA-SaaS/storage/app/public/<?= htmlspecialchars($row['lampiran_file']) ?>" target="_blank" class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100 font-semibold hover:bg-blue-100 transition-colors flex items-center gap-1 text-decoration-none">
                                                    <i class="bi bi-paperclip"></i> File
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-slate-700 border" style="background-color: <?= $row['kode_warna'] ? $row['kode_warna'].'15' : '#f1f5f9' ?>; border-color: <?= $row['kode_warna'] ? $row['kode_warna'].'30' : '#e2e8f0' ?>;">
                                            <span class="w-2 h-2 rounded-full inline-block mr-1 shadow-sm" style="background-color: <?= $row['kode_warna'] ?? '#64748b' ?>;"></span>
                                            <?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?>
                                        </span>
                                    </td>
                                    <td class="py-4">
                                        <div class="flex flex-col gap-1.5 items-center">
                                            <?php 
                                                $bg = 'bg-slate-50 text-slate-600 border-slate-200';
                                                if ($row['status_kegiatan'] == 'Sedang Berjalan') $bg = 'bg-amber-50 text-amber-600 border-amber-200';
                                                if ($row['status_kegiatan'] == 'Selesai') $bg = 'bg-emerald-50 text-emerald-600 border-emerald-200';
                                                if ($row['status_kegiatan'] == 'Terkonfirmasi') $bg = 'bg-blue-50 text-blue-600 border-blue-200';
                                                if ($row['status_kegiatan'] == 'Batal') $bg = 'bg-rose-50 text-rose-600 border-rose-200';
                                            ?>
                                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold border <?= $bg ?> text-center w-28">
                                                <?= htmlspecialchars($row['status_kegiatan']) ?>
                                            </span>
                                            
                                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border w-28 text-center <?= $row['visibilitas'] == 'public' ? 'border-emerald-200 text-emerald-600 bg-emerald-50' : 'border-rose-200 text-rose-600 bg-rose-50' ?>">
                                                <i class="bi <?= $row['visibilitas'] == 'public' ? 'bi-globe2' : 'bi-lock-fill' ?> mr-1"></i>
                                                <?= htmlspecialchars($row['target_audiens'] ?? 'Semua') ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-blue-600 hover:bg-blue-50 hover:border-blue-200 flex items-center justify-center transition-colors" onclick="editAgenda(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form action="/SINTA-SaaS/informasi/agenda/delete" method="POST" class="m-0" onsubmit="return confirm('Hapus kegiatan ini secara permanen?');">
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

        <!-- TAB 3: KATEGORI AGENDA -->
        <div class="tab-pane fade" id="kategori-pane" role="tabpanel">
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
                                <th class="pb-3">Indentifier Warna</th>
                                <th class="pb-3">Lingkup Sekolah</th>
                                <th class="text-center pb-3 w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($kategoriList)): ?>
                                <?php foreach ($kategoriList as $index => $row): ?>
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <td class="py-4 text-center font-medium text-slate-500"><?= $index + 1 ?></td>
                                    <td class="py-4 font-bold text-slate-700"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg shadow-inner border border-black/10" style="background-color: <?= $row['kode_warna'] ?>;"></div>
                                            <span class="text-sm font-mono text-slate-500 bg-slate-100 px-2 py-1 rounded"><?= htmlspecialchars($row['kode_warna']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <?php if (empty($row['tenant_id'])): ?>
                                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100">Global (Semua Sekolah)</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-white text-slate-600 border border-slate-200"><?= htmlspecialchars($row['nama_sekolah'] ?? 'Unknown') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-blue-600 hover:bg-blue-50 hover:border-blue-200 flex items-center justify-center transition-colors" title="Edit" onclick="editKategori(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-red-600 hover:bg-red-50 hover:border-red-200 flex items-center justify-center transition-colors" title="Hapus" onclick="deleteKategori(<?= $row['id'] ?>)">
                                                <i class="bi bi-trash3"></i>
                                            </button>
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

<!-- Modal Notifikasi (Pemberitahuan) -->
<div class="modal fade" id="notifikasiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-bell-fill text-warning me-2"></i> Pemberitahuan Agenda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <?php if (empty($upcoming)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                        Tidak ada jadwal mendesak yang mendekati deadline.
                    </div>
                <?php else: ?>
                    <p class="text-muted fs-7 mb-3">Tugas penting dan acara mendatang yang perlu perhatian:</p>
                    <div class="list-group list-group-flush gap-2">
                        <?php foreach ($upcoming as $u): ?>
                        <div class="list-group-item list-group-item-action rounded-3 border shadow-sm p-3">
                            <div class="d-flex w-100 justify-content-between mb-1">
                                <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($u['judul']) ?></h6>
                                <small class="text-danger fw-bold"><i class="bi bi-clock-history"></i> <?= date('d M', strtotime($u['waktu_mulai'] ?? $u['tanggal_mulai'])) ?></small>
                            </div>
                            <p class="mb-1 fs-7 text-muted"><?= htmlspecialchars(substr($u['deskripsi'] ?? 'Tidak ada deskripsi', 0, 60)) ?>...</p>
                            <span class="badge" style="background-color: <?= $u['kode_warna'] ?? '#0b5ed7' ?>;"><?= htmlspecialchars($u['status_kegiatan'] ?? 'Rencana') ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Agenda -->
<div class="modal fade" id="addAgendaModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form action="/SINTA-SaaS/informasi/agenda/store" method="POST" enctype="multipart/form-data" id="formAgenda">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-journal-plus me-2 text-primary"></i> Tambah Kegiatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="a_id">
                    
                    <h6 class="fw-bold text-primary mb-3">Informasi Utama</h6>
                    <div class="row mb-3">
                        <div class="col-md-12 mb-3">
                            <label for="a_judul" class="form-label fw-bold">Judul Kegiatan</label>
                            <input type="text" class="form-control" name="judul" id="a_judul" required placeholder="Contoh: Ujian Tengah Semester Ganjil">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="a_kategori_id" class="form-label fw-bold">Kategori Agenda</label>
                            <select class="form-select" name="kategori_id" id="a_kategori_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategoriList as $k): ?>
                                    <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="a_status" class="form-label fw-bold">Status Pelaksanaan</label>
                            <select class="form-select" name="status_kegiatan" id="a_status">
                                <option value="Rencana">Rencana / Draf</option>
                                <option value="Terkonfirmasi">Terkonfirmasi</option>
                                <option value="Sedang Berjalan">Sedang Berjalan</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Batal">Batal</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3 border-top pt-3">Waktu & Tempat</h6>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="a_waktu_mulai" class="form-label fw-bold">Waktu Mulai</label>
                            <input type="datetime-local" class="form-control" name="waktu_mulai" id="a_waktu_mulai" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="a_waktu_selesai" class="form-label fw-bold">Waktu Selesai</label>
                            <input type="datetime-local" class="form-control" name="waktu_selesai" id="a_waktu_selesai">
                        </div>
                        <div class="col-md-12">
                            <label for="a_lokasi" class="form-label fw-bold">Lokasi / Ruangan</label>
                            <input type="text" class="form-control" name="lokasi" id="a_lokasi" placeholder="Contoh: Aula Utama / Zoom Meeting">
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3 border-top pt-3">Kolaborasi Internal</h6>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="a_pic_id" class="form-label fw-bold">Person In Charge (PIC)</label>
                            <select class="form-select" name="pic_id" id="a_pic_id">
                                <option value="">-- Tidak ada PIC spesifik --</option>
                                <?php foreach ($pics as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="a_target_audiens" class="form-label fw-bold">Target Audiens</label>
                            <select class="form-select" name="target_audiens" id="a_target_audiens">
                                <option value="Semua">Semua (Umum)</option>
                                <option value="Siswa">Siswa</option>
                                <option value="Guru">Guru & Staf</option>
                                <option value="Wali Murid">Wali Murid</option>
                                <option value="Internal Manajemen">Internal Manajemen</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (isset($isSuperAdmin) && $isSuperAdmin): ?>
                    <div class="row mb-3">
                        <div class="col-md-12 bg-light p-3 rounded border">
                            <label for="a_tenant_id" class="form-label fw-bold text-danger">Target Sekolah (Khusus Super Admin)</label>
                            <select class="form-select border-danger" name="tenant_id" id="a_tenant_id">
                                <option value="global">Agenda Global (Semua Sekolah)</option>
                                <?php foreach ($tenants as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3 border-top pt-3">
                        <label for="a_deskripsi" class="form-label fw-bold">Deskripsi Tambahan / Instruksi</label>
                        <textarea class="form-control" name="deskripsi" id="a_deskripsi" rows="3" placeholder="Tulis rincian kegiatan..."></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="a_visibilitas" class="form-label fw-bold">Visibilitas Sistem</label>
                            <select class="form-select" name="visibilitas" id="a_visibilitas" onchange="toggleRoles()">
                                <option value="public">Publik (Tampil di Dashboard Semua)</option>
                                <option value="guru">Hanya Guru/Staf</option>
                                <option value="private">Private (Pilih Role Khusus)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="a_lampiran" class="form-label fw-bold">Dokumen Lampiran (Opsional)</label>
                            <input type="file" class="form-control" name="lampiran" id="a_lampiran" accept=".pdf,.jpg,.png">
                            <div id="lampiranInfo" class="mt-1 bg-light p-2 rounded" style="display:none;">
                                <small class="text-success fw-bold"><i class="bi bi-check-circle"></i> File terlampir</small>
                                <label for="delLampiran" class="form-check-label text-danger ms-3" style="font-size:13px; cursor: pointer;">
                                    <input type="checkbox" name="hapus_lampiran" value="1" id="delLampiran"> Hapus File Lama
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="roleSelection" style="display:none;">
                        <div class="col-12">
                            <label class="form-label fw-bold">Pilih Role Khusus (Visibilitas Private)</label>
                            <div class="border p-2 bg-light shadow-inner" style="max-height:120px; overflow-y:auto; border-radius:6px;">
                                <?php foreach ($roleList as $r): ?>
                                    <div class="form-check d-inline-block me-3 mb-1">
                                        <input class="form-check-input role-check" type="checkbox" name="target_roles[]" value="<?= $r['id'] ?>" id="role_<?= $r['id'] ?>">
                                        <label class="form-check-label text-muted" for="role_<?= $r['id'] ?>">
                                            <?= htmlspecialchars($r['nama_role']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-light border px-4 rounded-pill shadow-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm"><i class="bi bi-save me-1"></i> Simpan Agenda</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Agenda per Tanggal -->
<div class="modal fade" id="agendaDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold text-dark" id="agendaDetailTitle">Kegiatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="agendaDetailBody">
                <!-- content injected via JS -->
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-primary shadow-sm" id="btnTambahDariDetail" style="border-radius: 8px;">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kegiatan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.toggleRoles = function() {
    var isGlobal = document.getElementById('a_is_global') ? document.getElementById('a_is_global').checked : false;
    if (document.getElementById('a_target_roles_div')) {
        document.getElementById('a_target_roles_div').style.display = isGlobal ? 'none' : 'block';
    }
    const val = document.getElementById('a_visibilitas').value;
    document.getElementById('roleSelection').style.display = (val === 'private') ? 'block' : 'none';
};

function editKategori(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_nama').value = data.nama_kategori;
    document.getElementById('edit_warna').value = data.kode_warna;
    
    let editTenant = document.getElementById('edit_tenant_id');
    if (editTenant) {
        editTenant.value = data.tenant_id ? data.tenant_id : 'global';
    }
    
    var modal = new bootstrap.Modal(document.getElementById('editKategoriModal'));
    modal.show();
}

function deleteKategori(id) {
    Swal.fire({
        title: 'Hapus Kategori?',
        text: "Kategori yang sudah dihapus tidak dapat dikembalikan. Kategori ini tidak bisa dihapus jika sedang digunakan oleh suatu kegiatan.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete_kategori_id').value = id;
            document.getElementById('deleteKategoriForm').submit();
        }
    });
}

// Inisialisasi Tom Select untuk pencarian PIC
var picSelect = null;

window.editAgenda = function(data) {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-primary"></i> Edit Kegiatan';
    document.getElementById('formAgenda').action = '/SINTA-SaaS/informasi/agenda/update';
    
    document.getElementById('a_id').value = data.id;
    document.getElementById('a_judul').value = data.judul;
    document.getElementById('a_kategori_id').value = data.kategori_id || '';
    document.getElementById('a_lokasi').value = data.lokasi || '';
    
    // Set TomSelect value if initialized, else standard select
    if (picSelect) {
        picSelect.setValue(data.pic_id || '');
    } else {
        document.getElementById('a_pic_id').value = data.pic_id || '';
    }
    
    document.getElementById('a_target_audiens').value = data.target_audiens || 'Semua';
    
    // Set datetime local values
    if (data.waktu_mulai) {
        document.getElementById('a_waktu_mulai').value = data.waktu_mulai.substring(0, 16);
    } else if (data.tanggal_mulai) {
        let w = data.waktu ? data.waktu.substring(0,5) : '00:00';
        document.getElementById('a_waktu_mulai').value = data.tanggal_mulai + 'T' + w;
    }
    
    if (data.waktu_selesai) {
        document.getElementById('a_waktu_selesai').value = data.waktu_selesai.substring(0, 16);
    } else if (data.tanggal_selesai) {
        document.getElementById('a_waktu_selesai').value = data.tanggal_selesai + 'T23:59';
    }

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
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-journal-plus me-2 text-primary"></i> Tambah Kegiatan';
    document.getElementById('formAgenda').action = '/SINTA-SaaS/informasi/agenda/store';
    document.getElementById('a_id').value = '';
    document.getElementById('formAgenda').reset();
    document.getElementById('a_waktu_mulai').value = '';
    document.getElementById('a_waktu_selesai').value = '';
    document.getElementById('a_status').value = 'Rencana';
    document.getElementById('a_visibilitas').value = 'public';
    document.getElementById('a_target_audiens').value = 'Semua';
    document.getElementById('a_kategori_id').value = '';
    
    let tid = document.getElementById('a_tenant_id');
    if (tid) tid.value = 'global';
    
    if (picSelect) {
        picSelect.setValue('');
    }
    
    document.getElementById('lampiranInfo').style.display = 'none';
    toggleRoles();
});
</script>
<!-- Modal Tambah Kategori -->
<div class="modal fade" id="addKategoriModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none;">
            <div class="modal-header bg-primary text-white border-0" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Kategori</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/informasi/kategori-agenda/store" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="add_kategori_nama" class="form-label fw-bold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_kategori" id="add_kategori_nama" required placeholder="Contoh: Rapat Wali Murid" style="border-radius: 8px;">
                    </div>
                    <?php if ($isSuperAdmin): ?>
                    <div class="mb-3">
                        <label for="add_kategori_tenant_id" class="form-label fw-bold">Sekolah / Tenant <span class="text-danger">*</span></label>
                        <select class="form-select" name="tenant_id" id="add_kategori_tenant_id" required style="border-radius: 8px;">
                            <option value="global">Semua Sekolah (Global)</option>
                            <?php foreach ($tenants as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Pilih Global jika kategori ini berlaku untuk semua sekolah.</div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="add_kategori_warna" class="form-label fw-bold">Kode Warna <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color p-1" name="kode_warna" id="add_kategori_warna" value="#0b5ed7" title="Pilih warna kategori" style="border-radius: 8px; width: 60px;">
                            <span class="text-muted fs-7">Warna ini akan digunakan pada kalender dan timeline.</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm" style="border-radius: 8px;">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="editKategoriModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none;">
            <div class="modal-header bg-info text-white border-0" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Kategori</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/SINTA-SaaS/informasi/kategori-agenda/update" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_nama" class="form-label fw-bold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_kategori" id="edit_nama" required style="border-radius: 8px;">
                    </div>
                    <?php if ($isSuperAdmin): ?>
                    <div class="mb-3">
                        <label for="edit_tenant_id" class="form-label fw-bold">Sekolah / Tenant <span class="text-danger">*</span></label>
                        <select class="form-select" name="tenant_id" id="edit_tenant_id" required style="border-radius: 8px;">
                            <option value="global">Semua Sekolah (Global)</option>
                            <?php foreach ($tenants as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_sekolah']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="edit_warna" class="form-label fw-bold">Kode Warna <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color p-1" name="kode_warna" id="edit_warna" title="Pilih warna kategori" style="border-radius: 8px; width: 60px;">
                            <span class="text-muted fs-7">Warna ini akan digunakan pada kalender dan timeline.</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-info text-white px-4 fw-semibold shadow-sm" style="border-radius: 8px;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form Hapus Hidden -->
<form id="deleteKategoriForm" action="/SINTA-SaaS/informasi/kategori-agenda/delete" method="POST" style="display: none;">
    <input type="hidden" name="id" id="delete_kategori_id">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" crossorigin="anonymous"></script>
<!-- Tom Select CSS & JS untuk Searchable Dropdown -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.css" rel="stylesheet" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js" crossorigin="anonymous"></script>

<!-- Simple DataTables CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.3.0/dist/style.css" rel="stylesheet" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.3.0/dist/umd/simple-datatables.js" crossorigin="anonymous"></script>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js" crossorigin="anonymous"></script>
<!-- Frappe Gantt JS & CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.css" crossorigin="anonymous">
<script src="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js" crossorigin="anonymous"></script>

<script>
// --- GLOBAL ERROR MONITOR ---
window.onerror = function(message, source, lineno, colno, error) {
    let errorDetails = {
        message: message,
        source: source,
        lineno: lineno,
        colno: colno,
        stack: error ? error.stack : 'N/A',
        url: window.location.href,
        time: new Date().toISOString()
    };
    
    // Tampilkan di console dengan warna merah jelas agar mudah diperbaiki
    console.group('%c[ERROR MONITOR] Terjadi Kesalahan JS', 'color: white; background: red; font-size: 14px; padding: 4px; border-radius: 4px;');
    console.error("Detail Error:", errorDetails);
    console.groupEnd();
    
    // Kirim ke server (Contoh endpoint monitor error, jika ada)
    // fetch('/api/monitor/log-error', {
    //     method: 'POST',
    //     headers: { 'Content-Type': 'application/json' },
    //     body: JSON.stringify(errorDetails)
    // }).catch(e => console.log('Gagal mengirim ke Error Monitor', e));

    return false; // Tetap biarkan default handler browser berjalan
};

window.addEventListener("unhandledrejection", function(event) {
    let errorDetails = {
        message: "Unhandled Promise Rejection: " + (event.reason ? (event.reason.message || event.reason) : 'Unknown'),
        stack: event.reason && event.reason.stack ? event.reason.stack : 'N/A',
        url: window.location.href,
        time: new Date().toISOString()
    };
    
    console.group('%c[ERROR MONITOR] Unhandled Promise Rejection', 'color: white; background: darkorange; font-size: 14px; padding: 4px; border-radius: 4px;');
    console.error("Detail Error:", errorDetails);
    console.groupEnd();
    
    // Kirim ke server
    // fetch('/api/monitor/log-error', { ... });
});
// --- END GLOBAL ERROR MONITOR ---
</script>

<style>
/* Kustomisasi CSS Frappe Gantt berdasarkan warna Kategori */
<?php foreach ($kategoriList as $kat): ?>
    .gantt-<?= str_replace('#', '', $kat['kode_warna']) ?> .bar { fill: <?= $kat['kode_warna'] ?> !important; }
    .gantt-<?= str_replace('#', '', $kat['kode_warna']) ?> .bar-progress { fill: <?= $kat['kode_warna'] ?> !important; opacity: 0.8; }
<?php endforeach; ?>
.gantt-0b5ed7 .bar { fill: #0b5ed7 !important; }
.gantt-0b5ed7 .bar-progress { fill: #0b5ed7 !important; opacity: 0.8; }
.gantt .grid-header { fill: #f8f9fa; }
.gantt .grid-row:nth-child(even) { fill: #f8f9fa; }
.gantt .bar-label { fill: #fff; font-weight: bold; font-size: 12px; }
</style>

<script>
// Fungsi inisialisasi yang mendukung Turbo Drive
function initAgendaTerpadu() {
    // Pastikan library CDN sudah selesai diunduh oleh Turbo sebelum jalan
    if (typeof TomSelect === 'undefined' || typeof FullCalendar === 'undefined' || typeof Gantt === 'undefined') {
        setTimeout(initAgendaTerpadu, 50); // Cek ulang 50ms lagi
        return;
    }

    // Inisialisasi Searchable Dropdown untuk PIC (Cegah inisialisasi ganda)
    if (document.getElementById('a_pic_id') && !document.getElementById('a_pic_id').tomselect) {
        picSelect = new TomSelect("#a_pic_id", {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            },
            placeholder: "Ketik nama untuk mencari..."
        });
    }

    // Fetch dynamic events and tasks via AJAX
    const filterTenantId = '<?= $selectedTenant ?>';
    axios.get(`/SINTA-SaaS/sekolah/agenda?ajax=1&action=get_agenda_data&filter_tenant_id=${filterTenantId}`)
        .then(response => {
            if (response.data && response.data.success) {
                const events = response.data.events || [];
                const tasks = response.data.ganttTasks || [];

                // Setup FullCalendar (Cegah inisialisasi ganda)
                var calendarEl = document.getElementById('calendar');
                if (calendarEl && !calendarEl.classList.contains('fc')) {
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        initialDate: '<?= $filterMonth ?>-01',
                        headerToolbar: {
                            left: 'prev,next',
                            center: 'title',
                            right: 'today'
                        },
                        height: 600, // Make it taller for full width
                        events: events,
                        dateClick: function(info) {
                            var dateStr = info.dateStr;
                            document.getElementById('agendaDetailTitle').innerText = 'Kegiatan Tanggal ' + dateStr;
                            
                            var filtered = calendar.getEvents().filter(e => {
                                var start = e.startStr.split('T')[0];
                                var end = e.endStr ? e.endStr.split('T')[0] : start;
                                return dateStr >= start && dateStr <= end;
                            });
                            
                            var bodyHtml = '';
                            if (filtered.length === 0) {
                                bodyHtml = '<div class="text-center py-4 text-muted"><i class="bi bi-calendar-x fs-1 opacity-50 mb-2 d-block"></i><p class="mb-0">Tidak ada kegiatan pada tanggal ini.</p></div>';
                            } else {
                                bodyHtml = '<div class="list-group list-group-flush">';
                                filtered.forEach(e => {
                                    bodyHtml += '<div class="list-group-item px-0 border-bottom-0 mb-3 rounded-3 p-3 shadow-sm" style="border-left: 4px solid '+e.backgroundColor+' !important; background-color: #f8f9fa;">';
                                    bodyHtml += '<h6 class="fw-bold mb-1">'+e.title+'</h6>';
                                    if (e.extendedProps && e.extendedProps.fullData) {
                                        if (e.extendedProps.fullData.lokasi) {
                                            bodyHtml += '<small class="text-muted d-block mt-1"><i class="bi bi-geo-alt me-1 text-danger"></i>'+e.extendedProps.fullData.lokasi+'</small>';
                                        }
                                        if (e.extendedProps.fullData.nama_pic) {
                                            bodyHtml += '<small class="text-muted d-block mt-1"><i class="bi bi-person me-1 text-primary"></i>PIC: '+e.extendedProps.fullData.nama_pic+'</small>';
                                        }
                                    }
                                    bodyHtml += '</div>';
                                });
                                bodyHtml += '</div>';
                            }
                            document.getElementById('agendaDetailBody').innerHTML = bodyHtml;
                            
                            document.getElementById('btnTambahDariDetail').onclick = function() {
                                var detailModal = bootstrap.Modal.getInstance(document.getElementById('agendaDetailModal'));
                                if (detailModal) detailModal.hide();
                                
                                document.getElementById('a_waktu_mulai').value = dateStr + 'T08:00';
                                var addModal = new bootstrap.Modal(document.getElementById('addAgendaModal'));
                                addModal.show();
                            };
                            
                            var dModal = new bootstrap.Modal(document.getElementById('agendaDetailModal'));
                            dModal.show();
                        },
                        eventClick: function(info) {
                            let data = info.event.extendedProps.fullData;
                            if (data) {
                                editAgenda(data);
                            }
                        }
                    });
                    
                    // Langsung render saat inisialisasi
                    calendar.render();
                    
                    document.getElementById('kalender-tab').addEventListener('shown.bs.tab', function () {
                        calendar.updateSize();
                    });
                    
                    // Timeout yang lebih panjang untuk memastikan DOM benar-benar siap
                    setTimeout(function() {
                        calendar.updateSize();
                        calendar.render();
                    }, 500);
                    
                    window.addEventListener('load', function() {
                        calendar.updateSize();
                    });
                }

                // Setup Frappe Gantt (Cegah inisialisasi ganda)
                var ganttTarget = document.getElementById('gantt-target');
                if (tasks && tasks.length > 0 && ganttTarget && ganttTarget.innerHTML.trim() === '') {
                    var gantt = new Gantt("#gantt-target", tasks, {
                        header_height: 50,
                        column_width: 30,
                        step: 24,
                        view_modes: ['Quarter Day', 'Half Day', 'Day', 'Week', 'Month'],
                        bar_height: 25,
                        bar_corner_radius: 5,
                        arrow_curve: 5,
                        padding: 18,
                        view_mode: 'Day',   
                        date_format: 'YYYY-MM-DD',
                        custom_popup_html: function(task) {
                            return `
                            <div class="p-2" style="background:#fff; border-radius:5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                <h6 class="mb-1 fw-bold">${task.name}</h6>
                                <small class="text-muted">${task.start} - ${task.end}</small>
                            </div>`;
                        }
                    });
                    // Automatically scroll to today or start
                    if (document.querySelector('.gantt-container')) {
                        document.querySelector('.gantt-container').scrollLeft = 0;
                    }
                }
            }
        })
        .catch(err => {
            console.error("Gagal memuat data agenda:", err);
        });
    
    // Inisialisasi Simple DataTables pada Daftar Agenda
    if (document.getElementById('agendaTable') && typeof simpleDatatables !== 'undefined') {
        if (!document.getElementById('agendaTable').classList.contains('dataTable-table')) {
            new simpleDatatables.DataTable("#agendaTable", {
                searchable: true,
                fixedHeight: false,
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                labels: {
                    placeholder: "Cari Kegiatan...",
                    perPage: "data per halaman",
                    noRows: "Belum ada data kegiatan.",
                    info: "Menampilkan {start} - {end} dari {rows} kegiatan"
                }
            });
        }
    }

    // Inisialisasi Simple DataTables pada Kategori
    if (document.getElementById('kategoriTable') && typeof simpleDatatables !== 'undefined') {
        if (!document.getElementById('kategoriTable').classList.contains('dataTable-table')) {
            new simpleDatatables.DataTable("#kategoriTable", {
                searchable: true,
                fixedHeight: false,
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                labels: {
                    placeholder: "Cari Kategori...",
                    perPage: "data per halaman",
                    noRows: "Belum ada data kategori agenda.",
                    info: "Menampilkan {start} - {end} dari {rows} kategori"
                }
            });
        }
    }
}

// Menjalankan fungsi inisialisasi saat event turbo:load (atau load biasa jika Turbo tidak ada)
if (typeof Turbo !== 'undefined') {
    document.addEventListener('turbo:load', initAgendaTerpadu);
} else {
    document.addEventListener('DOMContentLoaded', initAgendaTerpadu);
}

// Fallback: Jika script dijalankan ulang (eval) secara paksa
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initAgendaTerpadu();
}

</script>
