<?php
/**
 * Layout Component: Sidebar (Dinamis Berbasis RBAC)
 * Menu dimuat dari database secara real-time berdasarkan peran (role) user aktif di session.
 */
use App\Config\Database;

$requestUri = $_SERVER['REQUEST_URI'];
$roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
$roles = array_filter(array_map('trim', $roles));
if (empty($roles)) {
    $roles = [''];
}
$sidebarMenus = [];

// Helper untuk mengecek active state berdasarkan path url
$isActive = function($paths) use ($requestUri) {
    if (empty($paths) || $paths === '#') {
        return '';
    }
    
    if (is_array($paths)) {
        foreach ($paths as $path) {
            if ($path !== '#' && str_contains($requestUri, $path)) {
                return 'active';
            }
        }
    } else {
        if (str_contains($requestUri, $paths)) {
            return 'active';
        }
    }
    return '';
};

// Pemuatan data menu dinamis dari database (Secure by Design - prepared statements)
if (!empty($roles)) {
    try {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        if ($tenantId) {
            // Cek apakah tenant ini memiliki kustomisasi di role_menu_access
            $stmtCheckCustom = $db->prepare("SELECT COUNT(*) FROM role_menu_access WHERE tenant_id = :tenant_id");
            $stmtCheckCustom->execute(['tenant_id' => $tenantId]);
            $hasCustomAccess = (int)$stmtCheckCustom->fetchColumn() > 0;
            $accessTenantId = $hasCustomAccess ? $tenantId : '00000000-0000-0000-0000-000000000000';

            // Ambil menu yang terpetakan untuk salah satu role user saat ini DAN diaktifkan untuk sekolah/tenant ini
            $inClause = implode(',', array_fill(0, count($roles), '?'));
            $sql = "SELECT DISTINCT m.* 
                    FROM menus m
                    JOIN tenant_menu_access tma ON m.id = tma.menu_id
                    WHERE tma.tenant_id = ?
                      AND (
                          m.id IN (
                              SELECT rma.menu_id 
                              FROM role_menu_access rma
                              JOIN roles r ON rma.role_id = r.id
                              WHERE r.nama_role IN ($inClause) AND rma.tenant_id = ?
                          )
                          OR m.id IN (
                              SELECT uma.menu_id 
                              FROM user_menu_access uma 
                              WHERE uma.user_id = ? AND uma.tenant_id = ?
                          )
                      )
                    ORDER BY m.parent_id ASC, m.urutan ASC";
            $stmt = $db->prepare($sql);
            $params = array_merge([$tenantId], $roles, [$accessTenantId, $_SESSION['user_id'] ?? '', $tenantId]);
            $stmt->execute($params);
        } else {
            // Tanpa filter tenant (untuk Super Admin yang mengelola seluruh platform, gunakan fallback default)
            $inClause = implode(',', array_fill(0, count($roles), '?'));
            $sql = "SELECT DISTINCT m.* 
                    FROM menus m
                    JOIN role_menu_access rma ON m.id = rma.menu_id
                    JOIN roles r ON rma.role_id = r.id
                    WHERE r.nama_role IN ($inClause)
                      AND rma.tenant_id = '00000000-0000-0000-0000-000000000000'
                    ORDER BY m.parent_id ASC, m.urutan ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($roles);
        }
        $allMenus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Rekonstruksi array menu datar menjadi pohon hirarki (Parent-Child)
        $buildTree = function(array $menus, ?int $parentId = null) use (&$buildTree) {
            $branch = [];
            foreach ($menus as $menu) {
                if ($menu['parent_id'] == $parentId) {
                    $children = $buildTree($menus, $menu['id']);
                    $menu['children'] = $children ?: [];
                    $branch[] = $menu;
                }
            }
            return $branch;
        };
        
        $sidebarMenus = $buildTree($allMenus);

        // Kustomisasi Istilah & Visibilitas Modul Keuangan (SPP) secara Dinamis
        $customModulName = 'Keuangan & Pembayaran';
        $visibilitasSiswa = 1;
        if (!empty($_SESSION['tenant_id'])) {
            try {
                $stmtSet = $db->prepare("SELECT nama_modul, visibilitas_siswa FROM transaksi_spp_pengaturan WHERE tenant_id = ?");
                $stmtSet->execute([$_SESSION['tenant_id']]);
                $setting = $stmtSet->fetch(PDO::FETCH_ASSOC);
                if ($setting) {
                    $customModulName = $setting['nama_modul'];
                    $visibilitasSiswa = (int)$setting['visibilitas_siswa'];
                }
            } catch (\Throwable $e) {}
        }

        $filteredSidebarMenus = [];
        foreach ($sidebarMenus as $menu) {
            if ($menu['id'] == 70) {
                $menu['nama_menu'] = $customModulName;
                if ($visibilitasSiswa === 0 && in_array('siswa', $roles)) {
                    continue; // Sembunyikan modul keuangan dari dashboard siswa jika diset private
                }
            }
            $filteredSidebarMenus[] = $menu;
        }
        $sidebarMenus = $filteredSidebarMenus;



        if (in_array('siswa', $roles)) {
            $siswaId = $_SESSION['user_id'] ?? '';

            // Cek status siswa dari DB (BUKAN dari session, untuk mencegah session tampering)
            $statusSiswa = 'Aktif'; // default
            if (!empty($siswaId)) {
                try {
                    $stmtStatus = $db->prepare("SELECT status FROM siswa WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                    $stmtStatus->execute([$siswaId]);
                    $statusSiswa = $stmtStatus->fetchColumn() ?: 'Aktif';
                } catch (\Throwable $e) {
                    // Fail-safe: jika DB error, anggap aktif (tidak tampilkan menu tracer)
                }
            }

            // Inject URL dinamis untuk menu siswa:
            // - "Data Diri" → redirect ke halaman edit profil siswa (dengan user_id)
            // - "Data Pokok / Core Dapodik" (url=#) → redirect ke halaman profil siswa
            // Menu lainnya tetap berasal dari database (role_menu_access), sehingga
            // konfigurasi di halaman /konfigurasi/akses tetap berlaku.
            foreach ($sidebarMenus as &$menu) {
                // Inject URL untuk "Data Diri"
                if (stripos($menu['nama_menu'], 'Data Diri') !== false && !empty($siswaId)) {
                    $menu['url'] = '/SINTA-SaaS/siswa/edit?id=' . $siswaId;
                }
                // Inject URL untuk "Data Pokok / Core Dapodik" (parent dengan url=#)
                // Untuk siswa, tampilkan profil mereka sendiri
                if ((stripos($menu['nama_menu'], 'Data Pokok') !== false || stripos($menu['nama_menu'], 'Core Dapodik') !== false) && !empty($siswaId)) {
                    $menu['url'] = '/SINTA-SaaS/siswa/edit?id=' . $siswaId;
                    $menu['children'] = []; // Hilangkan sub-menu (Pengguna, Master Data, dll tidak relevan untuk siswa)
                }
                // Inject URL dinamis juga di children jika ada
                if (!empty($menu['children'])) {
                    foreach ($menu['children'] as &$child) {
                        if (stripos($child['nama_menu'], 'Data Diri') !== false && !empty($siswaId)) {
                            $child['url'] = '/SINTA-SaaS/siswa/edit?id=' . $siswaId;
                        }
                    }
                    unset($child);
                }
            }
            unset($menu);


            // Tambahkan menu Tracer Study HANYA jika status siswa adalah 'Lulus'
            // dan menu ini belum ada di $sidebarMenus dari database
            $tracerExists = false;
            foreach ($sidebarMenus as $m) {
                if (stripos($m['nama_menu'], 'Tracer') !== false) {
                    $tracerExists = true;
                    break;
                }
            }
            if ($statusSiswa === 'Lulus' && !$tracerExists) {
                $sidebarMenus[] = [
                    'id'        => 99,
                    'nama_menu' => 'Tracer Study',
                    'url'       => '/SINTA-SaaS/tracer-study',
                    'icon'      => 'bi bi-mortarboard-fill',
                    'badge'     => 'BARU',
                    'children'  => []
                ];
            }
        }

        // Ambil jumlah unread tickets untuk badge sidebar
        $unreadBadgeCount = 0;
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (($_SESSION['role_name'] ?? '') === 'super_admin') {
                $stmtUnread = $db->prepare("SELECT COUNT(*) FROM tickets WHERE admin_unread = 1");
                $stmtUnread->execute();
            } else {
                $stmtUnread = $db->prepare("SELECT COUNT(*) FROM tickets WHERE user_unread = 1 AND tenant_id = ? AND user_id = ?");
                $stmtUnread->execute([$_SESSION['tenant_id'] ?? null, $_SESSION['user_id'] ?? null]);
            }
            $unreadBadgeCount = (int)$stmtUnread->fetchColumn();
        }

    } catch (\Throwable $e) {
        error_log("Gagal memuat sidebar dinamis: " . $e->getMessage());
    }
}
?>
<aside id="sidebar" class="sidebar">
    <div class="sidebar-wrapper d-flex flex-column h-100">
        
        <!-- Menu Navigasi (Compact Padding & Small Font) -->
        <div class="flex-grow-1 overflow-y-auto py-3">
            <ul class="nav flex-column gap-1 px-2">
                
                <?php 
                if (empty($sidebarMenus)): 
                ?>
                    <li class="px-3 text-muted fs-8 text-center py-4">
                        <i class="bi bi-shield-lock d-block mb-1 fs-5"></i> Tidak ada menu yang diizinkan.
                    </li>
                <?php 
                else:
                    foreach ($sidebarMenus as $menu):
                        // Cek apakah menu bertindak sebagai parent yang memiliki anak
                        if (!empty($menu['children'])):
                            // Cek keaktifan anak-anak menu secara otomatis
                            $hasActiveChild = false;
                            foreach ($menu['children'] as $child) {
                                if ($isActive($child['url']) === 'active') {
                                    $hasActiveChild = true;
                                    break;
                                }
                            }
                            $collapseShow = $hasActiveChild ? 'show' : '';
                            $ariaExpanded = $hasActiveChild ? 'true' : 'false';
                            $parentActive = $hasActiveChild ? 'active' : '';
                ?>
                            <li class="nav-item">
                                <!-- Induk Menu Collapsible -->
                                <a class="nav-link-item d-flex justify-content-between align-items-center <?= $parentActive ?>" 
                                   data-bs-toggle="collapse" 
                                   href="#menuCollapse<?= $menu['id'] ?>" 
                                   role="button" 
                                   aria-expanded="<?= $ariaExpanded ?>" 
                                   aria-controls="menuCollapse<?= $menu['id'] ?>">
                                    <div>
                                        <i class="<?= htmlspecialchars($menu['icon'] ?? 'bi bi-folder-fill') ?>"></i>
                                        <span class="nav-label"><?= htmlspecialchars($menu['nama_menu']) ?></span>
                                    </div>
                                    <i class="bi bi-chevron-down nav-label arrow-icon" style="font-size: 0.7rem; transition: transform 0.2s ease;"></i>
                                </a>
                                
                                <!-- Container Sub-menu (Collapsible) -->
                                <div class="collapse <?= $collapseShow ?>" id="menuCollapse<?= $menu['id'] ?>">
                                    <ul class="nav flex-column ps-3 pt-1 gap-1">
                                        <?php 
                                        foreach ($menu['children'] as $child): 
                                            $activeClass = $isActive($child['url']);
                                        ?>
                                            <li class="nav-item">
                                                <a href="<?= htmlspecialchars($child['url'] ?? '#') ?>" class="nav-link-item <?= $activeClass ?>"
                                                   <?= ($child['url'] === '#' || empty($child['url'])) ? 'onclick="showSimulationAlert(\'' . htmlspecialchars($child['nama_menu'], ENT_QUOTES, 'UTF-8') . '\'); return false;"' : '' ?>>
                                                    <i class="<?= htmlspecialchars($child['icon'] ?? 'bi bi-circle') ?>"></i>
                                                    <span class="nav-label"><?= htmlspecialchars($child['nama_menu']) ?></span>
                                                </a>
                                            </li>
                                        <?php 
                                        endforeach; 
                                        ?>
                                    </ul>
                                </div>
                            </li>

                <?php 
                        else: 
                            // Render menu utama mandiri (Tanpa Sub-menu, misal: Dashboard)
                            $activeClass = $isActive($menu['url']);
                ?>
                            <li class="nav-item">
                                <a href="<?= htmlspecialchars($menu['url'] ?? '#') ?>" class="nav-link-item <?= $activeClass ?>"
                                   <?= ($menu['url'] === '#' || empty($menu['url'])) ? 'onclick="showSimulationAlert(\'' . htmlspecialchars($menu['nama_menu'], ENT_QUOTES, 'UTF-8') . '\'); return false;"' : '' ?>>
                                    <i class="<?= htmlspecialchars($menu['icon'] ?? 'bi bi-circle') ?>"></i>
                                    <span class="nav-label"><?= htmlspecialchars($menu['nama_menu']) ?></span>
                                     <?php if ($menu['id'] == 61 && $unreadBadgeCount > 0): ?>
                                     <span class="ms-auto badge rounded-pill bg-danger" style="font-size:0.6rem;padding:2px 6px;">
                                         <?= $unreadBadgeCount ?>
                                     </span>
                                     <?php elseif (!empty($menu['badge'])): ?>
                                     <span class="ms-auto badge rounded-pill text-bg-success" style="font-size:0.6rem;padding:2px 6px;">
                                         <?= htmlspecialchars($menu['badge']) ?>
                                     </span>
                                     <?php endif; ?>
                                </a>
                            </li>
                <?php 
                        endif;
                    endforeach;
                endif;
                ?>

                <!-- CSS Tambahan Khusus Submenu & Chevron Rotation -->
                <style>
                    /* Fix collision between Bootstrap and Tailwind collapse classes */
                    .collapse {
                        visibility: visible !important;
                    }

                    .nav-link-item[aria-expanded="true"] .arrow-icon {
                        transform: rotate(180deg);
                    }
                </style>

            </ul>
        </div>
        
        <!-- Sidebar Footer Info (Hidden when collapsed) -->
        <div class="sidebar-footer p-3 border-top border-light text-center">
            <div class="nav-label fs-9 text-muted fw-bold">SINTA PLATFORM</div>
        </div>

    </div>
</aside>
