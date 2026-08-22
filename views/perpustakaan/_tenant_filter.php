<?php
/**
 * Reusable Hero Header Banner with Multi-Tenant School Selector for Perpustakaan Module
 * SINTA SaaS Platform — Modern Gradient Design (Ekstrakurikuler Style)
 */
use App\Config\Database;

$userRole = $_SESSION['user_role'] ?? ($data['user_role'] ?? '');
$roles = $_SESSION['roles'] ?? [];
$isSuperAdmin = ($userRole === 'super_admin') || in_array('super_admin', $roles, true);

$sessionTenantId = $_SESSION['tenant_id'] ?? null;
if ($isSuperAdmin) {
    $activeTenantId = $data['active_tenant_id'] ?? ($_GET['tenant_id'] ?? ($sessionTenantId ?? null));
} else {
    $activeTenantId = $sessionTenantId;
}

$tenantsList = $data['tenants'] ?? [];
$namaSekolahAktif = 'Sekolah Belum Dipilih';
$npsnAktif = '-';

try {
    $db = Database::getConnection();
    if ($isSuperAdmin && empty($tenantsList)) {
        $stmtAll = $db->query("SELECT id, nama_sekolah, npsn FROM core.tenants WHERE id != 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' AND (status = 'active' OR status IS NULL) ORDER BY nama_sekolah ASC");
        $tenantsList = $stmtAll->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    if ($isSuperAdmin && empty($activeTenantId) && !empty($tenantsList)) {
        $activeTenantId = $tenantsList[0]['id'];
    }

    if ($activeTenantId) {
        $stmtT = $db->prepare("SELECT nama_sekolah, npsn FROM core.tenants WHERE id = :id LIMIT 1");
        $stmtT->execute(['id' => $activeTenantId]);
        $rowT = $stmtT->fetch();
        if ($rowT) {
            $namaSekolahAktif = $rowT['nama_sekolah'];
            $npsnAktif = $rowT['npsn'];
        }
    }
} catch (\Throwable $e) {}

$heroBadge = $heroBadge ?? 'Modul Perpustakaan Digital';
$heroTitle = $heroTitle ?? ($data['title'] ?? 'Perpustakaan Digital');
$heroDesc = $heroDesc ?? 'Sistem Manajemen Perpustakaan Terintegrasi (ILS) Akreditasi Sekolah.';
?>

<div class="p-4 p-md-4.5 rounded-2xl text-white shadow-xs position-relative overflow-hidden mb-4" 
     style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #0d9488 100%);">
    <!-- Ambient Glow Circles -->
    <div class="position-absolute rounded-circle" style="width: 280px; height: 280px; background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 70%); top: -90px; right: -40px; pointer-events: none;"></div>
    <div class="position-absolute rounded-circle" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(20,184,166,0.2) 0%, rgba(255,255,255,0) 70%); bottom: -70px; left: 10%; pointer-events: none;"></div>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 position-relative" style="z-index: 2;">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge px-3 py-1.5 rounded-pill text-xs font-semibold d-inline-flex align-items-center gap-1.5" style="background: rgba(255,255,255,0.18); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.25);">
                    <i class="bi <?= !empty($heroIcon) ? htmlspecialchars($heroIcon) : 'bi-book-half' ?> text-amber-300"></i> <?= htmlspecialchars($heroBadge) ?>
                </span>
            </div>
            <h2 class="h3 font-bold text-white mb-1 tracking-tight"><?= htmlspecialchars($heroTitle) ?></h2>
            <p class="text-white/85 text-xs mb-0" style="max-width: 680px; line-height: 1.6;">
                <?= htmlspecialchars($heroDesc) ?>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
            <!-- Active School / Tenant Filter Selector Dropdown (HANYA UNTUK SUPER ADMIN) -->
            <?php if ($isSuperAdmin && !empty($tenantsList)): ?>
            <div class="d-flex align-items-center gap-2 bg-white/15 p-2 rounded-xl border border-white/25 shadow-xs" style="backdrop-filter: blur(6px);">
                <i class="bi bi-building text-white fs-6 ms-1.5"></i>
                <label for="selectFilterTenant" class="visually-hidden">Pilih Sekolah</label>
                <select id="selectFilterTenant" name="tenant_id_filter" class="form-select form-select-sm border-0 text-xs font-semibold bg-white text-slate-800 rounded-lg shadow-2xs cursor-pointer" style="min-width: 220px;" onchange="switchSuperAdminTenant(this.value)" aria-label="Pilih Sekolah">
                    <?php if (empty($activeTenantId)): ?>
                        <option value="" selected disabled>— PILIH SEKOLAH / TENANT —</option>
                    <?php endif; ?>
                    <?php foreach ($tenantsList as $t): ?>
                        <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($t['id'] === $activeTenantId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nama_sekolah']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($heroButtons)): ?>
                <?= $heroButtons ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isSuperAdmin): ?>
<script>
function switchSuperAdminTenant(tenantId) {
    if (!tenantId) return;
    const url = new URL(window.location.href);
    url.searchParams.set('tenant_id', tenantId);
    window.location.href = url.toString();
}
</script>
<?php endif; ?>
