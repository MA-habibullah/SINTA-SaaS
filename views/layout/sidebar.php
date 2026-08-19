<?php
/**
 * Layout Component: Sidebar (Dinamis Berbasis RBAC)
 * Menu dimuat dari database secara real-time berdasarkan peran (role) user aktif di session.
 */
/** @var \App\Core\Controller $this */
use App\Config\Database;

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = rtrim(dirname($scriptName), '/\\');
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Ambil path murni dan bersihkan dari $baseUrl (misal: /sinta/pengguna -> /pengguna)
$rawPath = parse_url($requestUri ?? '', PHP_URL_PATH) ?? '/';
if (!empty($baseUrl) && $baseUrl !== '/' && strncasecmp($rawPath, $baseUrl, strlen($baseUrl)) === 0) {
    $rawPath = substr($rawPath, strlen($baseUrl));
}
$currentPath = '/' . trim((string)$rawPath, '/');

$roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
$roles = array_filter(array_map('trim', $roles));
if (empty($roles)) {
    $roles = [''];
}
$sidebarMenus = [];
$unreadBadgeCount = 0;

// Helper untuk mengecek active state berdasarkan path url.
$isActive = function($paths) use ($currentPath): string {
    if (empty($paths) || $paths === '#') {
        return '';
    }

    $checkPath = function(string $path) use ($currentPath): bool {
        if ($path === '#' || $path === '') {
            return false;
        }
        $menuPath = (string)strtok($path, '?');
        $menuPath = '/' . trim($menuPath, '/');

        // Exact match
        if ($currentPath === $menuPath) {
            return true;
        }

        // Dashboard / Login / Root match exact
        if ($menuPath === '/dashboard' || $menuPath === '/login' || $menuPath === '/admin' || $menuPath === '/') {
            return $currentPath === $menuPath;
        }

        // Prefix match untuk sub-halaman (misal: /siswa/edit cocok dengan /pengguna atau /siswa)
        if (strlen($menuPath) > 1 && str_starts_with($currentPath, $menuPath . '/')) {
            return true;
        }

        return false;
    };

    if (is_array($paths)) {
        foreach ($paths as $path) {
            if ($checkPath((string)$path)) {
                return 'active';
            }
        }
    } else {
        if ($checkPath((string)$paths)) {
            return 'active';
        }
    }
    return '';
};

// Role normalization helper
$normalizedRoles = [];
foreach ($roles as $r) {
    $clean = strtolower(trim((string)$r));
    $normalizedRoles[] = $clean;
    if (in_array($clean, ['super_admin', 'superadmin', 'admin', 'super admin'], true)) {
        $normalizedRoles[] = 'super_admin';
        $normalizedRoles[] = 'superadmin';
        $normalizedRoles[] = 'admin';
        $normalizedRoles[] = 'operator_sekolah';
    }
}
$roles = array_values(array_unique($normalizedRoles));

