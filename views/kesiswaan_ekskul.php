<?php
$title = "Ekstrakurikuler - Kesiswaan";
$user_roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
$show_all_tabs = in_array('super_admin', $user_roles, true) || in_array('operator_sekolah', $user_roles, true) || in_array('kesiswaan', $user_roles, true);
$active_tab = $_GET['tab'] ?? ($show_all_tabs ? 'master' : 'anggota');

// Check if user has lock authority (super_admin, operator_sekolah, or kesiswaan)
$can_lock = in_array('super_admin', $user_roles, true) || in_array('operator_sekolah', $user_roles, true) || in_array('kesiswaan', $user_roles, true);
?>
<style>
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

<div class="container-fluid py-4">
    <?php
    // Flash: import msg from session (legacy) or GET success/error
    $flash_success = null;
    $flash_error   = null;
    if (!empty($_SESSION['import_success_msg'])) {
        $flash_success = $_SESSION['import_success_msg'];
        unset($_SESSION['import_success_msg']);
    } elseif (isset($_GET['success']) && $_GET['success'] !== '') {
        $flash_success = htmlspecialchars($_GET['success']);
    }
    if (isset($_GET['error']) && $_GET['error'] !== '') {
        $flash_error = htmlspecialchars($_GET['error']);
    }
    ?>
    <?php if ($flash_success): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <span><?= $flash_success ?></span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <span><?= $flash_error ?></span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold"><i class="bi bi-controller text-primary me-2"></i>Modul Ekstrakurikuler</h1>
            <p class="text-muted mb-0">Kelola master data, anggota, jurnal kegiatan, dan penilaian ekskul.</p>
        </div>
        <?php if($is_super_admin): ?>
        <div class="bg-white p-2 rounded-3 shadow-sm border border-light">
            <form action="" method="GET" class="d-flex align-items-center mb-0">
                <i class="bi bi-building text-muted me-2 ms-2"></i>
                <label for="tenant_id" class="me-2 fw-bold text-muted small mb-0 text-nowrap">Sekolah:</label>
                <select name="tenant_id" id="tenant_id" class="form-select form-select-sm border-0 shadow-none bg-light fw-semibold" onchange="this.form.submit()" style="min-width: 250px;">
                    <?php if(empty($selected_tenant)): ?>
                        <option value="">-- Semua / Pilih Sekolah --</option>
                    <?php endif; ?>
                    <?php foreach($tenants as $t): ?>
                        <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($selected_tenant === $t['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nama_sekolah']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Nav Tabs -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-2 bg-white rounded-4">
            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto text-nowrap scrollable-nav-tabs gap-3 px-2" id="ekskulTab" role="tablist">
                    <?php if ($show_all_tabs): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition <?= $active_tab === 'master' ? 'active' : '' ?>" id="master-tab" data-bs-toggle="tab" data-bs-target="#master" type="button" role="tab" aria-controls="master" aria-selected="<?= $active_tab === 'master' ? 'true' : 'false' ?>">
                            <i class="bi bi-diagram-3 me-2 fs-6"></i>Master Ekskul
                        </button>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition <?= $active_tab === 'anggota' ? 'active' : '' ?>" id="anggota-tab" data-bs-toggle="tab" data-bs-target="#anggota" type="button" role="tab" aria-controls="anggota" aria-selected="<?= $active_tab === 'anggota' ? 'true' : 'false' ?>">
                            <i class="bi bi-people me-2 fs-6"></i>Kelola Anggota
                        </button>
                    </li>
                    <?php if ($show_all_tabs): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition <?= $active_tab === 'pembina' ? 'active' : '' ?>" id="pembina-tab" data-bs-toggle="tab" data-bs-target="#pembina" type="button" role="tab" aria-controls="pembina" aria-selected="<?= $active_tab === 'pembina' ? 'true' : 'false' ?>">
                            <i class="bi bi-person-badge me-2 fs-6"></i>Kelola Pembina
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition <?= $active_tab === 'jurnal' ? 'active' : '' ?>" id="jurnal-tab" data-bs-toggle="tab" data-bs-target="#jurnal" type="button" role="tab" aria-controls="jurnal" aria-selected="<?= $active_tab === 'jurnal' ? 'true' : 'false' ?>">
                            <i class="bi bi-journal-text me-2 fs-6"></i>Jurnal Kegiatan
                        </button>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link border-0 fw-semibold px-3 py-2.5 fs-7 transition <?= $active_tab === 'nilai' ? 'active' : '' ?>" id="nilai-tab" data-bs-toggle="tab" data-bs-target="#nilai" type="button" role="tab" aria-controls="nilai" aria-selected="<?= $active_tab === 'nilai' ? 'true' : 'false' ?>">
                            <i class="bi bi-check2-circle me-2 fs-6"></i>Penilaian & Presensi
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="ekskulTabContent">
        
        <!-- Tab: Master Ekskul -->
        <?php if ($show_all_tabs): ?>
        <div class="tab-pane fade <?= $active_tab === 'master' ? 'show active' : '' ?>" id="master" role="tabpanel" aria-labelledby="master-tab">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Ekstrakurikuler</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tambahEkskulModal">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Ekskul
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nama Ekskul</th>
                                    <th>Kategori</th>
                                    <th>Guru Pembina</th>
                                    <th>Status</th>
                                    <th class="text-center pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($master_ekskul)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data ekstrakurikuler.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($master_ekskul as $ekskul): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= htmlspecialchars($ekskul['nama_ekskul']) ?></td>
                                        <td>
                                            <?php if($ekskul['kategori'] === 'Wajib'): ?>
                                                <span class="badge bg-danger">Wajib</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark">Pilihan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($ekskul['nama_pembina'] ?? 'Belum Ditugaskan') ?></td>
                                        <td>
                                            <form action="/SINTA-SaaS/api/v1/ekskul/toggle-status<?= $is_super_admin ? '?tenant_id=' . urlencode($selected_tenant) : '' ?>" method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($ekskul['id']) ?>">
                                                <select name="new_status" class="form-select form-select-sm <?= ($ekskul['status'] ?? 'active') === 'active' ? 'border-success text-success fw-bold' : 'border-secondary text-secondary fw-bold' ?>" onchange="if(confirm('Yakin ingin mengubah status?')) this.form.submit(); else this.value='<?= htmlspecialchars($ekskul['status'] ?? 'active') ?>';">
                                                    <option value="active" <?= ($ekskul['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="inactive" <?= ($ekskul['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="text-center pe-4">
                                            <button class="btn btn-sm btn-outline-secondary" title="Edit" data-bs-toggle="modal" data-bs-target="#editEkskulModal" data-id="<?= htmlspecialchars($ekskul['id']) ?>" data-nama="<?= htmlspecialchars($ekskul['nama_ekskul']) ?>" data-kategori="<?= htmlspecialchars($ekskul['kategori']) ?>" data-pembina="<?= htmlspecialchars($ekskul['pembina_id'] ?? '') ?>" onclick="populateEditEkskul(this)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div><!-- /.card-body -->
            </div><!-- /.card -->
        </div><!-- /.tab-pane -->
        <?php endif; ?>


        <!-- Tab: Kelola Pembina -->
        <?php if ($show_all_tabs): ?>
        <div class="tab-pane fade <?= $active_tab === 'pembina' ? 'show active' : '' ?>" id="pembina" role="tabpanel" aria-labelledby="pembina-tab">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Guru Pembina</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tambahPembinaModal">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Pembina
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nama Lengkap</th>
                                    <th>Email (Username Login)</th>
                                    <th>No. Telepon</th>
                                    <th>Status</th>
                                    <th class="text-center pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($pembina_list)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data pembina.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($pembina_list as $pembina): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= htmlspecialchars($pembina['nama_lengkap']) ?></td>
                                        <td><?= htmlspecialchars($pembina['email']) ?></td>
                                        <td>
                                            <?php if (!empty($pembina['no_telp'])): ?>
                                                <a href="tel:<?= htmlspecialchars($pembina['no_telp']) ?>" class="text-decoration-none">
                                                    <i class="bi bi-telephone-fill text-success me-1"></i><?= htmlspecialchars($pembina['no_telp']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form action="/SINTA-SaaS/api/v1/ekskul/pembina/toggle-status<?= $is_super_admin ? '?tenant_id=' . urlencode($selected_tenant) : '' ?>" method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($pembina['id']) ?>">
                                                <select name="new_status" class="form-select form-select-sm <?= ($pembina['status'] ?? 'active') === 'active' ? 'border-success text-success fw-bold' : 'border-secondary text-secondary fw-bold' ?>" onchange="if(confirm('Yakin ingin mengubah status pembina?')) this.form.submit(); else this.value='<?= htmlspecialchars($pembina['status'] ?? 'active') ?>';">
                                                    <option value="active" <?= ($pembina['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="inactive" <?= ($pembina['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="text-center pe-4">
                                            <button class="btn btn-sm btn-outline-secondary" title="Edit" data-bs-toggle="modal" data-bs-target="#editPembinaModal" data-id="<?= htmlspecialchars($pembina['id']) ?>" data-nama="<?= htmlspecialchars($pembina['nama_lengkap']) ?>" data-email="<?= htmlspecialchars($pembina['email']) ?>" data-notelp="<?= htmlspecialchars($pembina['no_telp'] ?? '') ?>" onclick="populateEditPembina(this)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
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
        <?php endif; ?>

        <!-- Tab: Kelola Anggota -->
        <div class="tab-pane fade <?= $active_tab === 'anggota' ? 'show active' : '' ?>" id="anggota" role="tabpanel" aria-labelledby="anggota-tab">
            
            <!-- Filter & Selection Section -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-muted small">PILIH EKSTRAKURIKULER</label>
                            <select id="selected_ekskul_selector" class="form-select border-2 border-primary-subtle" onchange="changeEkskulFilter(this.value)">
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php foreach($master_ekskul as $ekskul): ?>
                                    <?php if (($ekskul['status'] ?? 'active') === 'active'): ?>
                                        <option value="<?= htmlspecialchars($ekskul['id']) ?>" <?= $selected_ekskul_id === $ekskul['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ekskul['nama_ekskul']) ?> (<?= htmlspecialchars($ekskul['kategori']) ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-muted small">PILIH TAHUN AJARAN</label>
                            <select id="selected_ta_selector" class="form-select border-2 border-primary-subtle" onchange="changeTaFilter(this.value)">
                                <?php foreach($all_tahun_ajaran as $ta): ?>
                                    <option value="<?= htmlspecialchars($ta['id']) ?>" <?= $selected_ta_id === $ta['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ta['tahun_ajaran']) ?> <?= $ta['is_active'] == 1 ? '(Aktif)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-muted small">PILIH SEMESTER</label>
                            <select id="selected_semester_selector" class="form-select border-2 border-primary-subtle" onchange="changeSemesterFilter(this.value)">
                                <option value="Ganjil" <?= $selected_semester === 'Ganjil' ? 'selected' : '' ?>>Semester Ganjil</option>
                                <option value="Genap" <?= $selected_semester === 'Genap' ? 'selected' : '' ?>>Semester Genap</option>
                            </select>
                        </div>
                    </div>
                    <?php if (isset($is_historical) && $is_historical): ?>
                    <div class="alert alert-warning mt-3 mb-0 py-2 d-flex align-items-center">
                        <i class="bi bi-clock-history fs-5 me-2"></i>
                        <span>Anda sedang melihat data historis untuk Tahun Ajaran <strong><?= htmlspecialchars($active_ta['tahun_ajaran'] ?? '') ?></strong> Semester <strong><?= htmlspecialchars($selected_semester) ?></strong>.</span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($selected_ekskul_id)): ?>
                    <hr class="my-3 border-light">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center">
                            <?php if ($kunci_anggota): ?>
                                <span class="badge bg-danger px-3 py-2 rounded-3 fs-7">
                                    <i class="bi bi-lock-fill me-1"></i> Keanggotaan Terkunci
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success px-3 py-2 rounded-3 fs-7">
                                    <i class="bi bi-unlock-fill me-1"></i> Keanggotaan Terbuka
                                </span>
                            <?php endif; ?>
                            <span class="text-muted small ms-2">
                                <?= $kunci_anggota ? 'Anggota tidak dapat ditambah atau dikeluarkan (Sudah di-input di Rapot).' : 'Anggota dapat dikelola secara bebas.' ?>
                            </span>
                        </div>
                        <?php if ($can_lock): ?>
                            <form action="/SINTA-SaaS/api/v1/ekskul/kunci/anggota" method="POST" class="mb-0">
                                <input type="hidden" name="ekskul_id" value="<?= htmlspecialchars($selected_ekskul_id) ?>">
                                <input type="hidden" name="tahun_ajaran_id" value="<?= htmlspecialchars($active_ta['id'] ?? '') ?>">
                                <input type="hidden" name="semester" value="<?= htmlspecialchars($selected_semester) ?>">
                                <?php if($is_super_admin): ?>
                                    <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($selected_tenant) ?>">
                                <?php endif; ?>
                                <input type="hidden" name="lock" value="<?= $kunci_anggota ? 0 : 1 ?>">
                                <button type="submit" class="btn btn-sm <?= $kunci_anggota ? 'btn-outline-success' : 'btn-outline-danger' ?> rounded-3">
                                    <i class="bi <?= $kunci_anggota ? 'bi-unlock-fill' : 'bi-lock-fill' ?> me-1"></i>
                                    <?= $kunci_anggota ? 'Buka Kunci Keanggotaan' : 'Kunci Keanggotaan' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(empty($selected_ekskul_id)): ?>
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body py-5 text-center">
                        <i class="bi bi-search text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5>Pilih Ekstrakurikuler Terlebih Dahulu</h5>
                        <p class="text-muted">Gunakan dropdown di atas untuk memilih ekstrakurikuler yang ingin dikelola anggotanya.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    
                    <!-- Left: Current Members -->
                    <div class="col-lg-7 mb-4">
                        <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                            <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-people-fill me-1"></i> Anggota Saat Ini (<?= $total_members ?> Siswa)
                                </h6>
                                <?php if (!empty($active_ta) && !empty($selected_ekskul_id)): ?>
                                <a href="/SINTA-SaaS/api/v1/ekskul/anggota/export?ekskul_id=<?= urlencode($selected_ekskul_id) ?>&semester=<?= urlencode($selected_semester) ?>&tahun_ajaran_id=<?= urlencode($active_ta['id']) ?><?= $is_super_admin ? '&tenant_id=' . urlencode($selected_tenant) : '' ?>" class="btn btn-sm btn-outline-success rounded-3" title="Ekspor rekap anggota ke Excel">
                                    <i class="bi bi-file-earmark-excel me-1"></i>Ekspor Rekap
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th class="ps-4">Nama Lengkap</th>
                                                <th>NISN</th>
                                                <th>Kelas</th>
                                                <th class="text-center pe-4" style="width: 120px;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($current_members)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-5 text-muted">
                                                        <i class="bi bi-person-exclamation fs-3 d-block mb-2"></i>
                                                        Belum ada anggota terdaftar di ekskul ini.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach($current_members as $member): ?>
                                                    <tr>
                                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($member['nama_lengkap']) ?></td>
                                                        <td><code class="text-secondary"><?= htmlspecialchars($member['nisn']) ?></code></td>
                                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($member['nama_kelas'] ?? 'Tanpa Kelas') ?></span></td>
                                                        <td class="text-center pe-4">
                                                            <form action="/SINTA-SaaS/api/v1/ekskul/anggota/hapus<?= $is_super_admin ? '?tenant_id=' . urlencode($selected_tenant) : '' ?>" method="POST" class="d-inline" onsubmit="return confirm('Keluarkan <?= htmlspecialchars(addslashes($member['nama_lengkap'])) ?> dari ekstrakurikuler ini?');">
                                                                <input type="hidden" name="membership_id" value="<?= htmlspecialchars($member['membership_id']) ?>">
                                                                <input type="hidden" name="ekskul_id" value="<?= htmlspecialchars($selected_ekskul_id) ?>">
                                                                <input type="hidden" name="semester" value="<?= htmlspecialchars($selected_semester) ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Keluarkan" <?= $kunci_anggota ? 'disabled' : '' ?>>
                                                                    <i class="bi bi-person-dash"></i> Keluarkan
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($total_pages_anggota > 1): ?>
                                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center px-4 py-2">
                                    <small class="text-muted">Halaman <?= $page_anggota ?> dari <?= $total_pages_anggota ?></small>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0 gap-1">
                                            <?php if ($page_anggota > 1): ?>
                                            <li class="page-item"><a class="page-link rounded-2" href="?tab=anggota&ekskul_id=<?= urlencode($selected_ekskul_id) ?>&semester=<?= urlencode($selected_semester) ?>&page_anggota=<?= $page_anggota - 1 ?>&page_available=<?= $page_available ?><?= $is_super_admin ? '&tenant_id=' . urlencode($selected_tenant) : '' ?>"><i class="bi bi-chevron-left"></i></a></li>
                                            <?php endif; ?>
                                            <?php for ($p = max(1, $page_anggota - 2); $p <= min($total_pages_anggota, $page_anggota + 2); $p++): ?>
                                            <li class="page-item <?= $p === $page_anggota ? 'active' : '' ?>"><a class="page-link rounded-2" href="?tab=anggota&ekskul_id=<?= urlencode($selected_ekskul_id) ?>&semester=<?= urlencode($selected_semester) ?>&page_anggota=<?= $p ?>&page_available=<?= $page_available ?><?= $is_super_admin ? '&tenant_id=' . urlencode($selected_tenant) : '' ?>"><?= $p ?></a></li>
                                            <?php endfor; ?>
                                            <?php if ($page_anggota < $total_pages_anggota): ?>
                                            <li class="page-item"><a class="page-link rounded-2" href="?tab=anggota&ekskul_id=<?= urlencode($selected_ekskul_id) ?>&semester=<?= urlencode($selected_semester) ?>&page_anggota=<?= $page_anggota + 1 ?>&page_available=<?= $page_available ?><?= $is_super_admin ? '&tenant_id=' . urlencode($selected_tenant) : '' ?>"><i class="bi bi-chevron-right"></i></a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Add Members -->
                    <div class="col-lg-5 mb-4">
                        <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <h6 class="m-0 font-weight-bold text-success">
                                    <i class="bi bi-person-plus-fill me-1"></i> Tambah Anggota Baru
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                
                                <!-- Filter Kelas -->
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">FILTER BERDASARKAN KELAS</label>
                                    <select id="filter_kelas_available" class="form-select form-select-sm" onchange="filterKelasAvailable(this.value)">
                                        <option value="">-- Semua Kelas --</option>
                                        <?php foreach($kelas_list as $kelas): ?>
                                            <option value="<?= htmlspecialchars($kelas['id']) ?>" <?= $selected_kelas_id == $kelas['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <form action="/SINTA-SaaS/api/v1/ekskul/anggota/tambah<?= $is_super_admin ? '?tenant_id=' . urlencode($selected_tenant) : '' ?>" method="POST" id="form_tambah_anggota">
                                    <input type="hidden" name="ekskul_id" value="<?= htmlspecialchars($selected_ekskul_id) ?>">
                                    <input type="hidden" name="tahun_ajaran_id" value="<?= htmlspecialchars($active_ta['id'] ?? '') ?>">
                                    <input type="hidden" name="semester" value="<?= htmlspecialchars($selected_semester) ?>">

                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">PILIH SISWA (<?= $total_available ?> Tersedia)</label>
                                        
                                        <!-- Header Actions -->
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <button type="button" class="btn btn-link p-0 text-decoration-none" style="font-size: 0.8rem;" onclick="toggleSelectAll(true)">Pilih Semua</button>
                                            <button type="button" class="btn btn-link p-0 text-decoration-none text-muted" style="font-size: 0.8rem;" onclick="toggleSelectAll(false)">Bersihkan</button>
                                        </div>

                                        <div class="border rounded p-2 bg-light" style="max-height: 320px; overflow-y: auto;">
                                            <?php if(empty($available_students)): ?>
                                                <div class="text-center py-4 text-muted">
                                                    Tidak ada siswa aktif yang tersedia.
                                                </div>
                                            <?php else: ?>
                                                <?php foreach($available_students as $student): ?>
                                                    <div class="form-check py-1 border-bottom border-light-subtle">
                                                        <input class="form-check-input student-checkbox" type="checkbox" name="siswa_ids[]" value="<?= htmlspecialchars($student['siswa_id']) ?>" id="chk_siswa_<?= htmlspecialchars($student['siswa_id']) ?>" <?= $kunci_anggota ? 'disabled' : '' ?>>
                                                        <label class="form-check-label w-100 cursor-pointer" for="chk_siswa_<?= htmlspecialchars($student['siswa_id']) ?>">
                                                            <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($student['nama_lengkap']) ?></div>
                                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                                NISN: <?= htmlspecialchars($student['nisn']) ?> | Kelas: <?= htmlspecialchars($student['nama_kelas'] ?? 'Tanpa Kelas') ?>
                                                            </div>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($total_pages_available > 1): ?>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">Hal. <?= $page_available ?>/<?= $total_pages_available ?></small>
                                            <div class="d-flex gap-1">
                                                <?php if ($page_available > 1): ?>
                                                <a href="?tab=anggota&ekskul_id=<?= urlencode($selected_ekskul_id) ?>&semester=<?= urlencode($selected_semester) ?>&page_anggota=<?= $page_anggota ?>&page_available=<?= $page_available - 1 ?>&kelas_id=<?= urlencode($selected_kelas_id) ?><?= $is_super_admin ? '&tenant_id=' . urlencode($selected_tenant) : '' ?>" class="btn btn-outline-secondary btn-sm py-0 px-2"><i class="bi bi-chevron-left"></i></a>
                                                <?php endif; ?>
                                                <?php if ($page_available < $total_pages_available): ?>
                                                <a href="?tab=anggota&ekskul_id=<?= urlencode($selected_ekskul_id) ?>&semester=<?= urlencode($selected_semester) ?>&page_anggota=<?= $page_anggota ?>&page_available=<?= $page_available + 1 ?>&kelas_id=<?= urlencode($selected_kelas_id) ?><?= $is_super_admin ? '&tenant_id=' . urlencode($selected_tenant) : '' ?>" class="btn btn-outline-secondary btn-sm py-0 px-2"><i class="bi bi-chevron-right"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
 
                                    <button type="submit" class="btn btn-success w-100 py-2 rounded-3" <?= empty($available_students) || $kunci_anggota ? 'disabled' : '' ?>>
                                        <i class="bi bi-check-circle me-1"></i> Tambahkan Anggota Terpilih
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>

                </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Jurnal Kegiatan -->
        <?php if ($show_all_tabs): ?>
        <div class="tab-pane fade <?= $active_tab === 'jurnal' ? 'show active' : '' ?>" id="jurnal" role="tabpanel" aria-labelledby="jurnal-tab">
            <div class="card shadow-sm border-0">
                <div class="card-body py-5 text-center">
                    <i class="bi bi-tools text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5>Fitur Jurnal Kegiatan Sedang Dibangun</h5>
                    <p class="text-muted">Tempat pembina mencatat jadwal rutin and log kegiatan harian ekskul.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tab: Penilaian & Presensi -->
        <div class="tab-pane fade <?= $active_tab === 'nilai' ? 'show active' : '' ?>" id="nilai" role="tabpanel" aria-labelledby="nilai-tab">
            
            <!-- Filter & Selection Section -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-muted small">PILIH EKSTRAKURIKULER</label>
                            <select id="selected_ekskul_selector_nilai" class="form-select border-2 border-primary-subtle" onchange="changeEkskulFilterNilai(this.value)">
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php foreach($master_ekskul as $ekskul): ?>
                                    <?php if (($ekskul['status'] ?? 'active') === 'active'): ?>
                                        <option value="<?= htmlspecialchars($ekskul['id']) ?>" <?= $selected_ekskul_id === $ekskul['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ekskul['nama_ekskul']) ?> (<?= htmlspecialchars($ekskul['kategori']) ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-muted small">PILIH TAHUN AJARAN</label>
                            <select id="selected_ta_selector_nilai" class="form-select border-2 border-primary-subtle" onchange="changeTaFilterNilai(this.value)">
                                <?php foreach($all_tahun_ajaran as $ta): ?>
                                    <option value="<?= htmlspecialchars($ta['id']) ?>" <?= $selected_ta_id === $ta['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ta['tahun_ajaran']) ?> <?= $ta['is_active'] == 1 ? '(Aktif)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label class="form-label fw-bold text-muted small">PILIH SEMESTER</label>
                            <select id="selected_semester_selector_nilai" class="form-select border-2 border-primary-subtle" onchange="changeSemesterFilterNilai(this.value)">
                                <option value="Ganjil" <?= $selected_semester === 'Ganjil' ? 'selected' : '' ?>>Semester Ganjil</option>
                                <option value="Genap" <?= $selected_semester === 'Genap' ? 'selected' : '' ?>>Semester Genap</option>
                            </select>
                        </div>
                    </div>
                    <?php if (isset($is_historical) && $is_historical): ?>
                    <div class="alert alert-warning mt-3 mb-0 py-2 d-flex align-items-center">
                        <i class="bi bi-clock-history fs-5 me-2"></i>
                        <span>Anda sedang melihat data historis untuk Tahun Ajaran <strong><?= htmlspecialchars($active_ta['tahun_ajaran'] ?? '') ?></strong> Semester <strong><?= htmlspecialchars($selected_semester) ?></strong>.</span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($selected_ekskul_id)): ?>
                    <hr class="my-3 border-light">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center">
                            <?php if ($kunci_nilai): ?>
                                <span class="badge bg-danger px-3 py-2 rounded-3 fs-7">
                                    <i class="bi bi-lock-fill me-1"></i> Penilaian Terkunci
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success px-3 py-2 rounded-3 fs-7">
                                    <i class="bi bi-unlock-fill me-1"></i> Penilaian Terbuka
                                </span>
                            <?php endif; ?>
                            <span class="text-muted small ms-2">
                                <?= $kunci_nilai ? 'Nilai, presensi, dan impor berkas telah dikunci (Sudah di-input di Rapot).' : 'Nilai dan presensi dapat diinput atau diimpor secara bebas.' ?>
                            </span>
                        </div>
                        <?php if ($can_lock): ?>
                            <form action="/SINTA-SaaS/api/v1/ekskul/kunci/nilai" method="POST" class="mb-0">
                                <input type="hidden" name="ekskul_id" value="<?= htmlspecialchars($selected_ekskul_id) ?>">
                                <input type="hidden" name="tahun_ajaran_id" value="<?= htmlspecialchars($active_ta['id'] ?? '') ?>">
                                <input type="hidden" name="semester" value="<?= htmlspecialchars($selected_semester) ?>">
                                <?php if($is_super_admin): ?>
                                    <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($selected_tenant) ?>">
                                <?php endif; ?>
                                <input type="hidden" name="lock" value="<?= $kunci_nilai ? 0 : 1 ?>">
                                <button type="submit" class="btn btn-sm <?= $kunci_nilai ? 'btn-outline-success' : 'btn-outline-danger' ?> rounded-3">
                                    <i class="bi <?= $kunci_nilai ? 'bi-unlock-fill' : 'bi-lock-fill' ?> me-1"></i>
                                    <?= $kunci_nilai ? 'Buka Kunci Penilaian' : 'Kunci Penilaian' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(empty($selected_ekskul_id)): ?>
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body py-5 text-center">
                        <i class="bi bi-search text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5>Pilih Ekstrakurikuler Terlebih Dahulu</h5>
                        <p class="text-muted">Gunakan dropdown di atas untuk memilih ekstrakurikuler yang ingin dinilai anggotanya.</p>
                    </div>
                </div>
            <?php else: ?>
                <form action="/SINTA-SaaS/api/v1/ekskul/nilai/simpan<?= $is_super_admin ? '?tenant_id=' . urlencode($selected_tenant) : '' ?>" method="POST">
                    <input type="hidden" name="ekskul_id" value="<?= htmlspecialchars($selected_ekskul_id) ?>">
                    <input type="hidden" name="tahun_ajaran_id" value="<?= htmlspecialchars($active_ta['id'] ?? '') ?>">
                    <input type="hidden" name="semester" value="<?= htmlspecialchars($selected_semester) ?>">
                    <?php if($is_super_admin): ?>
                        <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($selected_tenant) ?>">
                    <?php endif; ?>

                    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
                        <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-pen-fill me-1"></i> Penilaian & Presensi (<?= count($nilai_list) ?> Siswa)
                            </h6>
                            <div class="d-flex gap-2">
                                <a href="/SINTA-SaaS/api/v1/ekskul/nilai/export?ekskul_id=<?= urlencode($selected_ekskul_id) ?>&semester=<?= urlencode($selected_semester) ?>&tahun_ajaran_id=<?= urlencode($active_ta['id'] ?? '') ?><?= $is_super_admin ? '&tenant_id=' . urlencode($selected_tenant) : '' ?>" class="btn btn-sm btn-outline-primary <?= empty($nilai_list) ? 'disabled' : '' ?>">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importGradesModal" <?= $kunci_nilai ? 'disabled' : '' ?>>
                                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Impor Excel
                                </button>
                                <button type="submit" class="btn btn-sm btn-success" <?= empty($nilai_list) || $kunci_nilai ? 'disabled' : '' ?>>
                                    <i class="bi bi-save me-1"></i> Simpan Semua Penilaian
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4" style="min-width: 200px;">Nama Siswa</th>
                                            <th style="min-width: 100px;">Kelas</th>
                                            <th style="width: 100px;">Poin (1-100)</th>
                                            <th style="width: 120px;">Predikat</th>
                                            <th style="min-width: 250px;">Keterangan / Deskripsi Penilaian</th>
                                            <th class="text-center" style="width: 70px;">Sakit</th>
                                            <th class="text-center" style="width: 70px;">Izin</th>
                                            <th class="text-center" style="width: 70px;">Alfa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($nilai_list)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">
                                                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                                                    Belum ada anggota terdaftar di ekskul ini untuk semester ini.
                                                    <br>
                                                    <a href="?tab=anggota&ekskul_id=<?= urlencode($selected_ekskul_id) ?>&semester=<?= urlencode($selected_semester) ?><?= $is_super_admin ? '&tenant_id=' . urlencode($selected_tenant) : '' ?>" class="btn btn-sm btn-primary mt-3">
                                                        <i class="bi bi-plus-circle me-1"></i> Kelola Anggota Pertama
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($nilai_list as $row): ?>
                                                <?php $s_id = htmlspecialchars($row['siswa_id']); ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                                        <small class="text-muted">NISN: <?= htmlspecialchars($row['nisn']) ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($row['nama_kelas'] ?? 'Tanpa Kelas') ?></span>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="grades[<?= $s_id ?>][poin]" class="form-control form-control-sm text-center" min="0" max="100" placeholder="0-100" value="<?= $row['poin'] !== null ? (int)$row['poin'] : '' ?>" <?= $kunci_nilai ? 'disabled' : '' ?>>
                                                    </td>
                                                    <td>
                                                        <select name="grades[<?= $s_id ?>][nilai]" class="form-select form-select-sm" <?= $kunci_nilai ? 'disabled' : '' ?>>
                                                            <option value="A" <?= ($row['nilai'] ?? 'B') === 'A' ? 'selected' : '' ?>>A (Sangat Baik)</option>
                                                            <option value="B" <?= ($row['nilai'] ?? 'B') === 'B' ? 'selected' : '' ?>>B (Baik)</option>
                                                            <option value="C" <?= ($row['nilai'] ?? 'B') === 'C' ? 'selected' : '' ?>>C (Cukup)</option>
                                                            <option value="D" <?= ($row['nilai'] ?? 'B') === 'D' ? 'selected' : '' ?>>D (Kurang)</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="grades[<?= $s_id ?>][deskripsi]" class="form-control form-control-sm" placeholder="Contoh: Sangat baik dan aktif mengikuti kegiatan latihan rutin..." value="<?= htmlspecialchars($row['deskripsi'] ?? '') ?>" <?= $kunci_nilai ? 'disabled' : '' ?>>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="grades[<?= $s_id ?>][sakit]" class="form-control form-control-sm text-center" min="0" value="<?= (int)($row['sakit'] ?? 0) ?>" <?= $kunci_nilai ? 'disabled' : '' ?>>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="grades[<?= $s_id ?>][izin]" class="form-control form-control-sm text-center" min="0" value="<?= (int)($row['izin'] ?? 0) ?>" <?= $kunci_nilai ? 'disabled' : '' ?>>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="grades[<?= $s_id ?>][alfa]" class="form-control form-control-sm text-center" min="0" value="<?= (int)($row['alfa'] ?? 0) ?>" <?= $kunci_nilai ? 'disabled' : '' ?>>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Tambah Ekskul -->
<div class="modal fade" id="tambahEkskulModal" tabindex="-1" aria-labelledby="tambahEkskulModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form action="/SINTA-SaaS/api/v1/ekskul/tambah" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="tambahEkskulModalLabel">Tambah Ekstrakurikuler</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if($is_super_admin): ?>
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($selected_tenant ?? '') ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Ekskul</label>
                <input type="text" name="nama_ekskul" class="form-control" required placeholder="Contoh: Pramuka">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Kategori</label>
                <select name="kategori" class="form-select" required>
                    <option value="Pilihan">Pilihan</option>
                    <option value="Wajib">Wajib</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Guru Pembina (Opsional)</label>
                <select name="pembina_id" class="form-select">
                    <option value="">-- Pilih Pembina --</option>
                    <?php foreach($pembina_list as $p): ?>
                        <?php if (($p['status'] ?? 'active') !== 'inactive'): ?>
                            <option value="<?= htmlspecialchars($p['id']) ?>"><?= htmlspecialchars($p['nama_lengkap']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Tambah Pembina -->
<div class="modal fade" id="tambahPembinaModal" tabindex="-1" aria-labelledby="tambahPembinaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form action="/SINTA-SaaS/api/v1/ekskul/pembina/tambah" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="tambahPembinaModalLabel">Tambah Guru Pembina</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if($is_super_admin): ?>
                <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($selected_tenant ?? '') ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" required placeholder="Contoh: Budi Santoso, S.Pd">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Email (Untuk Login)</label>
                <input type="email" name="email" class="form-control" required placeholder="budi.s@sekolah.id">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">No. Telepon <span class="text-muted fw-normal">(Opsional)</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="tel" name="no_telp" class="form-control" autocomplete="tel" placeholder="Contoh: 08123456789" maxlength="20">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Password Login</label>
                <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter" autocomplete="new-password" value="">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Ekskul -->
<div class="modal fade" id="editEkskulModal" tabindex="-1" aria-labelledby="editEkskulModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form action="/SINTA-SaaS/api/v1/ekskul/edit" method="POST">
          <input type="hidden" name="id" id="edit_ekskul_id">
          <?php if($is_super_admin): ?>
              <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($selected_tenant ?? '') ?>">
          <?php endif; ?>
          
          <div class="modal-header">
            <h5 class="modal-title" id="editEkskulModalLabel">Edit Ekstrakurikuler</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Ekskul</label>
                <input type="text" name="nama_ekskul" id="edit_nama_ekskul" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Kategori</label>
                <select name="kategori" id="edit_kategori" class="form-select" required>
                    <option value="Pilihan">Pilihan</option>
                    <option value="Wajib">Wajib</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Guru Pembina (Opsional)</label>
                <select name="pembina_id" id="edit_pembina_id" class="form-select">
                    <option value="">-- Pilih Pembina --</option>
                    <?php foreach($pembina_list as $p): ?>
                        <?php if (($p['status'] ?? 'active') !== 'inactive'): ?>
                            <option value="<?= htmlspecialchars($p['id']) ?>"><?= htmlspecialchars($p['nama_lengkap']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function populateEditEkskul(button) {
    document.getElementById('edit_ekskul_id').value = button.getAttribute('data-id');
    document.getElementById('edit_nama_ekskul').value = button.getAttribute('data-nama');
    document.getElementById('edit_kategori').value = button.getAttribute('data-kategori');
    document.getElementById('edit_pembina_id').value = button.getAttribute('data-pembina');
}

function populateEditPembina(button) {
    document.getElementById('edit_pembina_id_modal').value = button.getAttribute('data-id');
    document.getElementById('edit_pembina_nama').value     = button.getAttribute('data-nama');
    document.getElementById('edit_pembina_email').value    = button.getAttribute('data-email');
    document.getElementById('edit_pembina_notelp').value   = button.getAttribute('data-notelp') || '';
    document.getElementById('edit_pembina_password').value = '';
}

function changeEkskulFilter(ekskulId) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', 'anggota');
    if (ekskulId) {
        urlParams.set('ekskul_id', ekskulId);
    } else {
        urlParams.delete('ekskul_id');
    }
    urlParams.delete('kelas_id'); // Reset kelas filter when changing ekskul
    window.location.search = urlParams.toString();
}

function changeSemesterFilter(semester) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', 'anggota');
    urlParams.set('semester', semester);
    urlParams.delete('kelas_id'); // Reset kelas filter when changing semester
    window.location.search = urlParams.toString();
}

function changeTaFilter(taId) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', 'anggota');
    urlParams.set('tahun_ajaran_id', taId);
    window.location.search = urlParams.toString();
}

function changeEkskulFilterNilai(ekskulId) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', 'nilai');
    if (ekskulId) {
        urlParams.set('ekskul_id', ekskulId);
    } else {
        urlParams.delete('ekskul_id');
    }
    urlParams.delete('kelas_id');
    window.location.search = urlParams.toString();
}

function changeSemesterFilterNilai(semester) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', 'nilai');
    urlParams.set('semester', semester);
    urlParams.delete('kelas_id');
    window.location.search = urlParams.toString();
}

function changeTaFilterNilai(taId) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', 'nilai');
    urlParams.set('tahun_ajaran_id', taId);
    window.location.search = urlParams.toString();
}

function filterKelasAvailable(kelasId) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('tab', 'anggota');
    if (kelasId) {
        urlParams.set('kelas_id', kelasId);
    } else {
        urlParams.delete('kelas_id');
    }
    window.location.search = urlParams.toString();
}

function toggleSelectAll(selectAll) {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(chk => chk.checked = selectAll);
}

// Sync URL search params with active tab clicks
document.addEventListener('DOMContentLoaded', function () {
    const tabElList = document.querySelectorAll('#ekskulTab button[data-bs-toggle="tab"]');
    tabElList.forEach(button => {
        button.addEventListener('shown.bs.tab', function (event) {
            const tabId = event.target.id.replace('-tab', '');
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('tab', tabId);
            window.history.replaceState({}, '', window.location.pathname + '?' + urlParams.toString());
        });
    });

    // Horizontal scroll gradient indicator logic
    const navUl      = document.getElementById('ekskulTab');
    const wrapper    = document.getElementById('navScrollWrapper');

    if (navUl && wrapper) {
        function updateScrollIndicators() {
            const { scrollLeft, scrollWidth, clientWidth } = navUl;
            wrapper.classList.toggle('at-start', scrollLeft <= 4);
            wrapper.classList.toggle('at-end',   scrollLeft + clientWidth >= scrollWidth - 4);
        }

        navUl.addEventListener('scroll', updateScrollIndicators, { passive: true });
        // Run once on load to set initial state
        updateScrollIndicators();

        // Scroll the active tab into view smoothly
        const activeBtn = navUl.querySelector('.nav-link.active');
        if (activeBtn) {
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }
});
</script>


<!-- Modal Edit Pembina -->
<div class="modal fade" id="editPembinaModal" tabindex="-1" aria-labelledby="editPembinaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form action="/SINTA-SaaS/api/v1/ekskul/pembina/edit" method="POST">
          <input type="hidden" name="id" id="edit_pembina_id_modal">
          <?php if($is_super_admin): ?>
              <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($selected_tenant ?? '') ?>">
          <?php endif; ?>
          <div class="modal-header">
            <h5 class="modal-title" id="editPembinaModalLabel">Edit Guru Pembina</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="edit_pembina_nama" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Email (Untuk Login)</label>
                <input type="email" name="email" id="edit_pembina_email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">No. Telepon <span class="text-muted fw-normal">(Opsional)</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="tel" name="no_telp" id="edit_pembina_notelp" class="form-control" autocomplete="tel" placeholder="08123456789" maxlength="20">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Password Login (Opsional)</label>
                <input type="password" name="password" id="edit_pembina_password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password" autocomplete="new-password" value="">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Import Nilai -->
<div class="modal fade" id="importGradesModal" tabindex="-1" aria-labelledby="importGradesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <form action="/SINTA-SaaS/api/v1/ekskul/nilai/import" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="ekskul_id" value="<?= htmlspecialchars($selected_ekskul_id) ?>">
          <input type="hidden" name="tahun_ajaran_id" value="<?= htmlspecialchars($active_ta['id'] ?? '') ?>">
          <input type="hidden" name="semester" value="<?= htmlspecialchars($selected_semester) ?>">
          <?php if($is_super_admin): ?>
              <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($selected_tenant) ?>">
          <?php endif; ?>
          <div class="modal-header">
            <h5 class="modal-title" id="importGradesModalLabel">Impor Penilaian Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info py-2 rounded-3 small">
                <i class="bi bi-info-circle-fill me-1"></i>
                Harap gunakan format berkas `.xlsx` hasil ekspor dari halaman ini. Pencocokan data siswa menggunakan nomor **NISN**.
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-muted small">PILIH BERKAS EXCEL (.xlsx)</label>
                <input type="file" name="file" class="form-control" accept=".xlsx" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Mulai Impor</button>
          </div>
      </form>
    </div>
  </div>
</div>
