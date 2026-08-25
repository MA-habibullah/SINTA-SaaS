<?php
/**
 * View: Kelola Akses (Child View)
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 * Zero Data Leakage: Daftar tenant dimuat async via Axios — tidak tercetak di View Source.
 */
$isSuperAdmin = ($data['user_role'] ?? '') === 'super_admin';
?>
<!-- 1. Row Header & Action Toolbar -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-blue-600 text-white rounded-2xl d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 48px; height: 48px;">
            <i class="bi bi-shield-lock-fill fs-4"></i>
        </div>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h3 class="fw-bold text-slate-900 fs-4 mb-0"><?= htmlspecialchars($data['title'] ?? 'Manajemen User & Hak Akses (RBAC)') ?></h3>
                <?php if ($isSuperAdmin): ?>
                    <span class="badge bg-slate-100 text-slate-700 border border-slate-200 rounded-pill px-2.5 py-1 fs-9 font-bold">
                        <i class="bi bi-shield-check text-blue-600 me-1"></i>Super Admin
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-slate-500 fs-8 mb-0 mt-0.5">Atur menu sidebar mana saja yang dapat dilihat oleh masing-masing peran secara real-time.</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= $this->getBaseUrl() ?>/dashboard" class="btn btn-sm btn-light border border-slate-200 text-slate-700 rounded-xl px-3.5 py-2 fs-8 font-semibold shadow-2xs hover-lift d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-arrow-left"></i>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>
</div>

<!-- Alert Feedback Status (Success/Error) -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success border-0 rounded-2xl alert-dismissible fade show shadow-2xs mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger border-0 rounded-2xl alert-dismissible fade show shadow-2xs mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($isSuperAdmin): ?><!-- 2. Compact School Selector Banner (Khusus Super Admin Auto-Filter) -->
<div class="mb-4 p-3 px-md-4 rounded-2xl shadow-2xs border border-blue-100 bg-white">
    <div class="d-flex align-items-center flex-wrap gap-2.5">
        <div class="bg-blue-50 text-blue-600 p-2 rounded-xl d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
            <i class="bi bi-buildings fs-6"></i>
        </div>
        <div>
            <span class="fs-8 fw-bold text-slate-800 me-1">Target Pengaturan Akses:</span>
        </div>
        
        <div class="my-1 my-md-0" style="min-width: 220px; max-width: 300px;">
            <select id="tenantSelectorAkses" class="form-select form-select-sm bg-slate-50 border-slate-200 rounded-xl text-slate-800 fs-8 font-semibold shadow-2xs cursor-pointer focus:bg-white w-100" style="height: 38px;">
                <option value="">— Global Default (Semua Sekolah) —</option>
                <!-- Opsi tenant diisi secara asinkron oleh JavaScript di bawah (Zero Data Leakage) -->
            </select>
        </div>

        <!-- Badge Data Aktif Tepat di Samping Filter -->
        <div id="tenantBadge" class="d-none ms-md-1">
            <span class="badge bg-blue-50 text-blue-700 border border-blue-200 px-3 py-2 rounded-pill fs-8 font-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs" 
                  id="tenantBadgeContainer" 
                  style="max-width: 340px;" 
                  title="">
                <i class="bi bi-shield-fill-check text-blue-600 flex-shrink-0"></i>
                <span id="tenantBadgeText" class="text-truncate d-inline-block" style="max-width: 280px;"></span>
            </span>
        </div>
        <div id="loadingBadge" class="d-none ms-md-1">
            <span class="badge bg-slate-100 text-slate-600 border border-slate-200 px-3 py-2 rounded-pill fs-8 font-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs">
                <span class="spinner-border spinner-border-sm text-blue-600 me-1" role="status"></span>
                <span>Memuat data akses...</span>
            </span>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isSuperAdmin): ?>
