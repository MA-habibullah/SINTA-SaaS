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
                    JOIN role_menu_access rma ON m.id = rma.menu_id
                    JOIN roles r ON rma.role_id = r.id
                    JOIN tenant_menu_access tma ON m.id = tma.menu_id
                    WHERE r.nama_role IN ($inClause) 
                      AND tma.tenant_id = ?
                      AND rma.tenant_id = ?
                    ORDER BY m.parent_id ASC, m.urutan ASC";
            $stmt = $db->prepare($sql);
            $params = array_merge($roles, [$tenantId, $accessTenantId]);
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

            $sidebarMenus = [
                [
                    'id'       => 1,
                    'nama_menu'=> 'Dashboard',
                    'url'      => '/SINTA-SaaS/dashboard',
                    'icon'     => 'bi bi-grid-fill',
                    'children' => []
                ],
                [
                    'id'       => 6,
                    'nama_menu'=> 'Data Diri',
                    'url'      => '/SINTA-SaaS/siswa/edit?id=' . $siswaId,
                    'icon'     => 'bi bi-person-bounding-box',
                    'children' => []
                ]
            ];

            // Tambahkan menu Tracer Study HANYA jika status siswa adalah 'Lulus'
            if ($statusSiswa === 'Lulus') {
                $sidebarMenus[] = [
                    'id'        => 99,
                    'nama_menu' => 'Tracer Study',
                    'url'       => '/SINTA-SaaS/tracer-study',
                    'icon'      => 'bi bi-mortarboard-fill',
                    'badge'     => 'BARU',  // Untuk indikator visual di template
                    'children'  => []
                ];
            }
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
                                                <a href="<?= htmlspecialchars($child['url'] ?? '#') ?>" class="nav-link-item py-1.5 <?= $activeClass ?>"
                                                   <?= ($child['url'] === '#' || empty($child['url'])) ? 'onclick="showSimulationAlert(\'' . htmlspecialchars($child['nama_menu'], ENT_QUOTES, 'UTF-8') . '\'); return false;"' : '' ?>>
                                                    <i class="<?= htmlspecialchars($child['icon'] ?? 'bi bi-circle') ?> fs-8"></i>
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
                                    <?php if (!empty($menu['badge'])): ?>
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
                    /* Gaya font sub-menu (13px) */
                    #sidebar .collapse .nav-link-item {
                        font-size: 0.8125rem; /* ~13px */
                        padding-top: 0.4rem;
                        padding-bottom: 0.4rem;
                    }
                    /* Efek penyeimbang ukuran teks di mobile */
                    @media (max-width: 991.98px) {
                        #sidebar .collapse .nav-link-item {
                            font-size: 0.85rem;
                        }
                    }
                </style>

            </ul>
        </div>
        
        <!-- Sidebar Footer Info (Hidden when collapsed) -->
        <div class="sidebar-footer p-3 border-top border-light text-center">
            <div class="nav-label fs-9 text-muted fw-bold">DAPODIK PLATFORM</div>
        </div>

    </div>
</aside>
