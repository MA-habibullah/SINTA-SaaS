<?php

namespace App\Modules\Sistem\Models;

use App\Core\Model;
use PDO;

class PenggunaModel extends Model {

    // Pemetaan role untuk mempermudah identifikasi
    private array $roleMap = [
        'operator' => 'operator_sekolah',
        'guru'     => 'guru',
        'siswa'    => 'siswa',
        'karyawan' => 'karyawan'
    ];

    /**
     * Mengambil data terpaginasi berdasarkan tab/kategori pengguna
     */
    public function getPaginated(string $tab, array $filters = []): array {
        $search = $filters['search'] ?? '';
        $perPage = (int)($filters['per_page'] ?? 10);
        $page = (int)($filters['page'] ?? 1);
        $offset = ($page - 1) * $perPage;
        $trashMode = ($filters['trash'] ?? 'false') === 'true';

        $isSuperAdmin = ($this->tenantId === null);
        $params = [];
        if (!$isSuperAdmin) {
            $params['tenant_id'] = $this->tenantId;
        }

        if ($tab === 'mutasi') {
            // Query untuk Log Mutasi & Putus Sekolah (menggunakan tabel siswa.siswa langsung)
            $selectSql = "SELECT s.id, s.nama_lengkap, s.nisn, s.nis, s.kelas_saat_ini AS nama_kelas,
                                 s.status_siswa AS keluar_karena, s.updated_at AS tanggal_keluar, 'Data status ' || s.status_siswa AS alasan_keluar,
                                 t.nama_sekolah
                          FROM siswa.siswa s
                          LEFT JOIN core.tenants t ON s.tenant_id = t.id";
            $countSql = "SELECT COUNT(*) FROM siswa.siswa s 
                          LEFT JOIN core.tenants t ON s.tenant_id = t.id";
            $whereClause = $isSuperAdmin ? " WHERE s.status_siswa != 'aktif'" : " WHERE s.tenant_id = :tenant_id AND s.status_siswa != 'aktif'";

            if ($isSuperAdmin && !empty($filters['tenant_id'])) {
                $whereClause .= " AND s.tenant_id = :filter_tenant_id";
                $params['filter_tenant_id'] = $filters['tenant_id'];
            }

            // Filter kelas / rombel
            if (!empty($filters['id_kelas'])) {
                $whereClause .= " AND (s.kelas_saat_ini = (SELECT nama_kelas FROM akademik.kelas WHERE id::text = :id_kelas_sub LIMIT 1) OR s.kelas_saat_ini = :id_kelas_str)";
                $params['id_kelas_sub'] = $filters['id_kelas'];
                $params['id_kelas_str'] = $filters['id_kelas'];
            }

            // Mutasi: tidak filter berdasarkan is_active karena siswa mutasi/lulus sudah is_active=false
            // Trash mode tidak berlaku untuk mutasi (data mutasi bukan trash)

            if ($search !== '') {
                $whereClause .= " AND (s.nama_lengkap ILIKE :search_nama OR s.nisn ILIKE :search_nisn OR s.nis ILIKE :search_nis";
                if ($isSuperAdmin) {
                    $whereClause .= " OR t.nama_sekolah ILIKE :search_sekolah";
                }
                $whereClause .= ")";
                $params['search_nama'] = "%" . $search . "%";
                $params['search_nisn'] = "%" . $search . "%";
                $params['search_nis'] = "%" . $search . "%";
                if ($isSuperAdmin) {
                    $params['search_sekolah'] = "%" . $search . "%";
                }
            }

            $orderBy = " ORDER BY s.updated_at DESC, s.nama_lengkap ASC";

        } elseif ($tab === 'siswa') {
            // Query untuk Siswa (menggunakan tabel siswa.siswa langsung)
            $selectSql = "SELECT s.*, t.nama_sekolah,
                                 COALESCE(
                                     j.nama_jenjang,
                                     (SELECT j2.nama_jenjang FROM core.jenjang j2 WHERE (j2.id::text = k.id_jenjang::text OR j2.kode_jenjang ILIKE k.id_jenjang OR j2.nama_jenjang ILIKE k.id_jenjang) LIMIT 1),
                                     CASE 
                                         WHEN t.nama_sekolah ILIKE '%SMA%' THEN 'Sekolah Menengah Atas'
                                         WHEN t.nama_sekolah ILIKE '%SMK%' THEN 'Sekolah Menengah Kejuruan'
                                         WHEN t.nama_sekolah ILIKE '%SMP%' THEN 'Sekolah Menengah Pertama'
                                         WHEN t.nama_sekolah ILIKE '%SD%' THEN 'Sekolah Dasar'
                                         WHEN s.kelas_saat_ini ILIKE '%X%' OR s.kelas_saat_ini ILIKE '%XI%' OR s.kelas_saat_ini ILIKE '%XII%' THEN 'Sekolah Menengah Atas'
                                         WHEN s.kelas_saat_ini ILIKE '%VII%' OR s.kelas_saat_ini ILIKE '%VIII%' OR s.kelas_saat_ini ILIKE '%IX%' THEN 'Sekolah Menengah Pertama'
                                         ELSE 'Sekolah Menengah Atas'
                                     END
                                 ) AS nama_jenjang,
                                 CASE 
                                     WHEN s.status_siswa = 'aktif' THEN 'Aktif'
                                     WHEN s.status_siswa = 'lulus' THEN 'Lulus'
                                     WHEN s.status_siswa = 'pindah' THEN 'Pindah'
                                     WHEN s.status_siswa = 'putus_sekolah' THEN 'Putus Sekolah'
                                     ELSE INITCAP(COALESCE(s.status_siswa, 'Aktif'))
                                 END AS status,
                                 COALESCE(k.nama_kelas, s.kelas_saat_ini, '-') AS nama_kelas
                          FROM siswa.siswa s
                          LEFT JOIN core.tenants t ON s.tenant_id = t.id
                          LEFT JOIN akademik.kelas k ON (s.tenant_id = k.tenant_id AND (s.kelas_saat_ini = k.id::text OR s.kelas_saat_ini = k.nama_kelas OR s.kelas_saat_ini = k.kode_kelas))
                          LEFT JOIN core.jenjang j ON k.id_jenjang::text = j.id::text";
            $countSql = "SELECT COUNT(*) FROM siswa.siswa s 
                          LEFT JOIN core.tenants t ON s.tenant_id = t.id
                          LEFT JOIN akademik.kelas k ON (s.tenant_id = k.tenant_id AND (s.kelas_saat_ini = k.id::text OR s.kelas_saat_ini = k.nama_kelas OR s.kelas_saat_ini = k.kode_kelas))
                          LEFT JOIN core.jenjang j ON k.id_jenjang::text = j.id::text";
            $whereClause = $isSuperAdmin ? " WHERE 1=1" : " WHERE s.tenant_id = :tenant_id";

            if ($isSuperAdmin && !empty($filters['tenant_id'])) {
                $whereClause .= " AND s.tenant_id = :filter_tenant_id";
                $params['filter_tenant_id'] = $filters['tenant_id'];
            }

            if (isset($filters['siswa_id'])) {
                $whereClause .= " AND s.id = :siswa_id";
                $params['siswa_id'] = $filters['siswa_id'];
            } elseif (isset($filters['user_id'])) {
                $whereClause .= " AND s.id = :user_id";
                $params['user_id'] = $filters['user_id'];
            }

            // Filter status: default to 'aktif'
            $status = $filters['status'] ?? 'aktif';
            if (empty($status)) {
                $status = 'aktif';
            }
            $whereClause .= " AND LOWER(s.status_siswa) = LOWER(:status)";
            $params['status'] = $status;

            // Filter jenjang
            if (!empty($filters['id_jenjang'])) {
                $whereClause .= " AND (k.id_jenjang::text = :id_jenjang OR j.kode_jenjang = :id_jenjang_kode OR j.id::text = :id_jenjang_jid)";
                $params['id_jenjang'] = $filters['id_jenjang'];
                $params['id_jenjang_kode'] = $filters['id_jenjang'];
                $params['id_jenjang_jid'] = $filters['id_jenjang'];
            }

            // Filter kelas / rombel
            if (!empty($filters['id_kelas'])) {
                $whereClause .= " AND (k.id::text = :id_kelas OR s.kelas_saat_ini = (SELECT nama_kelas FROM akademik.kelas WHERE id::text = :id_kelas_sub LIMIT 1) OR s.kelas_saat_ini = :id_kelas_str)";
                $params['id_kelas'] = $filters['id_kelas'];
                $params['id_kelas_sub'] = $filters['id_kelas'];
                $params['id_kelas_str'] = $filters['id_kelas'];
            }

            if ($trashMode) {
                $whereClause .= " AND s.is_active = false";
            } else {
                $whereClause .= " AND s.is_active = true";
            }

            if ($search !== '') {
                $whereClause .= " AND (s.nama_lengkap ILIKE :search_nama OR s.nisn ILIKE :search_nisn OR s.nis ILIKE :search_nis";
                if ($isSuperAdmin) {
                    $whereClause .= " OR t.nama_sekolah ILIKE :search_sekolah";
                }
                $whereClause .= ")";
                $params['search_nama'] = "%" . $search . "%";
                $params['search_nisn'] = "%" . $search . "%";
                $params['search_nis'] = "%" . $search . "%";
                if ($isSuperAdmin) {
                    $params['search_sekolah'] = "%" . $search . "%";
                }
            }

            $orderBy = " ORDER BY s.nama_lengkap ASC";

        } else {
            // Query untuk staff (Guru, Karyawan, Operator) dari tabel users
            $roleName = $this->roleMap[$tab] ?? '';
            $selectSql = "SELECT u.id, u.nama_lengkap, u.email, u.is_active,
                            u.created_at, u.updated_at, r.nama_role,
                            u.nip, u.nuptk, u.jenis_gtk, u.jabatan_struktural, u.status_kepegawaian,
                            u.jam_mengajar, u.status_sertifikasi, u.no_hp, u.alamat, u.jenis_kelamin,
                            (
                                SELECT COALESCE(json_agg(json_build_object('id', sub_r.id, 'nama_role', sub_r.nama_role, 'deskripsi', sub_r.deskripsi)), '[]'::json)
                                FROM core.user_roles ur
                                JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND sub_r.id != u.role_id
                            ) AS secondary_roles,
                            EXISTS(
                                SELECT 1 FROM core.user_roles ur 
                                INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND (sub_r.nama_role = 'bk' OR sub_r.nama_role = 'guru_bk')
                            ) AS is_bk,
                            EXISTS(
                                SELECT 1 FROM core.user_roles ur 
                                INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND sub_r.nama_role = 'kesiswaan'
                            ) AS is_kesiswaan,
                            EXISTS(
                                SELECT 1 FROM core.user_roles ur 
                                INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND sub_r.nama_role = 'humas'
                            ) AS is_humas,
                            EXISTS(
                                SELECT 1 FROM core.user_roles ur 
                                INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND sub_r.nama_role = 'kurikulum'
                            ) AS is_kurikulum,
                            EXISTS(
                                SELECT 1 FROM core.user_roles ur 
                                INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND sub_r.nama_role = 'sarpras'
                            ) AS is_sarpras,
                            EXISTS(
                                SELECT 1 FROM core.user_roles ur 
                                INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND sub_r.nama_role = 'wali_kelas'
                            ) AS is_wali_kelas,
                            EXISTS(
                                SELECT 1 FROM core.user_roles ur 
                                INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND sub_r.nama_role = 'pembina_ekskul'
                            ) AS is_pembina_ekskul,
                            EXISTS(
                                SELECT 1 FROM core.user_roles ur 
                                INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND (sub_r.nama_role = 'keuangan' OR sub_r.nama_role = 'bendahara')
                            ) AS is_keuangan,
                            EXISTS(
                                SELECT 1 FROM core.user_roles ur 
                                INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                                WHERE ur.user_id = u.id AND sub_r.nama_role = 'perpustakaan'
                            ) AS is_perpustakaan";
            
            if ($isSuperAdmin) {
                $selectSql .= ", (SELECT nama_sekolah FROM core.tenants t WHERE t.id = u.tenant_id LIMIT 1) AS nama_sekolah";
            }

            $selectSql .= " FROM core.users u
                      INNER JOIN core.roles r ON u.role_id = r.id";
            $countSql = "SELECT COUNT(*) FROM core.users u INNER JOIN core.roles r ON u.role_id = r.id";
            $whereClause = $isSuperAdmin ? " WHERE r.nama_role = :role_name" : " WHERE u.tenant_id = :tenant_id AND r.nama_role = :role_name";
            $params['role_name'] = $roleName;

            if ($isSuperAdmin && !empty($filters['tenant_id'])) {
                $whereClause .= " AND u.tenant_id = :filter_tenant_id";
                $params['filter_tenant_id'] = $filters['tenant_id'];
            }

            if (!empty($filters['jenis_gtk'])) {
                $whereClause .= " AND u.jenis_gtk = :jenis_gtk";
                $params['jenis_gtk'] = $filters['jenis_gtk'];
            }

            if (!empty($filters['status_kepegawaian'])) {
                $whereClause .= " AND u.status_kepegawaian = :status_kepegawaian";
                $params['status_kepegawaian'] = $filters['status_kepegawaian'];
            }

            if ($trashMode) {
                $whereClause .= " AND u.is_active = false";
            } else {
                $whereClause .= " AND u.is_active = true";
            }

            if ($search !== '') {
                $whereClause .= " AND (u.nama_lengkap ILIKE :search_nama OR u.email ILIKE :search_email OR u.nip ILIKE :search_nip OR u.nuptk ILIKE :search_nuptk)";
                $params['search_nama'] = "%" . $search . "%";
                $params['search_email'] = "%" . $search . "%";
                $params['search_nip'] = "%" . $search . "%";
                $params['search_nuptk'] = "%" . $search . "%";
            }

            $orderBy = " ORDER BY u.nama_lengkap ASC";
        }

        // Hitung total data
        $countStmt = $this->db->prepare($countSql . $whereClause);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Query data terpaginasi
        $limitClause = " LIMIT :limit OFFSET :offset";
        $dataStmt = $this->db->prepare($selectSql . $whereClause . $orderBy . $limitClause);

        // Bind parameters dynamically based on type
        foreach ($params as $key => $val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $dataStmt->bindValue(':' . $key, $val, $type);
        }
        $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $dataStmt->execute();
        $list = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($tab === 'siswa') {
            foreach ($list as &$row) {
                $fieldsToCheck = [
                    'nisn', 'nis', 'nama_lengkap', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 
                    'agama', 'foto_url', 'email', 'no_hp', 'alamat', 'kelas_saat_ini', 'jurusan',
                    'angkatan', 'tahun_masuk', 'status_siswa'
                ];
                $filled = 0;
                $totalFields = count($fieldsToCheck);
                foreach ($fieldsToCheck as $f) {
                    $val = $row[$f] ?? '';
                    if ($f === 'kontak_email' && empty($val)) {
                        $val = $row['email'] ?? '';
                    }
                    if (trim((string)$val) !== '') {
                        $filled++;
                    }
                }
                $row['persentase_kelengkapan'] = round(($filled / $totalFields) * 100);
            }
        } else {
            foreach ($list as &$row) {
                if (isset($row['secondary_roles']) && is_string($row['secondary_roles'])) {
                    $row['secondary_roles'] = json_decode($row['secondary_roles'], true) ?: [];
                }
            }
        }

        $totalPages = ceil($total / $perPage);
        $from = $total > 0 ? $offset + 1 : 0;
        $to = min($offset + $perPage, $total);

        return [
            'data' => $list,
            'current_page' => $page,
            'last_page' => $totalPages,
            'per_page' => $perPage,
            'total' => $total,
            'from' => $from,
            'to' => $to
        ];
    }

