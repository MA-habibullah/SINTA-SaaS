<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Pengguna extends Model {

    // Pemetaan role untuk mempermudah identifikasi
    private array $roleMap = [
        'operator' => 2,  // operator_sekolah
        'guru' => 3,      // guru
        'siswa' => 4,     // siswa
        'karyawan' => 6   // karyawan
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
            // Query untuk Log Mutasi & Putus Sekolah (menggabungkan siswa dengan registrasi, kelas, jenjang, tenants, dan users)
            $selectSql = "SELECT s.*, COALESCE(kl_lahir.nama_kota, s.tempat_lahir) AS tempat_lahir,
                                 u.email, u.status AS user_status, t.nama_sekolah,
                                 kel.nama_kelas AS nama_kelas, jen.nama_jenjang AS nama_jenjang,
                                 reg.keluar_karena, reg.tanggal_keluar, reg.alasan_keluar
                          FROM siswa s
                          LEFT JOIN kota kl_lahir ON s.tempat_lahir = kl_lahir.id_kota
                          LEFT JOIN users u ON s.user_id = u.id
                          LEFT JOIN tenants t ON s.tenant_id = t.id
                          LEFT JOIN kelas kel ON s.id_kelas = kel.id
                          LEFT JOIN jenjang jen ON s.id_jenjang = jen.id
                          LEFT JOIN registrasi reg ON s.id = reg.id_siswa";
            $countSql = "SELECT COUNT(*) FROM siswa s 
                          LEFT JOIN tenants t ON s.tenant_id = t.id 
                          LEFT JOIN users u ON s.user_id = u.id
                          LEFT JOIN registrasi reg ON s.id = reg.id_siswa";
            $whereClause = $isSuperAdmin ? " WHERE (s.status = 'Pindah' OR reg.keluar_karena IS NOT NULL)" : " WHERE s.tenant_id = :tenant_id AND (s.status = 'Pindah' OR reg.keluar_karena IS NOT NULL)";

            if ($isSuperAdmin && !empty($filters['tenant_id'])) {
                $whereClause .= " AND s.tenant_id = :filter_tenant_id";
                $params['filter_tenant_id'] = $filters['tenant_id'];
            }

            // Filter kelas / rombel
            if (!empty($filters['id_kelas'])) {
                $whereClause .= " AND s.id_kelas = :id_kelas";
                $params['id_kelas'] = (int)$filters['id_kelas'];
            }

            if ($trashMode) {
                $whereClause .= " AND s.deleted_at IS NOT NULL";
            } else {
                $whereClause .= " AND s.deleted_at IS NULL";
            }

            if ($search !== '') {
                $whereClause .= " AND (LOWER(s.nama_lengkap) LIKE :search_nama OR LOWER(s.nisn) LIKE :search_nisn OR LOWER(s.nis) LIKE :search_nis OR LOWER(u.email) LIKE :search_email";
                if ($isSuperAdmin) {
                    $whereClause .= " OR LOWER(t.nama_sekolah) LIKE :search_sekolah";
                }
                $whereClause .= ")";
                $params['search_nama'] = "%" . strtolower($search) . "%";
                $params['search_nisn'] = "%" . strtolower($search) . "%";
                $params['search_nis'] = "%" . strtolower($search) . "%";
                $params['search_email'] = "%" . strtolower($search) . "%";
                if ($isSuperAdmin) {
                    $params['search_sekolah'] = "%" . strtolower($search) . "%";
                }
            }

            $orderBy = " ORDER BY reg.tanggal_keluar DESC, s.nama_lengkap ASC";
        } elseif ($tab === 'siswa') {
            // Query untuk Siswa (menggabungkan siswa dengan user account, rincian_alamat, kontak, dan orang_tua untuk hitung kelengkapan)
            $selectSql = "SELECT s.*, COALESCE(kl_lahir.nama_kota, s.tempat_lahir) AS tempat_lahir,
                                 u.email, u.status AS user_status, t.nama_sekolah,
                                 ra.alamat_kk, ra.alamat_domisili, ra.rt, ra.rw, ra.kode_pos, ra.id_kelurahan, ra.status_tinggal,
                                 k.email AS kontak_email, k.no_telepon_siswa,
                                 ot.nik_ibu, ot.nama_ibu, ot.id_tempat_lahir_ibu, ot.tanggal_lahir_ibu, ot.pendidikan_ibu, ot.pekerjaan_ibu, ot.penghasilan_ibu, ot.agama_ibu,
                                 rp.tinggi_badan, rp.berat_badan, rp.lingkar_kepala, rp.golongan_darah, rp.anak_ke, rp.jumlah_saudara, rp.jarak_rumah, rp.transportasi,
                                 reg.jenis_pendaftaran, reg.jalur_diterima, reg.tanggal_masuk, reg.hobi,
                                 kel.nama_kelas AS nama_kelas, jen.nama_jenjang AS nama_jenjang
                          FROM siswa s
                          LEFT JOIN kota kl_lahir ON s.tempat_lahir = kl_lahir.id_kota
                          LEFT JOIN users u ON s.user_id = u.id
                          LEFT JOIN tenants t ON s.tenant_id = t.id
                          LEFT JOIN rincian_alamat ra ON s.id = ra.id_siswa
                          LEFT JOIN kontak k ON s.id = k.id_siswa
                          LEFT JOIN orang_tua ot ON s.id = ot.id_siswa
                          LEFT JOIN rincian_pelajar rp ON s.id = rp.id_siswa
                          LEFT JOIN registrasi reg ON s.id = reg.id_siswa
                          LEFT JOIN kelas kel ON s.id_kelas = kel.id
                          LEFT JOIN jenjang jen ON s.id_jenjang = jen.id";
            $countSql = "SELECT COUNT(*) FROM siswa s 
                          LEFT JOIN tenants t ON s.tenant_id = t.id 
                          LEFT JOIN users u ON s.user_id = u.id";
            $whereClause = $isSuperAdmin ? " WHERE 1=1" : " WHERE s.tenant_id = :tenant_id";

            if ($isSuperAdmin && !empty($filters['tenant_id'])) {
                $whereClause .= " AND s.tenant_id = :filter_tenant_id";
                $params['filter_tenant_id'] = $filters['tenant_id'];
            }

            if (isset($filters['siswa_id'])) {
                $whereClause .= " AND s.id = :siswa_id";
                $params['siswa_id'] = $filters['siswa_id'];
            } elseif (isset($filters['user_id'])) {
                $whereClause .= " AND s.user_id = :user_id";
                $params['user_id'] = $filters['user_id'];
            }

            // Filter status: default to 'Aktif'
            $status = $filters['status'] ?? 'Aktif';
            if (empty($status)) {
                $status = 'Aktif';
            }
            $whereClause .= " AND s.status = :status";
            $params['status'] = $status;

            // Filter kelas / rombel
            if (!empty($filters['id_kelas'])) {
                $whereClause .= " AND s.id_kelas = :id_kelas";
                $params['id_kelas'] = (int)$filters['id_kelas'];
            }

            if ($trashMode) {
                $whereClause .= " AND s.deleted_at IS NOT NULL";
            } else {
                $whereClause .= " AND s.deleted_at IS NULL";
            }

            if ($search !== '') {
                $whereClause .= " AND (LOWER(s.nama_lengkap) LIKE :search_nama OR LOWER(s.nisn) LIKE :search_nisn OR LOWER(s.nis) LIKE :search_nis OR LOWER(u.email) LIKE :search_email";
                if ($isSuperAdmin) {
                    $whereClause .= " OR LOWER(t.nama_sekolah) LIKE :search_sekolah";
                }
                $whereClause .= ")";
                $params['search_nama'] = "%" . strtolower($search) . "%";
                $params['search_nisn'] = "%" . strtolower($search) . "%";
                $params['search_nis'] = "%" . strtolower($search) . "%";
                $params['search_email'] = "%" . strtolower($search) . "%";
                if ($isSuperAdmin) {
                    $params['search_sekolah'] = "%" . strtolower($search) . "%";
                }
            }

            $orderBy = " ORDER BY s.nama_lengkap ASC";
        } else {
            // Query untuk staff (Guru, Karyawan, Operator) dari tabel users
            $roleId = $this->roleMap[$tab] ?? 0;
            $selectSql = "SELECT u.*, r.nama_role, t.nama_sekolah,
                                 EXISTS(
                                     SELECT 1 FROM user_roles ur 
                                     WHERE ur.user_id = u.id AND ur.role_id = 20
                                 ) AS is_bk,
                                 EXISTS(
                                     SELECT 1 FROM user_roles ur 
                                     WHERE ur.user_id = u.id AND ur.role_id = 22
                                 ) AS is_kesiswaan,
                                 EXISTS(
                                     SELECT 1 FROM user_roles ur 
                                     WHERE ur.user_id = u.id AND ur.role_id = 23
                                 ) AS is_humas,
                                 EXISTS(
                                     SELECT 1 FROM user_roles ur 
                                     WHERE ur.user_id = u.id AND ur.role_id = 24
                                 ) AS is_kurikulum,
                                 EXISTS(
                                     SELECT 1 FROM user_roles ur 
                                     WHERE ur.user_id = u.id AND ur.role_id = 25
                                 ) AS is_sarpras
                          FROM users u
                          JOIN roles r ON u.role_id = r.id
                          LEFT JOIN tenants t ON u.tenant_id = t.id";
            $countSql = "SELECT COUNT(*) FROM users u LEFT JOIN tenants t ON u.tenant_id = t.id";
            $whereClause = $isSuperAdmin ? " WHERE u.role_id = :role_id" : " WHERE u.tenant_id = :tenant_id AND u.role_id = :role_id";
            $params['role_id'] = $roleId;

            if ($isSuperAdmin && !empty($filters['tenant_id'])) {
                $whereClause .= " AND u.tenant_id = :filter_tenant_id";
                $params['filter_tenant_id'] = $filters['tenant_id'];
            }

            if ($trashMode) {
                $whereClause .= " AND u.deleted_at IS NOT NULL";
            } else {
                $whereClause .= " AND u.deleted_at IS NULL";
            }

            if ($search !== '') {
                $whereClause .= " AND (LOWER(u.nama_lengkap) LIKE :search_nama OR LOWER(u.email) LIKE :search_email";
                if ($isSuperAdmin) {
                    $whereClause .= " OR LOWER(t.nama_sekolah) LIKE :search_sekolah";
                }
                $whereClause .= ")";
                $params['search_nama'] = "%" . strtolower($search) . "%";
                $params['search_email'] = "%" . strtolower($search) . "%";
                if ($isSuperAdmin) {
                    $params['search_sekolah'] = "%" . strtolower($search) . "%";
                }
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
                    if ($val !== null && trim((string)$val) !== '') {
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
            $sql = "SELECT s.*, u.email, u.status AS user_status 
                    FROM siswa s
                    LEFT JOIN users u ON s.user_id = u.id
                    WHERE s.id = :id";
            if (!$isSuperAdmin) {
                $sql .= " AND s.tenant_id = :tenant_id";
            }
        } else {
            $sql = "SELECT u.*, r.nama_role,
                           EXISTS(
                               SELECT 1 FROM user_roles ur 
                               WHERE ur.user_id = u.id AND ur.role_id = 20
                           ) AS is_bk,
                           EXISTS(
                               SELECT 1 FROM user_roles ur 
                               WHERE ur.user_id = u.id AND ur.role_id = 22
                           ) AS is_kesiswaan
                    FROM users u
                    JOIN roles r ON u.role_id = r.id
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
                
                $userSql = "INSERT INTO users (id, tenant_id, role_id, nama_lengkap, email, password, status) 
                            VALUES (:id, :tenant_id, :role_id, :nama_lengkap, :email, :password, 'active')";
                $userStmt = $this->db->prepare($userSql);
                $userStmt->execute([
                    'id' => $userId,
                    'tenant_id' => $this->tenantId,
                    'role_id' => 4, // siswa
                    'nama_lengkap' => $data['nama_lengkap'],
                    'email' => strtolower(trim($data['email'])),
                    'password' => $hashedPassword
                ]);
            }

            // 2. Buat data siswa
            $siswaSql = "INSERT INTO siswa (
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
                    
                    $userSql = "INSERT INTO users (id, tenant_id, role_id, nama_lengkap, email, password, status) 
                                VALUES (:id, :tenant_id, :role_id, :nama_lengkap, :email, :password, 'active')";
                    $userStmt = $this->db->prepare($userSql);
                    $userStmt->execute([
                        'id' => $userId,
                        'tenant_id' => $this->tenantId,
                        'role_id' => 4,
                        'nama_lengkap' => $data['nama_lengkap'],
                        'email' => strtolower(trim($data['email'])),
                        'password' => $hashedPassword
                    ]);

                    // Link user_id ke siswa
                    $linkSql = "UPDATE siswa SET user_id = :user_id WHERE id = :id";
                    $linkStmt = $this->db->prepare($linkSql);
                    $linkStmt->execute(['user_id' => $userId, 'id' => $id]);
                } else {
                    // Update akun user yang sudah ada
                    $userSql = "UPDATE users SET nama_lengkap = :nama_lengkap, email = :email";
                    $userParams = [
                        'nama_lengkap' => $data['nama_lengkap'],
                        'email' => strtolower(trim($data['email'])),
                        'id' => $userId
                    ];

                    if (!empty($data['password'])) {
                        $userSql .= ", password = :password";
                        $userParams['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
                    }

                    $userSql .= " WHERE id = :id";
                    $userStmt = $this->db->prepare($userSql);
                    $userStmt->execute($userParams);
                }
            }

            // 2. Update data siswa
            $siswaSql = "UPDATE siswa SET 
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
        $roleId = $this->roleMap[$tab] ?? 0;
        $hashedPassword = password_hash($data['password'] ?? 'staff123', PASSWORD_ARGON2ID);

        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO users (id, tenant_id, role_id, nama_lengkap, email, password, status) 
                    VALUES (:id, :tenant_id, :role_id, :nama_lengkap, :email, :password, 'active')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $userId,
                'tenant_id' => $this->tenantId,
                'role_id' => $roleId,
                'nama_lengkap' => strip_tags(trim($data['nama_lengkap'])),
                'email' => strtolower(trim($data['email'])),
                'password' => $hashedPassword
            ]);

            // Tulis role utama ke user_roles
            $urSql = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
            $urStmt = $this->db->prepare($urSql);
            $urStmt->execute([
                'user_id' => $userId,
                'role_id' => $roleId
            ]);

            // Fungsi helper untuk mengecek ketersediaan role
            $checkRoleExist = function($rId) {
                $st = $this->db->prepare("SELECT COUNT(*) FROM roles WHERE id = ?");
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

        $sql = "UPDATE users SET nama_lengkap = :nama_lengkap, email = :email";
        if (!empty($data['password'])) {
            $sql .= ", password = :password";
            $params['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
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
            $roleId = $this->roleMap[$tab] ?? 0;
            if ($roleId > 0) {
                $urSql = "INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
                $urStmt = $this->db->prepare($urSql);
                $urStmt->execute([
                    'user_id' => $id,
                    'role_id' => $roleId
                ]);
            }

            // Kelola role Guru BK & Kesiswaan kustom jika tab adalah Guru
            if ($tab === 'guru') {
                $checkRoleExist = function($rId) {
                    $st = $this->db->prepare("SELECT COUNT(*) FROM roles WHERE id = ?");
                    $st->execute([$rId]);
                    return $st->fetchColumn() > 0;
                };

                if (!empty($data['is_bk'])) {
                    if ($checkRoleExist(20)) {
                        $insertBk = "INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, 20)";
                        $this->db->prepare($insertBk)->execute([$id]);
                    } else {
                        throw new \Exception("Role Guru BK (20) belum tersedia. Harap jalankan migrasi database.");
                    }
                } else {
                    $deleteBk = "DELETE FROM user_roles WHERE user_id = ? AND role_id = 20";
                    $this->db->prepare($deleteBk)->execute([$id]);
                }

                if (!empty($data['is_kesiswaan'])) {
                    if ($checkRoleExist(22)) {
                        $insertKis = "INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, 22)";
                        $this->db->prepare($insertKis)->execute([$id]);
                    } else {
                        throw new \Exception("Role Kesiswaan (22) belum tersedia. Harap jalankan migrasi database.");
                    }
                } else {
                    $deleteKis = "DELETE FROM user_roles WHERE user_id = ? AND role_id = 22";
                    $this->db->prepare($deleteKis)->execute([$id]);
                }

                if (!empty($data['is_humas'])) {
                    if ($checkRoleExist(23)) {
                        $this->db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, 23)")->execute([$id]);
                    } else {
                        throw new \Exception("Role Humas (23) belum tersedia.");
                    }
                } else {
                    $this->db->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = 23")->execute([$id]);
                }

                if (!empty($data['is_kurikulum'])) {
                    if ($checkRoleExist(24)) {
                        $this->db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, 24)")->execute([$id]);
                    } else {
                        throw new \Exception("Role Kurikulum (24) belum tersedia.");
                    }
                } else {
                    $this->db->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = 24")->execute([$id]);
                }

                if (!empty($data['is_sarpras'])) {
                    if ($checkRoleExist(25)) {
                        $this->db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, 25)")->execute([$id]);
                    } else {
                        throw new \Exception("Role Sarpras (25) belum tersedia.");
                    }
                } else {
                    $this->db->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = 25")->execute([$id]);
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
                // Hapus data siswa
                $sql = "UPDATE siswa SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
                if ($this->tenantId !== null) {
                    $sql .= " AND tenant_id = :tenant_id";
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                // Jika siswa memiliki akun user, hapus juga akun user-nya
                $siswa = $this->findById($tab, $id);
                if ($siswa && $siswa['user_id']) {
                    $userSql = "UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = :user_id";
                    $userStmt = $this->db->prepare($userSql);
                    $userStmt->execute(['user_id' => $siswa['user_id']]);
                }
            } else {
                // Hapus data staff
                $sql = "UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
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
     * Memulihkan Pengguna dari Tempat Sampah (Restore)
     */
    public function restore(string $tab, string $id): bool {
        try {
            $this->db->beginTransaction();
            $params = ['id' => $id];
            if ($this->tenantId !== null) {
                $params['tenant_id'] = $this->tenantId;
            }

            if ($tab === 'siswa' || $tab === 'mutasi') {
                // Pulihkan data siswa
                $sql = "UPDATE siswa SET deleted_at = NULL WHERE id = :id";
                if ($this->tenantId !== null) {
                    $sql .= " AND tenant_id = :tenant_id";
                }
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                // Jika siswa memiliki akun user, pulihkan juga akun user-nya
                $siswa = $this->findById($tab, $id);
                if ($siswa && $siswa['user_id']) {
                    $userSql = "UPDATE users SET deleted_at = NULL WHERE id = :user_id";
                    $userStmt = $this->db->prepare($userSql);
                    $userStmt->execute(['user_id' => $siswa['user_id']]);
                }
            } else {
                // Pulihkan data staff
                $sql = "UPDATE users SET deleted_at = NULL WHERE id = :id";
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
            $sql = "SELECT status FROM users WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->db->rollBack();
                return false;
            }

            $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';

            // Update status
            $updateSql = "UPDATE users SET status = :status WHERE id = :id";
            $updateStmt = $this->db->prepare($updateSql);
            $success = $updateStmt->execute([
                'status' => $newStatus,
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
        
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email AND deleted_at IS NULL";
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
        $sql = "SELECT COUNT(*) FROM siswa WHERE nisn = :nisn AND deleted_at IS NULL";
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
        
        $sql = "SELECT COUNT(*) FROM siswa WHERE nis = :nis AND deleted_at IS NULL";
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
        $stmt = $this->db->query("SELECT id, nama_sekolah, npsn FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC");
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
        $sql = "SELECT k.id, k.nama_kelas, j.nama_jenjang
                FROM kelas k
                LEFT JOIN jenjang j ON k.id_jenjang = j.id
                WHERE k.tenant_id = :tenant_id AND k.deleted_at IS NULL
                ORDER BY j.nama_jenjang ASC, k.nama_kelas ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil daftar siswa aktif berdasarkan kelas dan tenant (untuk tabel checklist aksi).
     */
    public function getSiswaByKelas(int $idKelas, string $tenantId): array {
        $sql = "SELECT s.id, s.nama_lengkap, s.nisn, s.nis,
                       k.nama_kelas, j.nama_jenjang, ta.tahun_ajaran
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN jenjang j ON s.id_jenjang = j.id
                LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id
                WHERE s.tenant_id = :tenant_id
                  AND s.id_kelas = :id_kelas
                  AND s.status = 'Aktif'
                  AND s.deleted_at IS NULL
                ORDER BY s.nama_lengkap ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId, 'id_kelas' => $idKelas]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Naikkan kelas siswa secara massal (atomik: UPDATE siswa + INSERT riwayat).
     *
     * @param  array  $siswaIds      Array UUID siswa yang dipilih
     * @param  int    $idKelasTujuan ID kelas tujuan
     * @param  string $tenantId      Tenant ID (dikunci dari session di Controller)
     * @param  array  $auditData     ['tahun_ajaran', 'dilakukan_oleh', 'nama_pelaku', 'catatan']
     * @return int                   Jumlah siswa yang berhasil diproses
     */
    public function naikkanKelas(array $siswaIds, int $idKelasTujuan, string $tenantId, array $auditData): int {
        if (empty($siswaIds)) return 0;

        // Sanitasi: pastikan hanya siswa yang benar-benar milik tenant ini
        $placeholders = implode(',', array_fill(0, count($siswaIds), '?'));
        $checkSql = "SELECT id, id_kelas,
                            (SELECT nama_kelas FROM kelas WHERE id = siswa.id_kelas LIMIT 1) AS nama_kelas_asal
                     FROM siswa
                     WHERE id IN ({$placeholders})
                       AND tenant_id = ?
                       AND status = 'Aktif'
                       AND deleted_at IS NULL";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([...$siswaIds, $tenantId]);
        $validRows = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($validRows)) return 0;

        // Nama kelas tujuan & jenjang tujuan (untuk snapshot dan update jenjang)
        $kelasStmt = $this->db->prepare("SELECT nama_kelas, id_jenjang FROM kelas WHERE id = ? LIMIT 1");
        $kelasStmt->execute([$idKelasTujuan]);
        $kelasTujuanRow = $kelasStmt->fetch(PDO::FETCH_ASSOC);
        $namaKelasTujuan = $kelasTujuanRow['nama_kelas'] ?? '';
        $idJenjangTujuan = $kelasTujuanRow['id_jenjang'] ?? null;

        $tahunAjaran   = $auditData['tahun_ajaran']   ?? '';
        $dilakukanOleh = $auditData['dilakukan_oleh'] ?? '';
        $namaPelaku    = $auditData['nama_pelaku']    ?? '';
        $catatan       = $auditData['catatan']        ?? null;

        try {
            $this->db->beginTransaction();

            $updateSql = "UPDATE siswa SET id_kelas = :id_kelas_tujuan, id_jenjang = :id_jenjang_tujuan
                          WHERE id = :id AND tenant_id = :tenant_id AND deleted_at IS NULL";
            $updateStmt = $this->db->prepare($updateSql);

            $insertSql = "INSERT INTO riwayat_kenaikan_kelas
                            (tenant_id, siswa_id, jenis_aksi, id_kelas_asal, id_kelas_tujuan,
                             id_jenjang_asal, id_jenjang_tujuan,
                             nama_kelas_asal, nama_kelas_tujuan, tahun_ajaran,
                             dilakukan_oleh, nama_pelaku, catatan)
                          VALUES
                            (:tenant_id, :siswa_id, 'naik_kelas', :id_kelas_asal, :id_kelas_tujuan,
                             :id_jenjang_asal, :id_jenjang_tujuan,
                             :nama_kelas_asal, :nama_kelas_tujuan, :tahun_ajaran,
                             :dilakukan_oleh, :nama_pelaku, :catatan)";
            $insertStmt = $this->db->prepare($insertSql);

            $count = 0;
            foreach ($validRows as $row) {
                $updateStmt->execute([
                    'id_kelas_tujuan'   => $idKelasTujuan,
                    'id_jenjang_tujuan' => $idJenjangTujuan,
                    'id'                => $row['id'],
                    'tenant_id'         => $tenantId
                ]);
                $insertStmt->execute([
                    'tenant_id'          => $tenantId,
                    'siswa_id'           => $row['id'],
                    'id_kelas_asal'      => $row['id_kelas'],
                    'id_kelas_tujuan'    => $idKelasTujuan,
                    'id_jenjang_asal'    => $row['id_jenjang_asal'],
                    'id_jenjang_tujuan'  => $idJenjangTujuan,
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
        $checkSql = "SELECT id, id_kelas, id_jenjang AS id_jenjang_asal,
                            (SELECT nama_kelas FROM kelas WHERE id = siswa.id_kelas LIMIT 1) AS nama_kelas_asal
                     FROM siswa
                     WHERE id IN ({$placeholders})
                       AND tenant_id = ?
                       AND status = 'Aktif'
                       AND deleted_at IS NULL";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([...$siswaIds, $tenantId]);
        $validRows = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($validRows)) return 0;

        $tahunAjaran   = $auditData['tahun_ajaran']   ?? '';
        $dilakukanOleh = $auditData['dilakukan_oleh'] ?? '';
        $namaPelaku    = $auditData['nama_pelaku']    ?? '';
        $catatan       = $auditData['catatan']        ?? null;

        try {
            $this->db->beginTransaction();

            $updateSql = "UPDATE siswa
                          SET status = 'Lulus', tanggal_lulus = CURDATE()
                          WHERE id = :id AND tenant_id = :tenant_id AND deleted_at IS NULL";
            $updateStmt = $this->db->prepare($updateSql);

            $insertSql = "INSERT INTO riwayat_kenaikan_kelas
                            (tenant_id, siswa_id, jenis_aksi, id_kelas_asal, id_kelas_tujuan,
                             id_jenjang_asal, id_jenjang_tujuan,
                             nama_kelas_asal, nama_kelas_tujuan, tahun_ajaran,
                             dilakukan_oleh, nama_pelaku, catatan)
                          VALUES
                            (:tenant_id, :siswa_id, 'lulus', :id_kelas_asal, NULL,
                             :id_jenjang_asal, NULL,
                             :nama_kelas_asal, NULL, :tahun_ajaran,
                             :dilakukan_oleh, :nama_pelaku, :catatan)";
            $insertStmt = $this->db->prepare($insertSql);

            $count = 0;
            foreach ($validRows as $row) {
                $updateStmt->execute(['id' => $row['id'], 'tenant_id' => $tenantId]);
                $insertStmt->execute([
                    'tenant_id'      => $tenantId,
                    'siswa_id'       => $row['id'],
                    'id_kelas_asal'  => $row['id_kelas'],
                    'id_jenjang_asal'=> $row['id_jenjang_asal'],
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
                FROM riwayat_kenaikan_kelas r
                WHERE r.siswa_id = :siswa_id AND r.tenant_id = :tenant_id
                ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['siswa_id' => $siswaId, 'tenant_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

