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
                $whereClause .= " AND s.kelas_saat_ini = (SELECT nama_kelas FROM akademik.kelas WHERE id = :id_kelas LIMIT 1)";
                $params['id_kelas'] = $filters['id_kelas'];
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
            // Query untuk Siswa (menggunakan tabel siswa.siswa langsung tanpa rincian_alamat/dll)
            $selectSql = "SELECT s.*, t.nama_sekolah,
                                 COALESCE(k.nama_kelas, s.kelas_saat_ini, '-') AS nama_kelas,
                                 COALESCE(j.nama_jenjang, (SELECT j2.nama_jenjang FROM core.jenjang j2 WHERE (j2.id::text = k.id_jenjang::text OR j2.tenant_id = s.tenant_id) LIMIT 1), '-') AS nama_jenjang
                          FROM siswa.siswa s
                          LEFT JOIN core.tenants t ON s.tenant_id = t.id
                          LEFT JOIN akademik.kelas k ON (s.tenant_id = k.tenant_id AND (s.kelas_saat_ini = k.id::text OR s.kelas_saat_ini = k.nama_kelas OR s.kelas_saat_ini = k.kode_kelas))
                          LEFT JOIN core.jenjang j ON k.id_jenjang::text = j.id::text";
            $countSql = "SELECT COUNT(*) FROM siswa.siswa s 
                          LEFT JOIN core.tenants t ON s.tenant_id = t.id
                          LEFT JOIN akademik.kelas k ON (s.tenant_id = k.tenant_id AND (s.kelas_saat_ini = k.id::text OR s.kelas_saat_ini = k.nama_kelas OR s.kelas_saat_ini = k.kode_kelas))";
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
            $whereClause .= " AND s.status_siswa = :status";
            $params['status'] = strtolower($status);

            // Filter kelas / rombel
            if (!empty($filters['id_kelas'])) {
                $whereClause .= " AND s.kelas_saat_ini = (SELECT nama_kelas FROM akademik.kelas WHERE id = :id_kelas LIMIT 1)";
                $params['id_kelas'] = $filters['id_kelas'];
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
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               INNER JOIN core.roles sub_r ON ur.role_id = sub_r.id
                               WHERE ur.user_id = u.id AND sub_r.nama_role = 'bk'
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
                           ) AS is_sarpras";
            
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

            if ($trashMode) {
                $whereClause .= " AND u.is_active = false";
            } else {
                $whereClause .= " AND u.is_active = true";
            }

            if ($search !== '') {
                $whereClause .= " AND (u.nama_lengkap ILIKE :search_nama OR u.email ILIKE :search_email)";
                $params['search_nama'] = "%" . $search . "%";
                $params['search_email'] = "%" . $search . "%";
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
                    'nisn', 'nama_lengkap', 'jenis_kelamin', 'tanggal_lahir', 'tempat_lahir', 
                    'id_angkatan', 'id_tahun_ajaran', 'id_jenjang', 'id_jurusan', 'id_kelas', 'id_pendidikan',
                    'alamat_kk', 'alamat_domisili', 'rt', 'rw', 'kode_pos', 'id_kelurahan', 'status_tinggal',
                    'kontak_email', 'no_telepon_siswa',
                    'tinggi_badan', 'berat_badan', 'lingkar_kepala', 'golongan_darah', 
                    'anak_ke', 'jumlah_saudara', 'jarak_rumah', 'transportasi',
                    'nik_ibu', 'nama_ibu', 'id_tempat_lahir_ibu', 'tanggal_lahir_ibu',
                    'pendidikan_ibu', 'pekerjaan_ibu', 'penghasilan_ibu', 'agama_ibu',
                    'jenis_pendaftaran', 'jalur_diterima', 'tanggal_masuk', 'hobi'
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
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               WHERE ur.user_id = u.id AND ur.role_id = 20
                           ) AS is_bk,
                           EXISTS(
                               SELECT 1 FROM core.user_roles ur 
                               WHERE ur.user_id = u.id AND ur.role_id = 22
                           ) AS is_kesiswaan
                    FROM core.users u
                    JOIN core.roles r ON u.role_id = r.id
                    WHERE u.id = :id";
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
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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
            
            $sql = "INSERT INTO core.users (id, tenant_id, role_id, nama_lengkap, email, password_hash, is_active) 
                    VALUES (:id, :tenant_id, :role_id, :nama_lengkap, :email, :password_hash, true)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $userId,
                'tenant_id' => $this->tenantId,
                'role_id' => $roleId,
                'nama_lengkap' => strip_tags(trim($data['nama_lengkap'])),
                'email' => strtolower(trim($data['email'])),
                'password_hash' => $hashedPassword
            ]);

            // Tulis role utama ke user_roles
            $urSql = "INSERT INTO core.user_roles (user_id, role_id) VALUES (:user_id, :role_id) ON CONFLICT DO NOTHING";
            $urStmt = $this->db->prepare($urSql);
            $urStmt->execute([
                'user_id' => $userId,
                'role_id' => $roleId
            ]);

            // Fungsi helper untuk mengecek ketersediaan role
            $checkRoleExist = function($rId) {
                $st = $this->db->prepare("SELECT COUNT(*) FROM core.roles WHERE id = ?");
                $st->execute([$rId]);
                return $st->fetchColumn() > 0;
            };

            // Jika kategori guru dan dicentang sebagai Guru BK (role_id 20)
            if ($tab === 'guru' && !empty($data['is_bk'])) {
                if ($checkRoleExist(20)) {
                    $urStmt->execute([
                        'user_id' => $userId,
                        'role_id' => 20
                    ]);
                } else {
                    throw new \Exception("Role Guru BK (20) belum tersedia di sistem. Harap jalankan migrasi database.");
                }
            }

            // Jika kategori guru dan dicentang sebagai Kesiswaan (role_id 22)
            if ($tab === 'guru' && !empty($data['is_kesiswaan'])) {
                if ($checkRoleExist(22)) {
                    $urStmt->execute([
                        'user_id' => $userId,
                        'role_id' => 22
                    ]);
                } else {
                    throw new \Exception("Role Kesiswaan (22) belum tersedia di sistem. Harap jalankan migrasi database.");
                }
            }

            if ($tab === 'guru' && !empty($data['is_humas'])) {
                if ($checkRoleExist(23)) {
                    $urStmt->execute(['user_id' => $userId, 'role_id' => 23]);
                } else {
                    throw new \Exception("Role Humas (23) belum tersedia di sistem. Harap jalankan migrasi database.");
                }
            }

            if ($tab === 'guru' && !empty($data['is_kurikulum'])) {
                if ($checkRoleExist(24)) {
                    $urStmt->execute(['user_id' => $userId, 'role_id' => 24]);
                } else {
                    throw new \Exception("Role Kurikulum (24) belum tersedia di sistem. Harap jalankan migrasi database.");
                }
            }

            if ($tab === 'guru' && !empty($data['is_sarpras'])) {
                if ($checkRoleExist(25)) {
                    $urStmt->execute(['user_id' => $userId, 'role_id' => 25]);
                } else {
                    throw new \Exception("Role Sarpras (25) belum tersedia di sistem. Harap jalankan migrasi database.");
                }
            }

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
            'nama_lengkap' => strip_tags(trim($data['nama_lengkap'])),
            'email' => strtolower(trim($data['email']))
        ];
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }

        $sql = "UPDATE core.users SET nama_lengkap = :nama_lengkap, email = :email";
        if (!empty($data['password'])) {
            $sql .= ", password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        $sql .= " WHERE id = :id";
        if ($this->tenantId !== null) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);

            // Tulis/sinkronisasikan role utama ke user_roles
            $roleName = $this->roleMap[$tab] ?? '';
            $roleId = $this->db->query("SELECT id FROM core.roles WHERE nama_role = '$roleName'")->fetchColumn() ?: 0;
            if ($roleId > 0) {
                $urSql = "INSERT INTO core.user_roles (user_id, role_id) VALUES (:user_id, :role_id) ON CONFLICT DO NOTHING";
                $urStmt = $this->db->prepare($urSql);
                $urStmt->execute([
                    'user_id' => $id,
                    'role_id' => $roleId
                ]);
            }

            // Kelola role Guru BK & Kesiswaan kustom jika tab adalah Guru
            if ($tab === 'guru') {
                $checkRoleExist = function($rId) {
                    $st = $this->db->prepare("SELECT COUNT(*) FROM core.roles WHERE id = ?");
                    $st->execute([$rId]);
                    return $st->fetchColumn() > 0;
                };

                if (!empty($data['is_bk'])) {
                    if ($checkRoleExist(20)) {
                        $insertBk = "INSERT INTO core.user_roles (user_id, role_id) VALUES (?, 20) ON CONFLICT DO NOTHING";
                        $this->db->prepare($insertBk)->execute([$id]);
                    } else {
                        throw new \Exception("Role Guru BK (20) belum tersedia. Harap jalankan migrasi database.");
                    }
                } else {
                    $deleteBk = "DELETE FROM core.user_roles WHERE user_id = ? AND role_id = 20";
                    $this->db->prepare($deleteBk)->execute([$id]);
                }

                if (!empty($data['is_kesiswaan'])) {
                    if ($checkRoleExist(22)) {
                        $insertKis = "INSERT INTO core.user_roles (user_id, role_id) VALUES (?, 22) ON CONFLICT DO NOTHING";
                        $this->db->prepare($insertKis)->execute([$id]);
                    } else {
                        throw new \Exception("Role Kesiswaan (22) belum tersedia. Harap jalankan migrasi database.");
                    }
                } else {
                    $deleteKis = "DELETE FROM core.user_roles WHERE user_id = ? AND role_id = 22";
                    $this->db->prepare($deleteKis)->execute([$id]);
                }

                if (!empty($data['is_humas'])) {
                    if ($checkRoleExist(23)) {
                        $this->db->prepare("INSERT INTO core.user_roles (user_id, role_id) VALUES (?, 23) ON CONFLICT DO NOTHING")->execute([$id]);
                    } else {
                        throw new \Exception("Role Humas (23) belum tersedia.");
                    }
                } else {
                    $this->db->prepare("DELETE FROM core.user_roles WHERE user_id = ? AND role_id = 23")->execute([$id]);
                }

                if (!empty($data['is_kurikulum'])) {
                    if ($checkRoleExist(24)) {
                        $this->db->prepare("INSERT INTO core.user_roles (user_id, role_id) VALUES (?, 24) ON CONFLICT DO NOTHING")->execute([$id]);
                    } else {
                        throw new \Exception("Role Kurikulum (24) belum tersedia.");
                    }
                } else {
                    $this->db->prepare("DELETE FROM core.user_roles WHERE user_id = ? AND role_id = 24")->execute([$id]);
                }

                if (!empty($data['is_sarpras'])) {
                    if ($checkRoleExist(25)) {
                        $this->db->prepare("INSERT INTO core.user_roles (user_id, role_id) VALUES (?, 25) ON CONFLICT DO NOTHING")->execute([$id]);
                    } else {
                        throw new \Exception("Role Sarpras (25) belum tersedia.");
                    }
                } else {
                    $this->db->prepare("DELETE FROM core.user_roles WHERE user_id = ? AND role_id = 25")->execute([$id]);
                }
            }

            $this->db->commit();
            return $success;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
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
            $sql = "SELECT is_active FROM core.users WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->db->rollBack();
                return false;
            }

            $newStatus = ($user['is_active'] === true) ? false : true;

            // Update status
            $updateSql = "UPDATE core.users SET is_active = :is_active WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $success = $updateStmt->execute([
                'is_active' => $newStatus,
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