    /**
     * Ambil data detail pengguna berdasarkan ID
     */
    public function findById(string $tab, string $id): ?array {
        $isSuperAdmin = ($this->tenantId === null);
        
        if ($tab === 'siswa' || $tab === 'mutasi') {
            $sql = "SELECT s.* 
                    FROM siswa.siswa s
                    WHERE s.id = :id";
            if (!$isSuperAdmin) {
                $sql .= " AND s.tenant_id = :tenant_id";
            }
        } else {
            $sql = "SELECT u.*, r.nama_role,
                           (
                               SELECT COALESCE(json_agg(json_build_object('id', sub_r.id, 'nama_role', sub_r.nama_role, 'deskripsi', sub_r.deskripsi)), '[]'::json)
                               FROM core.user_roles ur
                               JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND sub_r.id != u.role_id
                           ) AS secondary_roles,
                           (
                               SELECT COALESCE(json_agg(sub_r.nama_role), '[]'::json)
                               FROM core.user_roles ur
                               JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id
                           ) AS assigned_roles,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND (sub_r.nama_role = 'bk' OR sub_r.nama_role = 'guru_bk')
                           ) AS is_bk,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND sub_r.nama_role = 'kesiswaan'
                           ) AS is_kesiswaan,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND sub_r.nama_role = 'humas'
                           ) AS is_humas,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND sub_r.nama_role = 'kurikulum'
                           ) AS is_kurikulum,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND sub_r.nama_role = 'sarpras'
                           ) AS is_sarpras,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND sub_r.nama_role = 'wali_kelas'
                           ) AS is_wali_kelas,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND sub_r.nama_role = 'pembina_ekskul'
                           ) AS is_pembina_ekskul,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND (sub_r.nama_role = 'keuangan' OR sub_r.nama_role = 'bendahara')
                           ) AS is_keuangan,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND sub_r.nama_role = 'perpustakaan'
                           ) AS is_perpustakaan
                    FROM core.users u
                    JOIN core.roles r ON u.role_id = r.id
                    WHERE u.id::text = :id";
            if (!$isSuperAdmin) {
                $sql .= " AND u.tenant_id = :tenant_id";
            }
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $params = ['id' => $id];
        if (!$isSuperAdmin) {
            $params['tenant_id'] = $this->tenantId;
        }

        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($result && isset($result['secondary_roles']) && is_string($result['secondary_roles'])) {
            $result['secondary_roles'] = json_decode($result['secondary_roles'], true) ?: [];
        }
        if ($result && isset($result['assigned_roles']) && is_string($result['assigned_roles'])) {
            $result['assigned_roles'] = json_decode($result['assigned_roles'], true) ?: [];
        }
        return $result;
    }

    /**
     * Membuat data Siswa baru beserta akun User-nya secara transaksional
     */
    public function createSiswa(array $data): string {
        $siswaId = $this->generateUuidV4();
        $userId = null;

        try {
            $this->db->beginTransaction();

            // 1. Jika email diinput, buat akun user terlebih dahulu
            if (!empty($data['email'])) {
                $userId = $this->generateUuidV4();
                $hashedPassword = password_hash($data['password'] ?? 'siswa123', PASSWORD_ARGON2ID);
                
                $userSql = "INSERT INTO core.users (id, tenant_id, role_id, nama_lengkap, email, password_hash, is_active) 
                            VALUES (:id, :tenant_id, :role_id, :nama_lengkap, :email, :password_hash, true)";
                $userStmt = $this->db->prepare($userSql);
                $userStmt->execute([
                    'id' => $userId,
                    'tenant_id' => $this->tenantId,
                    'role_id' => 4, // siswa
                    'nama_lengkap' => $data['nama_lengkap'],
                    'email' => strtolower(trim($data['email'])),
                    'password_hash' => $hashedPassword
                ]);
            }

            // 2. Buat data siswa
            $siswaSql = "INSERT INTO siswa.siswa (
                            id, tenant_id, user_id, nisn, nis, nama_lengkap, 
                            tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, nama_wali, kontak_wali
                         ) VALUES (
                            :id, :tenant_id, :user_id, :nisn, :nis, :nama_lengkap, 
                            :tempat_lahir, :tanggal_lahir, :jenis_kelamin, :alamat, :nama_wali, :kontak_wali
                         )";
            $siswaStmt = $this->db->prepare($siswaSql);
            $siswaStmt->execute([
                'id' => $siswaId,
                'tenant_id' => $this->tenantId,
                'user_id' => $userId,
                'nisn' => !empty($data['nisn']) ? $data['nisn'] : null,
                'nis' => !empty($data['nis']) ? $data['nis'] : null,
                'nama_lengkap' => strip_tags(trim($data['nama_lengkap'])),
                'tempat_lahir' => !empty($data['tempat_lahir']) ? strip_tags(trim($data['tempat_lahir'])) : null,
                'tanggal_lahir' => !empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null,
                'jenis_kelamin' => $data['jenis_kelamin'],
                'alamat' => !empty($data['alamat']) ? strip_tags(trim($data['alamat'])) : null,
                'nama_wali' => !empty($data['nama_wali']) ? strip_tags(trim($data['nama_wali'])) : null,
                'kontak_wali' => !empty($data['kontak_wali']) ? strip_tags(trim($data['kontak_wali'])) : null
            ]);

            $this->db->commit();
            return $siswaId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Memperbarui data Siswa beserta akun User-nya secara transaksional
     */
    public function updateSiswa(string $id, array $data): bool {
        try {
            $this->db->beginTransaction();

            // Ambil data siswa lama untuk mengecek user_id
            $oldSiswa = $this->findById('siswa', $id);
            if (!$oldSiswa) {
                $this->db->rollBack();
                return false;
            }

            $userId = $oldSiswa['user_id'];

            // 1. Kelola akun user pendukung
            if (!empty($data['email'])) {
                if ($userId === null) {
                    // Buat akun baru jika sebelumnya tidak ada
                    $userId = $this->generateUuidV4();
                    $hashedPassword = password_hash($data['password'] ?? 'siswa123', PASSWORD_ARGON2ID);
                    
                    $userSql = "INSERT INTO core.users (id, tenant_id, role_id, nama_lengkap, email, password_hash, is_active) 
                                VALUES (:id, :tenant_id, :role_id, :nama_lengkap, :email, :password_hash, true)";
                    $userStmt = $this->db->prepare($userSql);
                    $userStmt->execute([
                        'id' => $userId,
                        'tenant_id' => $this->tenantId,
                        'role_id' => 4,
                        'nama_lengkap' => $data['nama_lengkap'],
                        'email' => strtolower(trim($data['email'])),
                        'password_hash' => $hashedPassword
                    ]);

                    // Link user_id ke siswa
                    $linkSql = "UPDATE siswa.siswa SET user_id = :user_id WHERE id = :id";
                    $linkStmt = $this->db->prepare($linkSql);
                    $linkStmt->execute(['user_id' => $userId, 'id' => $id]);
                } else {
                    // Update akun user yang sudah ada
                    $userSql = "UPDATE core.users SET nama_lengkap = :nama_lengkap, email = :email";
                    $userParams = [
                        'nama_lengkap' => $data['nama_lengkap'],
                        'email' => strtolower(trim($data['email'])),
                        'id' => $userId
                    ];

                    if (!empty($data['password'])) {
                        $userSql .= ", password_hash = :password_hash";
                        $userParams['password_hash'] = password_hash($data['password'], PASSWORD_ARGON2ID);
                    }

                    $userSql .= " WHERE id = :id";
                    $userStmt = $this->db->prepare($userSql);
                    $userStmt->execute($userParams);
                }
            }

            // 2. Update data siswa
            $siswaSql = "UPDATE siswa.siswa SET 
                            nisn = :nisn, 
                            nis = :nis, 
                            nama_lengkap = :nama_lengkap, 
                            tempat_lahir = :tempat_lahir, 
                            tanggal_lahir = :tanggal_lahir, 
                            jenis_kelamin = :jenis_kelamin, 
                            alamat = :alamat, 
                            nama_wali = :nama_wali, 
                            kontak_wali = :kontak_wali
                         WHERE id = :id";
            if ($this->tenantId !== null) {
                $siswaSql .= " AND tenant_id = :tenant_id";
            }
            
            $siswaStmt = $this->db->prepare($siswaSql);
            $siswaParams = [
                'id' => $id,
                'nisn' => !empty($data['nisn']) ? $data['nisn'] : null,
                'nis' => !empty($data['nis']) ? $data['nis'] : null,
                'nama_lengkap' => strip_tags(trim($data['nama_lengkap'])),
                'tempat_lahir' => !empty($data['tempat_lahir']) ? strip_tags(trim($data['tempat_lahir'])) : null,
                'tanggal_lahir' => !empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null,
                'jenis_kelamin' => $data['jenis_kelamin'],
                'alamat' => !empty($data['alamat']) ? strip_tags(trim($data['alamat'])) : null,
                'nama_wali' => !empty($data['nama_wali']) ? strip_tags(trim($data['nama_wali'])) : null,
                'kontak_wali' => !empty($data['kontak_wali']) ? strip_tags(trim($data['kontak_wali'])) : null
            ];
            if ($this->tenantId !== null) {
                $siswaParams['tenant_id'] = $this->tenantId;
            }

            $siswaStmt->execute($siswaParams);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Membuat data Staff (Guru, Karyawan, Operator) baru
     */
    public function createStaff(string $tab, array $data): string {
        $userId = $this->generateUuidV4();
        $roleName = $this->roleMap[$tab] ?? '';
        $roleId = $this->db->query("SELECT id FROM core.roles WHERE nama_role = '$roleName'")->fetchColumn() ?: 0;
        $hashedPassword = password_hash($data['password'] ?? 'staff123', PASSWORD_ARGON2ID);

        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO core.users (
                        id, tenant_id, role_id, nama_lengkap, email, password_hash, is_active,
                        nip, nuptk, jenis_gtk, jabatan_struktural, status_kepegawaian, jam_mengajar,
                        status_sertifikasi, no_hp, alamat, jenis_kelamin
                    ) VALUES (
                        :id, :tenant_id, :role_id, :nama_lengkap, :email, :password_hash, true,
                        :nip, :nuptk, :jenis_gtk, :jabatan_struktural, :status_kepegawaian, :jam_mengajar,
                        :status_sertifikasi, :no_hp, :alamat, :jenis_kelamin
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $userId,
                'tenant_id' => $this->tenantId,
                'role_id' => $roleId,
                'nama_lengkap' => strip_tags(trim($data['nama_lengkap'] ?? '')),
                'email' => strtolower(trim($data['email'] ?? '')),
                'password_hash' => $hashedPassword,
                'nip' => !empty($data['nip']) ? trim($data['nip']) : null,
                'nuptk' => !empty($data['nuptk']) ? trim($data['nuptk']) : null,
                'jenis_gtk' => !empty($data['jenis_gtk']) ? trim($data['jenis_gtk']) : ($tab === 'guru' ? 'Guru' : 'Tenaga Kependidikan'),
                'jabatan_struktural' => !empty($data['jabatan_struktural']) ? trim($data['jabatan_struktural']) : null,
                'status_kepegawaian' => !empty($data['status_kepegawaian']) ? trim($data['status_kepegawaian']) : 'GTY/PTY',
                'jam_mengajar' => isset($data['jam_mengajar']) ? (int)$data['jam_mengajar'] : 0,
                'status_sertifikasi' => (!empty($data['status_sertifikasi']) && $data['status_sertifikasi'] !== 'false') ? 'true' : 'false',
                'no_hp' => !empty($data['no_hp']) ? trim($data['no_hp']) : null,
                'alamat' => !empty($data['alamat']) ? trim($data['alamat']) : null,
                'jenis_kelamin' => !empty($data['jenis_kelamin']) ? trim($data['jenis_kelamin']) : null
            ]);

            // Tulis role utama ke user_roles
            $urSql = "INSERT INTO core.user_roles (user_id, role_id) VALUES (:user_id::uuid, :role_id::uuid) ON CONFLICT DO NOTHING";
            $urStmt = $this->db->prepare($urSql);
            $urStmt->execute([
                'user_id' => $userId,
                'role_id' => $roleId
            ]);

            $this->syncStaffRoles($userId, $data, $tab);

            $this->db->commit();
            return $userId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Memperbarui data Staff
     */
    public function updateStaff(string $tab, string $id, array $data): bool {
        $params = [
            'id' => $id,
            'nama_lengkap' => strip_tags(trim($data['nama_lengkap'] ?? '')),
            'email' => strtolower(trim($data['email'] ?? '')),
            'nip' => !empty($data['nip']) ? trim($data['nip']) : null,
            'nuptk' => !empty($data['nuptk']) ? trim($data['nuptk']) : null,
            'jenis_gtk' => !empty($data['jenis_gtk']) ? trim($data['jenis_gtk']) : ($tab === 'guru' ? 'Guru' : 'Tenaga Kependidikan'),
            'jabatan_struktural' => !empty($data['jabatan_struktural']) ? trim($data['jabatan_struktural']) : null,
            'status_kepegawaian' => !empty($data['status_kepegawaian']) ? trim($data['status_kepegawaian']) : 'GTY/PTY',
            'jam_mengajar' => isset($data['jam_mengajar']) ? (int)$data['jam_mengajar'] : 0,
            'status_sertifikasi' => (!empty($data['status_sertifikasi']) && $data['status_sertifikasi'] !== 'false') ? 'true' : 'false',
            'no_hp' => !empty($data['no_hp']) ? trim($data['no_hp']) : null,
            'alamat' => !empty($data['alamat']) ? trim($data['alamat']) : null,
            'jenis_kelamin' => !empty($data['jenis_kelamin']) ? trim($data['jenis_kelamin']) : null
        ];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }

        $sql = "UPDATE core.users SET 
                    nama_lengkap = :nama_lengkap, 
                    email = :email,
                    nip = :nip,
                    nuptk = :nuptk,
                    jenis_gtk = :jenis_gtk,
                    jabatan_struktural = :jabatan_struktural,
                    status_kepegawaian = :status_kepegawaian,
                    jam_mengajar = :jam_mengajar,
                    status_sertifikasi = :status_sertifikasi,
                    no_hp = :no_hp,
                    alamat = :alamat,
                    jenis_kelamin = :jenis_kelamin";

        if (!empty($data['password'])) {
            $sql .= ", password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        $sql .= " WHERE id::text = :id";
        if ($this->tenantId !== null) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);

            // Tulis/sinkronisasikan role utama ke user_roles
            $roleName = $this->roleMap[$tab] ?? '';
            $stRole = $this->db->prepare("SELECT id::text FROM core.roles WHERE nama_role = ? LIMIT 1");
            $stRole->execute([$roleName]);
            $roleId = $stRole->fetchColumn();
            if ($roleId) {
                $urSql = "INSERT INTO core.user_roles (user_id, role_id) VALUES (:user_id::uuid, :role_id::uuid) ON CONFLICT DO NOTHING";
                $urStmt = $this->db->prepare($urSql);
                $urStmt->execute([
                    'user_id' => $id,
                    'role_id' => $roleId
                ]);
            }

            $this->syncStaffRoles($id, $data, $tab);

            $this->db->commit();
            return $success;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Helper sinkronisasi multi-role / tugas tambahan staff ke core.user_roles
     */
    private function syncStaffRoles(string $userId, array $data, string $tab): void {
        $getRoleIdByName = function(string $rName): ?string {
            $st = $this->db->prepare("SELECT id::text FROM core.roles WHERE nama_role = ? OR nama_role ILIKE ? LIMIT 1");
            $st->execute([$rName, "%$rName%"]);
            return $st->fetchColumn() ?: null;
        };

        $syncUserRole = function(string $uId, string $rName, bool $enable) use ($getRoleIdByName) {
            $rId = $getRoleIdByName($rName);
            if ($rId) {
                if ($enable) {
                    $st = $this->db->prepare("INSERT INTO core.user_roles (user_id, role_id) VALUES (:user_id::uuid, :role_id::uuid) ON CONFLICT DO NOTHING");
                    $st->execute(['user_id' => $uId, 'role_id' => $rId]);
                } else {
                    $st = $this->db->prepare("DELETE FROM core.user_roles WHERE user_id::text = :user_id AND role_id::text = :role_id");
                    $st->execute(['user_id' => $uId, 'role_id' => $rId]);
                }
            }
        };

        // Multi-role array explicit jika dikirim
        if (isset($data['assigned_roles']) && is_array($data['assigned_roles'])) {
            $roleList = $data['assigned_roles'];
            $allRoles = ['bk', 'guru_bk', 'kesiswaan', 'humas', 'kurikulum', 'sarpras', 'wali_kelas', 'pembina_ekskul', 'keuangan', 'perpustakaan'];
            foreach ($allRoles as $r) {
                $syncUserRole($userId, $r, in_array($r, $roleList));
            }
        } else {
            // Sinkronisasi berbasis flag individual
            $syncUserRole($userId, 'bk', !empty($data['is_bk']));
            $syncUserRole($userId, 'kesiswaan', !empty($data['is_kesiswaan']));
            $syncUserRole($userId, 'humas', !empty($data['is_humas']));
            $syncUserRole($userId, 'kurikulum', !empty($data['is_kurikulum']));
            $syncUserRole($userId, 'sarpras', !empty($data['is_sarpras']));
            $syncUserRole($userId, 'wali_kelas', !empty($data['is_wali_kelas']));
            $syncUserRole($userId, 'pembina_ekskul', !empty($data['is_pembina_ekskul']));
            $syncUserRole($userId, 'keuangan', !empty($data['is_keuangan']));
            $syncUserRole($userId, 'perpustakaan', !empty($data['is_perpustakaan']));
        }
    }

    /**
     * Soft Delete Pengguna
     */
    public function delete(string $tab, string $id): bool {
        try {
            $this->db->beginTransaction();
            $params = ['id' => $id];
            if ($this->tenantId !== null) {
                $params['tenant_id'] = $this->tenantId;
            }

            if ($tab === 'siswa' || $tab === 'mutasi') {
                // Hapus data siswa (soft-delete via is_active)
                $sql = "UPDATE siswa.siswa SET is_active = false, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                if ($this->tenantId !== null) {
                    $sql .= " AND tenant_id = :tenant_id";
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                // Jika siswa memiliki akun user, hapus juga akun user-nya
                $siswa = $this->findById($tab, $id);
                if ($siswa && $siswa['user_id']) {
                    $userSql = "UPDATE core.users SET deleted_at = CURRENT_TIMESTAMP WHERE id = :user_id";
                    $userStmt = $this->db->prepare($userSql);
                    $userStmt->execute(['user_id' => $siswa['user_id']]);
                }
            } else {
                // Hapus data staff (soft-delete via is_active)
                $sql = "UPDATE core.users SET is_active = false, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                if ($this->tenantId !== null) {
                    $sql .= " AND tenant_id = :tenant_id";
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Memulihkan PenggunaModel dari Tempat Sampah (Restore)
     */
    public function restore(string $tab, string $id): bool {
        try {
            $this->db->beginTransaction();
            $params = ['id' => $id];
            if ($this->tenantId !== null) {
                $params['tenant_id'] = $this->tenantId;
            }

            if ($tab === 'siswa' || $tab === 'mutasi') {
                // Pulihkan data siswa (restore via is_active)
                $sql = "UPDATE siswa.siswa SET is_active = true, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                if ($this->tenantId !== null) {
                    $sql .= " AND tenant_id = :tenant_id";
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                // Jika siswa memiliki akun user, pulihkan juga akun user-nya
                $siswa = $this->findById($tab, $id);
                if ($siswa && $siswa['user_id']) {
                    $userSql = "UPDATE core.users SET deleted_at = NULL WHERE id = :user_id";
                    $userStmt = $this->db->prepare($userSql);
                    $userStmt->execute(['user_id' => $siswa['user_id']]);
                }
            } else {
                // Pulihkan data staff (restore via is_active)
                $sql = "UPDATE core.users SET is_active = true, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                if ($this->tenantId !== null) {
                    $sql .= " AND tenant_id = :tenant_id";
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Toggle status keaktifan user
     */
    public function toggleStatus(string $tab, string $id): bool {
        try {
            $this->db->beginTransaction();
            
            $userId = null;
            if ($tab === 'siswa' || $tab === 'mutasi') {
                $siswa = $this->findById($tab, $id);
                if ($siswa) {
                    $userId = $siswa['user_id'];
                }
            } else {
                $userId = $id;
            }

            if (!$userId) {
                $this->db->rollBack();
                return false; // Siswa tidak memiliki akun user
            }

            // Ambil status saat ini
            $sql = "SELECT is_active FROM core.users WHERE id::text = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->db->rollBack();
                return false;
            }

            $isCurrentActive = ($user['is_active'] === true || $user['is_active'] == 't' || $user['is_active'] === '1' || $user['is_active'] == 1);
            $newStatusStr = $isCurrentActive ? 'false' : 'true';

            // Update status
            $updateSql = "UPDATE core.users SET is_active = :is_active::boolean WHERE id::text = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $success = $updateStmt->execute([
                'is_active' => $newStatusStr,
                'id' => $userId
            ]);

            $this->db->commit();
            return $success;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Memeriksa keunikan email di users per tenant
     */
    public function isEmailUnique(string $email, ?string $excludeId = null): bool {
        $isSuperAdmin = ($this->tenantId === null);
        
        $sql = "SELECT COUNT(*) FROM core.users WHERE email = :email AND is_active = true";
        if (!$isSuperAdmin) {
            $sql .= " AND tenant_id = :tenant_id";
        }
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $params = ['email' => strtolower(trim($email))];
        if (!$isSuperAdmin) {
            $params['tenant_id'] = $this->tenantId;
        }
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }

        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Memeriksa keunikan NISN secara nasional
     */
    public function isNisnUnique(string $nisn, ?string $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM siswa.siswa WHERE nisn = :nisn AND is_active = true";
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $params = ['nisn' => trim($nisn)];
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }

        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Memeriksa keunikan NIS per tenant
     */
    public function isNisUnique(string $nis, ?string $excludeId = null): bool {
        $isSuperAdmin = ($this->tenantId === null);
        
        $sql = "SELECT COUNT(*) FROM siswa.siswa WHERE nis = :nis AND is_active = true";
        if (!$isSuperAdmin) {
            $sql .= " AND tenant_id = :tenant_id";
        }
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $params = ['nis' => trim($nis)];
        if (!$isSuperAdmin) {
            $params['tenant_id'] = $this->tenantId;
        }
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }

        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Generate standard UUID v4
     */
    private function generateUuidV4(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Ambil daftar semua tenant aktif
     */
    public function getTenants(): array {
        $stmt = $this->db->query("SELECT id, nama_sekolah, npsn FROM core.tenants WHERE status = 'active' ORDER BY nama_sekolah ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // METODE AKSI: NAIKKAN KELAS & LULUSKAN SISWA
    // =========================================================================

    /**
     * Ambil daftar kelas berdasarkan tenant untuk dropdown filter aksi.
     * Jika tenantId null (Super Admin) harus pass tenantId eksplisit.
     */
    public function getKelasForAction(string $tenantId): array {
        $sql = "SELECT id, nama_kelas 
                FROM akademik.kelas 
                WHERE tenant_id = :tenant_id AND is_active = true
                ORDER BY nama_kelas ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil daftar siswa aktif berdasarkan kelas dan tenant (untuk tabel checklist aksi).
     */
    public function getSiswaByKelas(string $idKelas, string $tenantId): array {
        $sql = "SELECT s.id, s.nama_lengkap, s.nisn, s.nis,
                       s.kelas_saat_ini AS nama_kelas
                FROM siswa.siswa s
                WHERE s.tenant_id = :tenant_id
                  AND s.kelas_saat_ini = (SELECT nama_kelas FROM akademik.kelas WHERE id = :id_kelas LIMIT 1)
                  AND s.status_siswa = 'aktif'
                  AND s.is_active = true
                ORDER BY s.nama_lengkap ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId, 'id_kelas' => $idKelas]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Naikkan kelas siswa secara massal (atomik: UPDATE siswa + INSERT riwayat).
     *
     * @param  array  $siswaIds      Array UUID siswa yang dipilih
     * @param  string $idKelasTujuan ID kelas tujuan (UUID)
     * @param  string $tenantId      Tenant ID (dikunci dari session di Controller)
     * @param  array  $auditData     ['tahun_ajaran', 'dilakukan_oleh', 'nama_pelaku', 'catatan']
     * @return int                   Jumlah siswa yang berhasil diproses
     */
    public function naikkanKelas(array $siswaIds, string $idKelasTujuan, string $tenantId, array $auditData): int {
        if (empty($siswaIds)) return 0;

        // Sanitasi: pastikan hanya siswa yang benar-benar milik tenant ini
        $placeholders = implode(',', array_fill(0, count($siswaIds), '?'));
        $checkSql = "SELECT id, kelas_saat_ini AS nama_kelas_asal
                     FROM siswa.siswa
                     WHERE id IN ({$placeholders})
                       AND tenant_id = ?
                       AND status_siswa = 'aktif'
                       AND is_active = true";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([...$siswaIds, $tenantId]);
        $validRows = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($validRows)) return 0;

        // Nama kelas tujuan 
        $kelasStmt = $this->db->prepare("SELECT nama_kelas FROM akademik.kelas WHERE id = ? LIMIT 1");
        $kelasStmt->execute([$idKelasTujuan]);
        $kelasTujuanRow = $kelasStmt->fetch(PDO::FETCH_ASSOC);
        $namaKelasTujuan = $kelasTujuanRow['nama_kelas'] ?? '';

        $tahunAjaran   = $auditData['tahun_ajaran']   ?? '';
        $dilakukanOleh = $auditData['dilakukan_oleh'] ?? '';
        $namaPelaku    = $auditData['nama_pelaku']    ?? '';
        $catatan       = $auditData['catatan']        ?? null;

        try {
            $this->db->beginTransaction();

            $updateSql = "UPDATE siswa.siswa SET kelas_saat_ini = :nama_kelas_tujuan
                          WHERE id = :id AND tenant_id = :tenant_id AND is_active = true";
            $updateStmt = $this->db->prepare($updateSql);

            $insertSql = "INSERT INTO siswa.riwayat_kenaikan_kelas
                            (tenant_id, siswa_id, jenis_aksi,
                             nama_kelas_asal, nama_kelas_tujuan, tahun_ajaran,
                             dilakukan_oleh, nama_pelaku, catatan)
                          VALUES
                            (:tenant_id, :siswa_id, 'naik_kelas',
                             :nama_kelas_asal, :nama_kelas_tujuan, :tahun_ajaran,
                             :dilakukan_oleh, :nama_pelaku, :catatan)";
            $insertStmt = $this->db->prepare($insertSql);

            $count = 0;
            foreach ($validRows as $row) {
                $updateStmt->execute([
                    'nama_kelas_tujuan' => $namaKelasTujuan,
                    'id'                => $row['id'],
                    'tenant_id'         => $tenantId
                ]);
                $insertStmt->execute([
                    'tenant_id'          => $tenantId,
                    'siswa_id'           => $row['id'],
                    'nama_kelas_asal'    => $row['nama_kelas_asal'],
                    'nama_kelas_tujuan'  => $namaKelasTujuan,
                    'tahun_ajaran'       => $tahunAjaran,
                    'dilakukan_oleh'     => $dilakukanOleh,
                    'nama_pelaku'        => $namaPelaku,
                    'catatan'            => $catatan
                ]);
                $count++;
            }

            $this->db->commit();
            return $count;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function tinggalKelas(array $siswaIds, string $idKelasTujuan, string $tenantId, array $auditData): int {
        if (empty($siswaIds)) return 0;

        $placeholders = implode(',', array_fill(0, count($siswaIds), '?'));
        $checkSql = "SELECT id, kelas_saat_ini AS nama_kelas_asal
                     FROM siswa.siswa
                     WHERE id IN ({$placeholders})
                       AND tenant_id = ?
                       AND status_siswa = 'aktif'
                       AND is_active = true";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([...$siswaIds, $tenantId]);
        $validRows = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($validRows)) return 0;

        $kelasStmt = $this->db->prepare("SELECT nama_kelas FROM akademik.kelas WHERE id = ? LIMIT 1");
        $kelasStmt->execute([$idKelasTujuan]);
        $kelasTujuanRow = $kelasStmt->fetch(PDO::FETCH_ASSOC);
        $namaKelasTujuan = $kelasTujuanRow['nama_kelas'] ?? '';

        $tahunAjaran   = $auditData['tahun_ajaran']   ?? '';
        $dilakukanOleh = $auditData['dilakukan_oleh'] ?? '';
        $namaPelaku    = $auditData['nama_pelaku']    ?? '';
        $catatan       = $auditData['catatan']        ?? null;

        try {
            $this->db->beginTransaction();

            $updateSql = "UPDATE siswa.siswa SET kelas_saat_ini = :nama_kelas_tujuan
                          WHERE id = :id AND tenant_id = :tenant_id AND is_active = true";
            $updateStmt = $this->db->prepare($updateSql);

            $insertSql = "INSERT INTO siswa.riwayat_kenaikan_kelas
                            (tenant_id, siswa_id, jenis_aksi,
                             nama_kelas_asal, nama_kelas_tujuan, tahun_ajaran,
                             dilakukan_oleh, nama_pelaku, catatan)
                          VALUES
                            (:tenant_id, :siswa_id, 'tinggal_kelas',
                             :nama_kelas_asal, :nama_kelas_tujuan, :tahun_ajaran,
                             :dilakukan_oleh, :nama_pelaku, :catatan)";
            $insertStmt = $this->db->prepare($insertSql);

            $count = 0;
            foreach ($validRows as $row) {
                $updateStmt->execute([
                    'nama_kelas_tujuan' => $namaKelasTujuan,
                    'id'                => $row['id'],
                    'tenant_id'         => $tenantId
                ]);
                $insertStmt->execute([
                    'tenant_id'          => $tenantId,
                    'siswa_id'           => $row['id'],
                    'nama_kelas_asal'    => $row['nama_kelas_asal'],
                    'nama_kelas_tujuan'  => $namaKelasTujuan,
                    'tahun_ajaran'       => $tahunAjaran,
                    'dilakukan_oleh'     => $dilakukanOleh,
                    'nama_pelaku'        => $namaPelaku,
                    'catatan'            => $catatan
                ]);
                $count++;
            }

            $this->db->commit();
            return $count;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Luluskan siswa secara massal (atomik: UPDATE siswa status + INSERT riwayat).
     *
     * @param  array  $siswaIds  Array UUID siswa yang dipilih
     * @param  string $tenantId  Tenant ID (dikunci dari session di Controller)
     * @param  array  $auditData ['tahun_ajaran', 'dilakukan_oleh', 'nama_pelaku', 'catatan']
     * @return int               Jumlah siswa yang berhasil diluluskan
     */
    public function luluskanSiswa(array $siswaIds, string $tenantId, array $auditData): int {
        if (empty($siswaIds)) return 0;

        // Sanitasi: pastikan hanya siswa yang benar-benar milik tenant ini
        $placeholders = implode(',', array_fill(0, count($siswaIds), '?'));
        $checkSql = "SELECT id, kelas_saat_ini AS nama_kelas_asal
                     FROM siswa.siswa
                     WHERE id IN ({$placeholders})
                       AND tenant_id = ?
                       AND status_siswa = 'aktif'
                       AND is_active = true";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([...$siswaIds, $tenantId]);
        $validRows = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($validRows)) return 0;

        $tahunAjaran   = $auditData['tahun_ajaran']   ?? '';
        $dilakukanOleh = $auditData['dilakukan_oleh'] ?? '';
        $namaPelaku    = $auditData['nama_pelaku']    ?? '';
        $catatan       = $auditData['catatan']        ?? null;
        $tahunLulus    = (int)date('Y');

        try {
            $this->db->beginTransaction();

            $updateSql = "UPDATE siswa.siswa
                          SET status_siswa = 'Lulus', tahun_lulus = :tahun_lulus
                          WHERE id = :id AND tenant_id = :tenant_id AND is_active = true";
            $updateStmt = $this->db->prepare($updateSql);

            $insertSql = "INSERT INTO siswa.riwayat_kenaikan_kelas
                            (tenant_id, siswa_id, jenis_aksi,
                             nama_kelas_asal, nama_kelas_tujuan, tahun_ajaran,
                             dilakukan_oleh, nama_pelaku, catatan)
                          VALUES
                            (:tenant_id, :siswa_id, 'lulus',
                             :nama_kelas_asal, NULL, :tahun_ajaran,
                             :dilakukan_oleh, :nama_pelaku, :catatan)";
            $insertStmt = $this->db->prepare($insertSql);

            $count = 0;
            foreach ($validRows as $row) {
                $updateStmt->execute([
                    'id' => $row['id'], 
                    'tenant_id' => $tenantId,
                    'tahun_lulus' => $tahunLulus
                ]);
                $insertStmt->execute([
                    'tenant_id'      => $tenantId,
                    'siswa_id'       => $row['id'],
                    'nama_kelas_asal'=> $row['nama_kelas_asal'],
                    'tahun_ajaran'   => $tahunAjaran,
                    'dilakukan_oleh' => $dilakukanOleh,
                    'nama_pelaku'    => $namaPelaku,
                    'catatan'        => $catatan
                ]);
                $count++;
            }

            $this->db->commit();
            return $count;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ambil riwayat kenaikan kelas & kelulusan seorang siswa (untuk detail/histori).
     */
    public function getRiwayatSiswa(string $siswaId, string $tenantId): array {
        $sql = "SELECT r.*
                FROM siswa.riwayat_kenaikan_kelas r
                WHERE r.siswa_id = :siswa_id AND r.tenant_id = :tenant_id
                ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['siswa_id' => $siswaId, 'tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

