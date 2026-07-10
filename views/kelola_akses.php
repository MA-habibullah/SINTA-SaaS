<?php
/**
 * View: Kelola Akses (Child View)
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */
$isSuperAdmin = ($data['user_role'] ?? '') === 'super_admin';
$tenants      = $data['tenants'] ?? [];
?>
<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <div>
        <h2 class="fw-bold text-dark mb-1"><?= htmlspecialchars($data['title'] ?? 'Kelola Akses Menu') ?></h2>
        <p class="text-muted fs-7">Atur menu sidebar mana saja yang dapat dilihat oleh masing-masing peran secara real-time.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="/SINTA-SaaS/dashboard" class="btn btn-outline-secondary btn-sm d-flex align-items-center rounded-3 px-3 py-2 fs-7">
            <i class="bi bi-arrow-left me-2"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<!-- Alert Feedback Status (Success/Error) -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success border-0 rounded-3 alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger border-0 rounded-3 alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($isSuperAdmin && !empty($tenants)): ?>
<!-- Dropdown Pemilih Sekolah (HANYA Super Admin) -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="row align-items-end g-3">
        <div class="col-12 col-md-7">
            <label for="tenantSelectorAkses" class="form-label fw-bold text-dark mb-2">
                <i class="bi bi-buildings text-primary me-2"></i>Target Pengaturan Akses
            </label>
            <div class="d-flex gap-2">
                <select id="tenantSelectorAkses" class="form-select rounded-3 py-2">
                    <option value="">— Global Default (Berlaku untuk semua sekolah yang belum dikustomisasi) —</option>
                    <?php foreach ($tenants as $tenant): ?>
                        <option value="<?= htmlspecialchars($tenant['id']) ?>">
                            <?= htmlspecialchars($tenant['nama_sekolah']) ?>
                            <?= !empty($tenant['npsn']) ? '(NPSN: ' . htmlspecialchars($tenant['npsn']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="btnTerapkanFilterAkses" class="btn btn-primary rounded-3 text-nowrap px-3">
                    <i class="bi bi-funnel me-1"></i> Terapkan Filter
                </button>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div id="tenantBadge" class="d-none">
                <span class="badge bg-primary-subtle px-3 py-2 rounded-pill fs-7" style="color: #084298;">
                    <i class="bi bi-shield-fill-check me-1"></i>
                    <span id="tenantBadgeText"></span>
                </span>
            </div>
            <div id="loadingBadge" class="d-none">
                <span class="badge bg-secondary-subtle px-3 py-2 rounded-pill fs-7 text-muted">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span> Memuat data akses...
                </span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Access Control Matrix Table (Card Wrap) -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="alert alert-info border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-3">
        <i class="bi bi-shield-fill-exclamation text-info fs-3"></i>
        <div class="fs-7">
            <?php if ($isSuperAdmin): ?>
                <strong>Petunjuk Super Admin:</strong> Pilih sekolah di atas untuk mengatur akses per-sekolah, atau biarkan "<em>Global Default</em>" untuk mengatur akses yang berlaku bagi semua sekolah yang belum memiliki kustomisasi. Centang kotak untuk mengizinkan akses.
            <?php else: ?>
                <strong>Petunjuk:</strong> Tandai (centang) kotak untuk mengizinkan peran tertentu mengakses menu sidebar tersebut. Menu bertipe induk (Parent) wajib dicentang agar menu anaknya dapat tampil.
            <?php endif; ?>
        </div>
    </div>

    <form action="/SINTA-SaaS/konfigurasi/akses/simpan" method="POST" id="aksesForm">
        <?php if ($isSuperAdmin): ?>
            <!-- Field tersembunyi: tenant target (diisi oleh JS saat dropdown berubah) -->
            <input type="hidden" name="target_tenant_id" id="targetTenantId" value="">
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-4" id="aksesTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Menu / Fitur Sidebar</th>
                        <th>Path / URL</th>
                        <th style="width: 100px;">Ikon</th>
                        <!-- Render headers for each role -->
                        <?php foreach ($data['roles'] as $role): ?>
                            <th class="text-center" style="width: 140px;">
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1 text-uppercase" style="font-size: 0.725rem;">
                                    <?= htmlspecialchars(str_replace('_', ' ', $role['nama_role'])) ?>
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                    <?php
                    // Bangun struktur tree: Parent → Children
                    // agar urutan tampilan identik dengan halaman tenant_menus (gambar 2)
                    $allMenus  = array_values($data['menus']);
                    $parents   = array_filter($allMenus, fn($m) => $m['parent_id'] === null);
                    $childMap  = [];
                    foreach ($allMenus as $m) {
                        if ($m['parent_id'] !== null) {
                            $childMap[$m['parent_id']][] = $m;
                        }
                    }

                    // Fungsi helper render satu baris
                    $renderRow = function(array $menu, bool $isChild) use ($data, &$no) {
                        $rowStyle = $isChild
                            ? 'background-color: #fafbfc;'
                            : 'font-weight: 600; background-color: #f8fafc;';
                        ?>
                        <tr style="<?= $rowStyle ?>">
                            <td class="text-muted"><?= $no++ ?></td>
                            <td class="<?= $isChild ? 'ps-4' : 'ps-3' ?>">
                                <?php if ($isChild): ?>
                                    <span class="text-muted ms-3 me-1">└──</span>
                                    <i class="<?= htmlspecialchars($menu['icon'] ?? 'bi bi-circle') ?> me-1" style="font-size:0.8rem; opacity:0.7;"></i>
                                    <span class="fw-normal text-muted fs-7"><?= htmlspecialchars($menu['nama_menu']) ?></span>
                                <?php else: ?>
                                    <span class="text-dark fw-bold">
                                        <i class="<?= htmlspecialchars($menu['icon'] ?? 'bi bi-folder-fill') ?> text-primary me-2"></i>
                                        <?= htmlspecialchars($menu['nama_menu']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="font-monospace fs-8 text-muted">
                                <?= $menu['url'] && $menu['url'] !== '#' ? htmlspecialchars($menu['url']) : '<span class="opacity-50">-</span>' ?>
                            </td>
                            <td>
                                <?php if (!empty($menu['icon'])): ?>
                                    <span class="badge bg-light text-dark border">
                                        <i class="<?= htmlspecialchars($menu['icon']) ?> me-1 text-primary"></i><?= htmlspecialchars($menu['icon']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="opacity-50">-</span>
                                <?php endif; ?>
                            </td>
                            <!-- Render checkboxes untuk setiap role -->
                            <?php foreach ($data['roles'] as $role):
                                $key     = $role['id'] . '-' . $menu['id'];
                                $checked = isset($data['access_map'][$key]) ? 'checked' : '';
                            ?>
                                <td class="text-center">
                                    <div class="form-check d-inline-block">
                                        <input class="form-check-input rbac-matrix-checkbox border-secondary"
                                               type="checkbox"
                                               name="access[<?= $role['id'] ?>][]"
                                               value="<?= $menu['id'] ?>"
                                               data-role="<?= $role['id'] ?>"
                                               data-menu="<?= $menu['id'] ?>"
                                               data-parent="<?= $menu['parent_id'] ?: '' ?>"
                                               <?= $checked ?>
                                               style="cursor: pointer; width: 1.15rem; height: 1.15rem;">
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php
                    };

                    $no = 1;
                    foreach ($parents as $parent):
                        $renderRow($parent, false);
                        // Render children langsung di bawah parentnya
                        foreach ($childMap[$parent['id']] ?? [] as $child):
                            $renderRow($child, true);
                        endforeach;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end gap-2 border-top pt-3">
            <a href="/SINTA-SaaS/dashboard" class="btn btn-light rounded-3 px-4 py-2 fs-7">Batal</a>
            <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 fs-7 shadow-sm">
                <i class="bi bi-save me-2"></i> Simpan Matriks Hak Akses
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('turbo:load', function () {
    // =====================================================================
    // BAGIAN 1: Cascade logic — uncheck parent otomatis uncheck children
    // (Logika lama, dipertahankan)
    // =====================================================================
    const checkboxes = document.querySelectorAll('.rbac-matrix-checkbox');

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            const roleId    = this.dataset.role;
            const menuId    = this.dataset.menu;
            const isChecked = this.checked;

            if (!isChecked) {
                const children = document.querySelectorAll(
                    `.rbac-matrix-checkbox[data-role="${roleId}"][data-parent="${menuId}"]`
                );
                children.forEach(child => {
                    if (child.checked) {
                        child.checked = false;
                        child.dispatchEvent(new Event('change'));
                    }
                });
            }
        });
    });

    // =====================================================================
    // BAGIAN 2: Dropdown pemilih tenant (HANYA Super Admin)
    // =====================================================================
    const tenantSelector = document.getElementById('tenantSelectorAkses');
    const targetTenantInput = document.getElementById('targetTenantId');
    const tenantBadge   = document.getElementById('tenantBadge');
    const tenantBadgeText = document.getElementById('tenantBadgeText');
    const loadingBadge  = document.getElementById('loadingBadge');
    const btnTerapkanFilter = document.getElementById('btnTerapkanFilterAkses');

    if (!tenantSelector || !btnTerapkanFilter) return; // bukan super admin, stop

    btnTerapkanFilter.addEventListener('click', function () {
        const selectedTenantId = tenantSelector.value;
        const selectedText     = tenantSelector.options[tenantSelector.selectedIndex].text;

        // Simpan tenant_id ke hidden input
        targetTenantInput.value = selectedTenantId;

        if (!selectedTenantId) {
            // Kembali ke global default — reset checkbox ke nilai awal dari PHP
            tenantBadge.classList.add('d-none');
            loadingBadge.classList.add('d-none');
            resetCheckboxesToDefault();
            return;
        }

        // Tampilkan loading
        tenantBadge.classList.add('d-none');
        loadingBadge.classList.remove('d-none');

        // Fetch access map dari server
        axios.get('/SINTA-SaaS/api/v1/akses/fetch', { params: { tenant_id: selectedTenantId } })
            .then(response => {
                loadingBadge.classList.add('d-none');
                if (response.data.success) {
                    const accessMap = response.data.access_map;
                    const isCustom  = response.data.is_custom;

                    // Update semua checkbox
                    document.querySelectorAll('.rbac-matrix-checkbox').forEach(cb => {
                        const key     = cb.dataset.role + '-' + cb.dataset.menu;
                        cb.checked = !!accessMap[key];
                    });

                    // Update badge
                    tenantBadgeText.textContent = isCustom
                        ? 'Mengedit: ' + selectedText
                        : 'Mengedit: ' + selectedText + ' (menggunakan konfigurasi global)';
                    tenantBadge.classList.remove('d-none');
                }
            })
            .catch(err => {
                loadingBadge.classList.add('d-none');
                console.error(err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Gagal Memuat', text: 'Tidak dapat mengambil data akses untuk sekolah ini.' });
                }
            });
    });

    // Simpan nilai default checkbox dari PHP (untuk reset saat pilih "Global Default")
    const defaultValues = {};
    document.querySelectorAll('.rbac-matrix-checkbox').forEach(cb => {
        defaultValues[cb.dataset.role + '-' + cb.dataset.menu] = cb.checked;
    });

    function resetCheckboxesToDefault() {
        document.querySelectorAll('.rbac-matrix-checkbox').forEach(cb => {
            cb.checked = !!defaultValues[cb.dataset.role + '-' + cb.dataset.menu];
        });
    }
});
</script>
