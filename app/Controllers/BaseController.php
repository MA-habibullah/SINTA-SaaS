<?php

namespace App\Controllers;

class BaseController {
    protected ?string $tenantId = null;

    public function __construct() {
        $this->detectTenant();
    }

    protected function detectTenant(): void {
        $subdomain = null;

        // 1. Dapatkan subdomain dari Header (untuk testing lokal) atau Hostname (produksi)
        if (isset($_SERVER['HTTP_X_TENANT_ID'])) {
            $subdomain = $_SERVER['HTTP_X_TENANT_ID'];
        } else {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $parts = explode('.', $host);
            // Jika ada subdomain (misal: sman1jkt.dapodikspmb.id), parts minimal 3
            if (count($parts) >= 3 && $parts[0] !== 'www') {
                $subdomain = $parts[0];
            }
        }

        // 2. Jika subdomain terdeteksi, cari UUID tenant_id di database
        if ($subdomain !== null && $subdomain !== '') {
            $this->tenantId = $this->getTenantIdBySubdomain($subdomain);
        }
    }

    /**
     * Get tenant UUID from database via subdomain
     */
    private function getTenantIdBySubdomain(string $subdomain): ?string {
        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("SELECT id FROM tenants WHERE subdomain = :subdomain AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['subdomain' => $subdomain]);
            $row = $stmt->fetch();
            return $row ? $row['id'] : null;
        } catch (\PDOException $e) {
            error_log("Failed to detect tenant by subdomain: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Resolve IDs inside log old_data and new_data to human-readable names.
     */
    protected function resolveLogDataIds(array &$logs, \PDO $db): void {
        $ids = [
            'angkatan' => [],
            'tahun_ajaran' => [],
            'jenjang' => [],
            'jurusan' => [],
            'kelas' => [],
            'pendidikan' => [],
            'kota' => [],
            'users' => [],
            'siswa' => [],
            'roles' => [],
            'tenants' => [],
            'kelurahan' => []
        ];

        // 1. Kumpulkan semua ID unik
        foreach ($logs as $log) {
            foreach (['old_data', 'new_data'] as $field) {
                if (empty($log[$field])) continue;
                $data = json_decode($log[$field], true);
                if (!is_array($data)) continue;

                foreach ($data as $key => $val) {
                    if (empty($val)) continue;

                    if ($key === 'id_angkatan') {
                        $ids['angkatan'][] = $val;
                    } elseif ($key === 'id_tahun_ajaran') {
                        $ids['tahun_ajaran'][] = $val;
                    } elseif ($key === 'id_jenjang') {
                        $ids['jenjang'][] = $val;
                    } elseif (in_array($key, ['id_jurusan', 'id_jurusan_lama', 'id_jurusan_baru'])) {
                        $ids['jurusan'][] = $val;
                    } elseif (in_array($key, ['id_kelas', 'id_kelas_snapshot', 'id_kelas_asal', 'id_kelas_tujuan'])) {
                        $ids['kelas'][] = $val;
                    } elseif ($key === 'id_pendidikan') {
                        $ids['pendidikan'][] = $val;
                    } elseif (in_array($key, ['tempat_lahir', 'id_tempat_lahir_ayah', 'id_tempat_lahir_ibu', 'id_tempat_lahir_wali'])) {
                        if (is_numeric($val)) {
                            $ids['kota'][] = $val;
                        }
                    } elseif (in_array($key, ['user_id', 'id_guru_bk', 'diverifikasi_oleh'])) {
                        $ids['users'][] = $val;
                    } elseif (in_array($key, ['siswa_id', 'id_siswa'])) {
                        $ids['siswa'][] = $val;
                    } elseif ($key === 'role_id') {
                        $ids['roles'][] = $val;
                    } elseif ($key === 'tenant_id') {
                        $ids['tenants'][] = $val;
                    } elseif ($key === 'id_kelurahan') {
                        $ids['kelurahan'][] = $val;
                    }
                }
            }
        }

        // Clean & keep only unique non-empty IDs
        foreach ($ids as $key => &$arr) {
            $arr = array_unique(array_filter($arr));
        }
        unset($arr);

        // 2. Fetch lookup data dari database
        $lookups = [];
        foreach ($ids as $table => $values) {
            $lookups[$table] = [];
            if (empty($values)) continue;

            $quotedValues = array_map([$db, 'quote'], $values);
            $in = implode(',', $quotedValues);

            if ($table === 'angkatan') {
                $lookups['angkatan'] = $db->query("SELECT id, tahun_angkatan FROM angkatan WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'tahun_ajaran') {
                $lookups['tahun_ajaran'] = $db->query("SELECT id, tahun_ajaran FROM tahun_ajaran WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'jenjang') {
                $lookups['jenjang'] = $db->query("SELECT id, nama_jenjang FROM jenjang WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'jurusan') {
                $lookups['jurusan'] = $db->query("SELECT id, nama_jurusan FROM jurusan WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'kelas') {
                $lookups['kelas'] = $db->query("SELECT id, nama_kelas FROM kelas WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'pendidikan') {
                $lookups['pendidikan'] = $db->query("SELECT id, nama_pendidikan FROM pendidikan WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'kota') {
                $lookups['kota'] = $db->query("SELECT id_kota, nama_kota FROM kota WHERE id_kota IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'users') {
                $lookups['users'] = $db->query("SELECT id, nama_lengkap FROM users WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'siswa') {
                $lookups['siswa'] = $db->query("SELECT id, nama_lengkap FROM siswa WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'roles') {
                $lookups['roles'] = $db->query("SELECT id, nama_role FROM roles WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'tenants') {
                $lookups['tenants'] = $db->query("SELECT id, nama_sekolah FROM tenants WHERE id IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            } elseif ($table === 'kelurahan') {
                $lookups['kelurahan'] = $db->query("SELECT id_kelurahan, nama_kelurahan FROM kelurahan WHERE id_kelurahan IN ($in)")->fetchAll(\PDO::FETCH_KEY_PAIR);
            }
        }

        // 3. Ganti ID dengan nama human-readable di array logs
        foreach ($logs as &$log) {
            foreach (['old_data', 'new_data'] as $field) {
                if (empty($log[$field])) continue;
                $data = json_decode($log[$field], true);
                if (!is_array($data)) continue;

                $changed = false;
                foreach ($data as $key => &$val) {
                    if (empty($val)) continue;

                    $lookupKey = is_numeric($val) ? (int)$val : $val;

                    if ($key === 'id_angkatan' && isset($lookups['angkatan'][$lookupKey])) {
                        $val = $lookups['angkatan'][$lookupKey];
                        $changed = true;
                    } elseif ($key === 'id_tahun_ajaran' && isset($lookups['tahun_ajaran'][$lookupKey])) {
                        $val = $lookups['tahun_ajaran'][$lookupKey];
                        $changed = true;
                    } elseif ($key === 'id_jenjang' && isset($lookups['jenjang'][$lookupKey])) {
                        $val = $lookups['jenjang'][$lookupKey];
                        $changed = true;
                    } elseif (in_array($key, ['id_jurusan', 'id_jurusan_lama', 'id_jurusan_baru']) && isset($lookups['jurusan'][$lookupKey])) {
                        $val = $lookups['jurusan'][$lookupKey];
                        $changed = true;
                    } elseif (in_array($key, ['id_kelas', 'id_kelas_snapshot', 'id_kelas_asal', 'id_kelas_tujuan']) && isset($lookups['kelas'][$lookupKey])) {
                        $val = $lookups['kelas'][$lookupKey];
                        $changed = true;
                    } elseif ($key === 'id_pendidikan' && isset($lookups['pendidikan'][$lookupKey])) {
                        $val = $lookups['pendidikan'][$lookupKey];
                        $changed = true;
                    } elseif (in_array($key, ['tempat_lahir', 'id_tempat_lahir_ayah', 'id_tempat_lahir_ibu', 'id_tempat_lahir_wali']) && is_numeric($val) && isset($lookups['kota'][$lookupKey])) {
                        $val = $lookups['kota'][$lookupKey];
                        $changed = true;
                    } elseif (in_array($key, ['user_id', 'id_guru_bk', 'diverifikasi_oleh']) && isset($lookups['users'][$val])) {
                        $val = $lookups['users'][$val];
                        $changed = true;
                    } elseif (in_array($key, ['siswa_id', 'id_siswa']) && isset($lookups['siswa'][$val])) {
                        $val = $lookups['siswa'][$val];
                        $changed = true;
                    } elseif ($key === 'role_id' && isset($lookups['roles'][$lookupKey])) {
                        $val = $lookups['roles'][$lookupKey];
                        $changed = true;
                    } elseif ($key === 'tenant_id' && isset($lookups['tenants'][$val])) {
                        $val = $lookups['tenants'][$val];
                        $changed = true;
                    } elseif ($key === 'id_kelurahan' && isset($lookups['kelurahan'][$lookupKey])) {
                        $val = $lookups['kelurahan'][$lookupKey];
                        $changed = true;
                    }
                }
                unset($val);

                if ($changed) {
                    $log[$field] = json_encode($data);
                }
            }
        }
        unset($log);
    }

    /**
     * Send JSON Response
     */
    protected function jsonResponse(mixed $data, int $statusCode = 200): void {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Get JSON request payload
     */
    protected function getJsonInput(): array {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    /**
     * Render view wrapped inside Master Layout
     */
    protected function render(string $view, array $data = []): void {
        // Extract data so variables are available inside the view files
        extract($data);
        
        // Define content view path
        $contentView = __DIR__ . '/../../views/' . $view . '.php';

        // Load master layout
        require_once __DIR__ . '/../../views/layout/master.php';
    }
}
