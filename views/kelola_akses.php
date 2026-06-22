<?php
/**
 * View: Kelola Akses (Child View)
 * Bagian ini dimuat secara dinamis oleh views/layout/master.php di area #main-content.
 */
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

<!-- Access Control Matrix Table (Card Wrap) -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="alert alert-info border-0 rounded-3 p-3 mb-4 d-flex align-items-center gap-3">
        <i class="bi bi-shield-fill-exclamation text-info fs-3"></i>
        <div class="fs-7">
            <strong>Petunjuk Super Admin:</strong> Tandai (centang) kotak untuk mengizinkan peran tertentu mengakses menu sidebar tersebut. Menu bertipe induk (Parent) wajib dicentang agar menu anaknya (Children) dapat tampil dengan benar di struktur menu sidebar target.
        </div>
    </div>

    <form action="/SINTA-SaaS/konfigurasi/akses/simpan" method="POST">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-4">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Menu (Sidebar)</th>
                        <th>Path / URL</th>
                        <th style="width: 100px;">Ikon</th>
                        <!-- Render headers for each role -->
                        <?php foreach ($data['roles'] as $role): ?>
                            <th class="text-center" style="width: 140px;">
                                <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5 text-uppercase" style="font-size: 0.725rem;">
                                    <?= htmlspecialchars(str_replace('_', ' ', $role['nama_role'])) ?>
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    foreach ($data['menus'] as $menu): 
                        $isChild = $menu['parent_id'] !== null;
                        $rowStyle = $isChild ? 'background-color: #fafbfc;' : 'font-weight: 600; background-color: #f8fafc;';
                    ?>
                        <tr style="<?= $rowStyle ?>">
                            <td class="text-muted"><?= $no++ ?></td>
                            <td class="<?= $isChild ? 'ps-4' : 'ps-3' ?>">
                                <?php if ($isChild): ?>
                                    <span class="text-muted ms-3 me-1">└──</span>
                                    <span class="fw-normal text-muted fs-7"><?= htmlspecialchars($menu['nama_menu']) ?></span>
                                <?php else: ?>
                                    <span class="text-dark fw-bold"><i class="<?= htmlspecialchars($menu['icon'] ?? 'bi bi-folder-fill') ?> text-primary me-2"></i> <?= htmlspecialchars($menu['nama_menu']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="font-monospace fs-8 text-muted">
                                <?= $menu['url'] && $menu['url'] !== '#' ? htmlspecialchars($menu['url']) : '<span class="text-slate-400 opacity-50">-</span>' ?>
                            </td>
                            <td>
                                <?php if ($menu['icon']): ?>
                                    <span class="badge bg-light text-dark border"><i class="<?= htmlspecialchars($menu['icon']) ?> me-1.5 text-primary"></i><?= htmlspecialchars($menu['icon']) ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400 opacity-50">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Render checkboxes for each role in access map matrix -->
                            <?php foreach ($data['roles'] as $role): 
                                $key = $role['id'] . '-' . $menu['id'];
                                $checked = isset($data['access_map'][$key]) ? 'checked' : '';
                            ?>
                                <td class="text-center">
                                    <div class="form-check d-inline-block">
                                        <input class="form-check-input rbac-matrix-checkbox border-secondary" type="checkbox" 
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
                    <?php endforeach; ?>
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

<!-- JavaScript Cascade Logic: Jika Parent di-uncheck, uncheck seluruh Child di bawahnya -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.rbac-matrix-checkbox');
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const roleId = this.dataset.role;
            const menuId = this.dataset.menu;
            const isChecked = this.checked;
            
            // Jika checkbox parent di-uncheck, hilangkan centang semua anaknya untuk role yang sama
            if (!isChecked) {
                const children = document.querySelectorAll(
                    `.rbac-matrix-checkbox[data-role="${roleId}"][data-parent="${menuId}"]`
                );
                children.forEach(child => {
                    if (child.checked) {
                        child.checked = false;
                        // Memicu trigger change secara rekursif jika anak tersebut juga bertindak sebagai parent
                        child.dispatchEvent(new Event('change'));
                    }
                });
            }
        });
    });
});
</script>