// Pemuatan data menu dinamis dari database (Secure by Design - prepared statements)
if (!empty($roles)) {
    try {
        $db = Database::getConnection();
        $tenantId = $_SESSION['tenant_id'] ?? null;
        
        if ($tenantId) {
            $stmtCheckCustom = $db->prepare("SELECT COUNT(*) FROM core.role_menu_access WHERE tenant_id = :tenant_id");
            $stmtCheckCustom->execute(['tenant_id' => $tenantId]);
            $hasCustomAccess = (int)$stmtCheckCustom->fetchColumn() > 0;
            $accessTenantId = $hasCustomAccess ? $tenantId : '00000000-0000-0000-0000-000000000000';

            $inClause = implode(',', array_fill(0, count($roles), '?'));
            $sql = "SELECT DISTINCT m.* 
                    FROM core.menus m
                    JOIN core.tenant_menu_access tma ON m.id = tma.menu_id
                    WHERE (tma.tenant_id = ? OR tma.tenant_id = '00000000-0000-0000-0000-000000000000')
                      AND (
                          m.id IN (
                              SELECT rma.menu_id 
                              FROM core.role_menu_access rma
                              JOIN core.roles r ON rma.role_id = r.id
                              WHERE LOWER(r.nama_role) IN ($inClause) AND (rma.tenant_id = ? OR rma.tenant_id = '00000000-0000-0000-0000-000000000000')
                          )
                          OR m.id IN (
                              SELECT uma.menu_id 
                              FROM core.user_menu_access uma 
                              WHERE uma.user_id = ? AND (uma.tenant_id = ? OR uma.tenant_id = '00000000-0000-0000-0000-000000000000')
                          )
                      )
                    ORDER BY m.parent_id ASC, m.urutan ASC";
            $stmt = $db->prepare($sql);
            $params = array_merge([$tenantId], $roles, [$accessTenantId, $_SESSION['user_id'] ?? '', $tenantId]);
            $stmt->execute($params);
        } else {
            $inClause = implode(',', array_fill(0, count($roles), '?'));
            $sql = "SELECT DISTINCT m.* 
                    FROM core.menus m
                    JOIN core.role_menu_access rma ON m.id = rma.menu_id
                    JOIN core.roles r ON rma.role_id = r.id
                    WHERE LOWER(r.nama_role) IN ($inClause)
                      AND (rma.tenant_id = '00000000-0000-0000-0000-000000000000' OR rma.tenant_id IS NOT NULL)
                    ORDER BY m.parent_id ASC, m.urutan ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($roles);
        }
        $allMenus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $buildTree = function(array $menus, ?string $parentId = null) use (&$buildTree) {
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

        $customModulName = 'Keuangan & Pembayaran';
        $visibilitasSiswa = 1;
        if (!empty($_SESSION['tenant_id'])) {
            try {
                $stmtSet = $db->prepare("SELECT nama_modul, visibilitas_siswa FROM keuangan.transaksi_spp_pengaturan WHERE tenant_id = ?");
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
            if ($menu['nama_menu'] == 'Keuangan') {
                $menu['nama_menu'] = $customModulName;
                if ($visibilitasSiswa === 0 && in_array('siswa', $roles)) {
                    continue;
                }
            }
            $filteredSidebarMenus[] = $menu;
        }
        $sidebarMenus = $filteredSidebarMenus;

        if (in_array('siswa', $roles)) {
            $siswaId = $_SESSION['user_id'] ?? '';
            $statusSiswa = 'Aktif';
            if (!empty($siswaId)) {
                try {
                    $stmtStatus = $db->prepare("SELECT status FROM siswa.siswa WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                    $stmtStatus->execute([$siswaId]);
                    $statusSiswa = $stmtStatus->fetchColumn() ?: 'Aktif';
                } catch (\Throwable $e) {}
            }

            foreach ($sidebarMenus as &$menu) {
                if (stripos($menu['nama_menu'], 'Data Diri') !== false && !empty($siswaId)) {
                    $menu['url'] = '/pengguna';
                }
                if ((stripos($menu['nama_menu'], 'Data Pokok') !== false || stripos($menu['nama_menu'], 'Core Dapodik') !== false) && !empty($siswaId)) {
                    $menu['url'] = '/pengguna';
                    $menu['children'] = [];
                }
                if (!empty($menu['children'])) {
                    foreach ($menu['children'] as &$child) {
                        if (stripos($child['nama_menu'], 'Data Diri') !== false && !empty($siswaId)) {
                            $child['url'] = '/pengguna';
                        }
                    }
                    unset($child);
                }
            }
            unset($menu);

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
                    'url'       => '/tracer-study',
                    'icon'      => 'bi bi-mortarboard-fill',
                    'badge'     => 'BARU',
                    'children'  => []
                ];
            }
        }

        $unreadBadgeCount = 0;
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (($_SESSION['role_name'] ?? '') === 'super_admin') {
                $stmtUnread = $db->prepare("SELECT COUNT(*) FROM sistem.tickets WHERE admin_unread = 1");
                $stmtUnread->execute();
            } else {
                $stmtUnread = $db->prepare("SELECT COUNT(*) FROM sistem.tickets WHERE user_unread = 1 AND tenant_id = ? AND user_id = ?");
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
        
        <!-- Menu Navigasi -->
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
                        if (!empty($menu['children'])):
                            $hasActiveChild = false;
                            foreach ($menu['children'] as $child) {
                                if ($isActive($child['url']) === 'active') {
                                    $hasActiveChild = true;
                                    break;
                                }
                            }
                            $collapseShow = $hasActiveChild ? 'show' : '';
                            $ariaExpanded = $hasActiveChild ? 'true' : 'false';
                            $collapsedClass = $hasActiveChild ? '' : 'collapsed';
                            $parentActiveClass = $hasActiveChild ? 'parent-active' : '';
                ?>
                            <li class="nav-item">
                                <!-- Induk Menu Collapsible -->
                                <a class="nav-link-item d-flex justify-content-between align-items-center <?= $parentActiveClass ?> <?= $collapsedClass ?>" 
                                   data-bs-toggle="collapse" 
                                   href="#menuCollapse<?= $menu['id'] ?>" 
                                   role="button" 
                                   aria-expanded="<?= $ariaExpanded ?>" 
                                   aria-controls="menuCollapse<?= $menu['id'] ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="<?= htmlspecialchars($menu['icon'] ?? 'bi bi-folder-fill') ?>"></i>
                                        <span class="nav-label"><?= htmlspecialchars($menu['nama_menu']) ?></span>
                                    </div>
                                    <i class="bi bi-chevron-down nav-label arrow-icon" style="font-size: 0.7rem; transition: transform 0.2s ease;"></i>
                                </a>
                                
                                <!-- Container Sub-menu (Collapsible) -->
                                <div class="collapse <?= $collapseShow ?>" id="menuCollapse<?= $menu['id'] ?>">
                                    <ul class="nav flex-column gap-1">
                                        <?php 
                                        foreach ($menu['children'] as $child): 
                                            $childActiveState = $isActive($child['url']);
                                        ?>
                                            <li class="nav-item">
                                                <a href="<?= ($child['url'] === '#' || empty($child['url'])) ? '#' : $this->getBaseUrl() . htmlspecialchars($child['url']) ?>" 
                                                   class="nav-link-item sub-nav-item <?= $childActiveState ?>"
                                                   <?= ($child['url'] === '#' || empty($child['url'])) ? 'onclick="showSimulationAlert(\'' . htmlspecialchars($child['nama_menu'], ENT_QUOTES, 'UTF-8') . '\'); return false;"' : '' ?>>
                                                    <i class="<?= htmlspecialchars($child['icon'] ?? 'bi bi-record-circle') ?>"></i>
                                                    <span class="nav-label"><?= htmlspecialchars($child['nama_menu']) ?></span>
                                                    <?php if ($childActiveState === 'active'): ?>
                                                        <span class="ms-auto active-dot-indicator"></span>
                                                    <?php endif; ?>
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
                            $mainActiveState = $isActive($menu['url']);
                ?>
                            <li class="nav-item">
                                <a href="<?= ($menu['url'] === '#' || empty($menu['url'])) ? '#' : $this->getBaseUrl() . htmlspecialchars($menu['url']) ?>" 
                                   class="nav-link-item <?= $mainActiveState ?>"
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

                <!-- CSS Bootstrap vs Tailwind Fix -->
                <style>
                    .collapse {
                        visibility: visible !important;
                    }
                    .nav-link-item[aria-expanded="true"] .arrow-icon,
                    .nav-link-item:not(.collapsed) .arrow-icon {
                        transform: rotate(180deg);
                    }
                </style>

            </ul>
        </div>
        
        <!-- Sidebar Footer Info -->
        <div class="sidebar-footer p-3 border-top border-light text-center">
            <div class="nav-label fs-9 text-muted fw-bold">SINTA PLATFORM</div>
        </div>

    </div>

<!-- PREMIUM HIGH-VISIBILITY SIDEBAR STYLING -->
<style>
#sidebar {
    background: linear-gradient(145deg, rgba(255,255,255,0.96), rgba(248,250,252,0.99));
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-right: 1px solid rgba(226,232,240,0.9);
    box-shadow: 4px 0 24px rgba(0,0,0,0.03);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

#sidebar .nav-item {
    margin-bottom: 0.2rem;
}

#sidebar .nav-link-item {
    padding: 0.65rem 0.95rem;
    border-radius: 0.65rem;
    color: #475569;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    text-decoration: none;
    position: relative;
    border-left: 3px solid transparent;
}

#sidebar .nav-link-item i {
    font-size: 1.15rem;
    margin-right: 0.75rem;
    color: #64748b;
    transition: all 0.2s ease;
}

#sidebar .nav-link-item .nav-label {
    font-family: 'Inter', 'Segoe UI', sans-serif;
    letter-spacing: -0.01em;
}