<script>
// Zero Data Leakage: Isi dropdown tenant secara asinkron — data tidak tercetak di HTML awal
(function() {
    const select = document.getElementById('tenantSelectorAkses');
    if (!select) return;

    const baseUrl = '<?= $this->getBaseUrl() ?>';

    // Fetch daftar tenant dari API yang sudah terproteksi session
    axios.get(baseUrl + '/api/v1/super-admin/tenants')
        .then(function(res) {
            if (res.data && res.data.success && Array.isArray(res.data.data)) {
                res.data.data.forEach(function(t) {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.nama_sekolah + (t.npsn ? ' (NPSN: ' + t.npsn + ')' : '');
                    select.appendChild(opt);
                });
            }
        })
        .catch(function(err) {
            console.error('Gagal memuat daftar tenant:', err);
        });
})();
</script>
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

    <form action="<?= $this->getBaseUrl() ?>/konfigurasi/akses/simpan" method="POST" id="aksesForm">
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
                        <?php foreach (($data['roles'] ?? []) as $role): ?>
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
                    $allMenus  = array_values($data['menus'] ?? []);
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
                                               data-role="<?= htmlspecialchars((string)$role['id'], ENT_QUOTES, 'UTF-8') ?>"
                                               data-menu="<?= htmlspecialchars((string)$menu['id'], ENT_QUOTES, 'UTF-8') ?>"
                                               data-parent="<?= htmlspecialchars((string)($menu['parent_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
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
            <a href="<?= $this->getBaseUrl() ?>/dashboard" class="btn btn-light rounded-3 px-4 py-2 fs-7">Batal</a>
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
    // BAGIAN 2: Dropdown pemilih tenant (HANYA Super Admin - Auto-Filter)
    // =====================================================================
    const tenantSelector = document.getElementById('tenantSelectorAkses');
    const targetTenantInput = document.getElementById('targetTenantId');
    const tenantBadge   = document.getElementById('tenantBadge');
    const tenantBadgeText = document.getElementById('tenantBadgeText');
    const loadingBadge  = document.getElementById('loadingBadge');

    if (!tenantSelector) return; // bukan super admin, stop

    tenantSelector.addEventListener('change', function () {
        const selectedTenantId = tenantSelector.value;
        const selectedText     = tenantSelector.options[tenantSelector.selectedIndex].text;

        // Simpan tenant_id ke hidden input
        if (targetTenantInput) {
            targetTenantInput.value = selectedTenantId;
        }

        if (!selectedTenantId) {
            // Kembali ke global default — reset checkbox ke nilai awal dari PHP
            if (tenantBadge) tenantBadge.classList.add('d-none');
            if (loadingBadge) loadingBadge.classList.add('d-none');
            resetCheckboxesToDefault();
            return;
        }

        // Tampilkan loading
        if (tenantBadge) tenantBadge.classList.add('d-none');
        if (loadingBadge) loadingBadge.classList.remove('d-none');

        // Fetch access map dari server
        axios.get('<?= $this->getBaseUrl() ?>/api/v1/akses/fetch', { params: { tenant_id: selectedTenantId } })
            .then(response => {
                if (loadingBadge) loadingBadge.classList.add('d-none');
                if (response.data && response.data.success) {
                    const accessMap = response.data.access_map || {};
                    const isCustom  = !!response.data.is_custom;

                    // Update semua checkbox
                    document.querySelectorAll('.rbac-matrix-checkbox').forEach(cb => {
                        const key     = cb.dataset.role + '-' + cb.dataset.menu;
                        cb.checked = !!accessMap[key];
                    });

                    // Update badge dengan teks bersih dan tooltip lengkap
                    const container = document.getElementById('tenantBadgeContainer');
                    const cleanName = selectedText.replace(/^[—\-]\s*|\s*[—\-]$/g, '').trim();
                    const fullTooltip = isCustom 
                        ? 'Target: ' + cleanName + ' (Konfigurasi Kustom)' 
                        : 'Target: ' + cleanName + ' (Konfigurasi Global Default)';
                    
                    if (container) {
                        container.setAttribute('title', fullTooltip);
                    }

                    if (tenantBadgeText) {
                        tenantBadgeText.textContent = isCustom
                            ? cleanName + ' (Kustom)'
                            : cleanName + ' (Global)';
                    }
                    if (tenantBadge) tenantBadge.classList.remove('d-none');
                }
            })
            .catch(err => {
                if (loadingBadge) loadingBadge.classList.add('d-none');
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
