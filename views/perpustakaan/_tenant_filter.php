<?php
/**
 * Reusable Partial View: Super Admin Tenant / School Selector Bar
 */
use App\Config\Database;

$isSuperAdmin = $data['is_super_admin'] ?? (($_SESSION['role_name'] ?? '') === 'super_admin');
$activeTenantId = $data['active_tenant_id'] ?? ($this->tenantId ?? ($_SESSION['tenant_id'] ?? null));
$tenantsList = $data['tenants'] ?? [];

$namaSekolahAktif = 'Sekolah Belum Dipilih';
$npsnAktif = '-';

if ($activeTenantId) {
    try {
        $db = Database::getConnection();
        $stmtT = $db->prepare("SELECT nama_sekolah, npsn FROM tenants WHERE id = :id LIMIT 1");
        $stmtT->execute(['id' => $activeTenantId]);
        $rowT = $stmtT->fetch();
        if ($rowT) {
            $namaSekolahAktif = $rowT['nama_sekolah'];
            $npsnAktif = $rowT['npsn'];
        }
    } catch (\Throwable $e) {}
}
?>

<div class="card border-0 shadow-sm rounded-4 bg-gradient bg-primary text-white p-3 mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-20 p-2.5 rounded-3 text-white">
                <i class="bi bi-building-fill fs-3"></i>
            </div>
            <div>
                <div class="fs-8 text-uppercase tracking-wider opacity-75">Sekolah / Tenant Terpilih</div>
                <h5 class="fw-bold mb-0 text-white"><?= htmlspecialchars($namaSekolahAktif) ?> <span class="fs-7 fw-normal opacity-75">(NPSN: <?= htmlspecialchars($npsnAktif) ?>)</span></h5>
            </div>
        </div>

        <?php if ($isSuperAdmin): ?>
            <div class="d-flex align-items-center gap-2">
                <label for="selectFilterTenant" class="form-label mb-0 fw-semibold fs-7 text-white text-nowrap">
                    <i class="bi bi-filter me-1"></i> Switch Sekolah:
                </label>
                <select id="selectFilterTenant" class="form-select form-select-sm rounded-3 border-0 bg-white text-dark shadow-sm fw-semibold px-3 py-2" style="min-width: 280px;" onchange="switchSuperAdminTenant(this.value)">
                    <?php if (empty($activeTenantId)): ?>
                        <option value="" selected>— PILIH SEKOLAH / TENANT DULU —</option>
                    <?php endif; ?>
                    <?php foreach ($tenantsList as $t): ?>
                        <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === $activeTenantId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nama_sekolah']) ?> (<?= htmlspecialchars($t['npsn']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <div>
                <span class="badge bg-white text-primary px-3 py-2 rounded-pill fs-8">
                    <i class="bi bi-shield-check me-1"></i> Admin Sekolah Terisolasi
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isSuperAdmin && empty($activeTenantId)): ?>
    <div class="alert alert-warning border-0 rounded-4 p-3 mb-4 d-flex align-items-center gap-3 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
        <div>
            <strong>Perhatian Super Admin:</strong> Silakan pilih sekolah terlebih dahulu pada dropdown di atas sebelum menambah atau mengelola data perpustakaan.
        </div>
    </div>
<?php endif; ?>

<script>
function switchSuperAdminTenant(tenantId) {
    if (!tenantId) return;
    const url = new URL(window.location.href);
    url.searchParams.set('tenant_id', tenantId);
    window.location.href = url.toString();
}
</script>