/* Hover State */
#sidebar .nav-link-item:hover {
    background: rgba(239, 246, 255, 0.75);
    color: #1d4ed8;
    border-left-color: #93c5fd;
}

#sidebar .nav-link-item:hover i {
    color: #2563eb;
}

/* Standalone Main Active State */
#sidebar .nav-link-item.active:not(.sub-nav-item) {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important;
    border-left-color: #1e40af !important;
}

#sidebar .nav-link-item.active:not(.sub-nav-item) i {
    color: #ffffff !important;
}

/* Parent Active Header State (When Child Active) */
#sidebar .nav-link-item.parent-active {
    background: linear-gradient(135deg, rgba(239, 246, 255, 0.95) 0%, rgba(219, 234, 254, 0.8) 100%) !important;
    color: #1e40af !important;
    font-weight: 700 !important;
    border-left-color: #2563eb !important;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1) !important;
}

#sidebar .nav-link-item.parent-active i {
    color: #2563eb !important;
}

/* Sub-menu Collapsible Container & Professional Alignment */
#sidebar .collapse ul {
    border-left: 2px solid rgba(203, 213, 225, 0.8);
    margin-left: 1.35rem;
    padding-left: 0.5rem;
    margin-top: 0.25rem;
    margin-bottom: 0.35rem;
}

#sidebar .collapse .nav-link-item.sub-nav-item {
    padding: 0.5rem 0.8rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
    color: #64748b;
    font-weight: 500;
    border-left: none;
}

#sidebar .collapse .nav-link-item.sub-nav-item i {
    font-size: 0.75rem;
    margin-right: 0.55rem;
    color: #94a3b8;
}

#sidebar .collapse .nav-link-item.sub-nav-item:hover {
    background: rgba(241, 245, 249, 0.95);
    color: #0f172a;
    transform: translateX(3px);
}

#sidebar .collapse .nav-link-item.sub-nav-item:hover i {
    color: #2563eb;
}

/* HIGH-CONTRAST ACTIVE SUB-MENU STATE */
#sidebar .collapse .nav-link-item.sub-nav-item.active {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35) !important;
    transform: translateX(3px);
}

#sidebar .collapse .nav-link-item.sub-nav-item.active i {
    color: #ffffff !important;
}

/* Active Dot Indicator */
.active-dot-indicator {
    width: 6px;
    height: 6px;
    background-color: #ffffff;
    border-radius: 50%;
    box-shadow: 0 0 6px #ffffff;
}

/* Custom Scrollbar */
#sidebar .overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
#sidebar .overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
#sidebar .overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(203, 213, 225, 0.7);
    border-radius: 10px;
}
</style>
</aside>
