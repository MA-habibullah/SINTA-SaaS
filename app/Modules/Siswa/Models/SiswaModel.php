<?php

namespace App\Modules\Siswa\Models;

use App\Core\BaseModel;
use PDO;

/**
 * SiswaModel — Model utama untuk entitas Siswa.
 * Menggunakan skema PostgreSQL: siswa.siswa + siswa.[sub_tables]
 * Tidak lagi bergantung pada legacy App\Models\Siswa.
 */
class SiswaModel extends BaseModel {
    protected static string $table  = 'siswa';
    protected static string $schema = 'siswa';
    protected ?string $tenantId = null;

    public function __construct(?string $tenantId = null) {
        parent::__construct();
        $this->tenantId = $tenantId;
    }

    public function setTenantId(string $tenantId): self {
        $this->tenantId = $tenantId;
        return $this;
    }

    // ═══════════════════════════════════════════════════════
    // READ METHODS
    // ═══════════════════════════════════════════════════════

    /**
     * Ambil data siswa berdasarkan ID, tanpa tenant filter (untuk Super Admin bypass).
     */
    public function findFullById(string $siswaId): ?array {
        $table = static::getTableName();
        $sql = "SELECT s.*,
                       COALESCE(k.nama_kelas, s.kelas_saat_ini, '-') as nama_kelas,
                       t.nama_sekolah,
                       COALESCE(j.nama_jurusan, s.jurusan, '-') as nama_jurusan
                FROM {$table} s
                LEFT JOIN akademik.kelas k ON (s.tenant_id = k.tenant_id AND (s.kelas_saat_ini = k.id::text OR s.kelas_saat_ini = k.nama_kelas))
                LEFT JOIN core.tenants t ON s.tenant_id = t.id
                LEFT JOIN akademik.jurusan j ON (s.tenant_id = j.tenant_id AND (s.jurusan = j.id::text OR s.jurusan = j.nama_jurusan))
                WHERE s.id::text = :id";

        $hasTenantFilter = (!empty($this->tenantId) && $this->tenantId !== '00000000-0000-0000-0000-000000000000');
        if ($hasTenantFilter) {
            $sql .= " AND s.tenant_id = :tenant_id";
        }
        $sql .= " AND (s.is_active = true OR s.is_active IS NULL) LIMIT 1";

        $stmt = self::getPdo()->prepare($sql);
        $stmt->bindValue(':id', $siswaId);
        if ($hasTenantFilter) {
            $stmt->bindValue(':tenant_id', $this->tenantId);
        }
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Ambil data siswa berdasarkan tenant + ID (multi-tenant safe).
     */
    public static function findById(string $tenantId, string $siswaId): ?array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare(
            "SELECT id, tenant_id, nama_lengkap, nis, nisn, is_active FROM {$table}
             WHERE tenant_id = :tenant_id AND id::text = :id AND is_active = true LIMIT 1"
        );
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':id', $siswaId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Daftar siswa aktif per tenant.
     */
    public static function getActiveSiswa(string $tenantId, int $limit = 50, int $offset = 0): array {
        $table = static::getTableName();
        $stmt = self::getPdo()->prepare(
            "SELECT id, tenant_id, nama_lengkap, nis, nisn, jenis_kelamin, status_siswa
             FROM {$table} WHERE tenant_id = :tenant_id AND is_active = true
             ORDER BY nama_lengkap ASC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil data kesehatan siswa dari fisik_kesehatan_siswa
     */
    public function getKesehatanSiswa(string $siswaId): ?array {
        $stmt = self::getPdo()->prepare(
            "SELECT * FROM siswa.fisik_kesehatan_siswa WHERE siswa_id::text = :siswa_id LIMIT 1"
        );
        $stmt->bindValue(':siswa_id', $siswaId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ═══════════════════════════════════════════════════════
    // VALIDATION HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Cek keunikan NISN secara nasional (lintas semua tenant).
     */
    public function isNisnUnique(string $nisn, ?string $excludeId = null): bool {
        $sql    = "SELECT COUNT(*) FROM siswa.siswa WHERE nisn = :nisn AND is_active = true";
        $params = [':nisn' => $nisn];
        if ($excludeId !== null && $excludeId !== '') {
            $sql              .= " AND id::text != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) === 0;
    }

    /**
     * Cek keunikan NIS di lingkup sekolah (tenant) bersangkutan.
     */
    public function isNisUnique(string $nis, ?string $excludeId = null): bool {
        $sql    = "SELECT COUNT(*) FROM siswa.siswa WHERE nis = :nis AND is_active = true";
        $params = [':nis' => $nis];
        $isSuperAdmin = (empty($this->tenantId) || $this->tenantId === '00000000-0000-0000-0000-000000000000');
        if (!$isSuperAdmin) {
            $sql              .= " AND tenant_id = :tenant_id";
            $params[':tenant_id'] = $this->tenantId;
        }
        if ($excludeId !== null && $excludeId !== '') {
            $sql              .= " AND id::text != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) === 0;
    }

    // ═══════════════════════════════════════════════════════
    // WRITE METHODS
    // ═══════════════════════════════════════════════════════

    /**
     * Tambah data siswa baru (native PostgreSQL, multi-schema).
     */
    public function create(array $data): string {
        $db = self::getPdo();
        $id = $data['id'] ?? $this->generateUuidV4();

        $isOuterTransaction = !$db->inTransaction();
        try {
            if ($isOuterTransaction) $db->beginTransaction();

            $sql = "INSERT INTO siswa.siswa (
                        id, tenant_id, nisn, nis, nama_lengkap,
                        tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, agama,
                        angkatan, jurusan, kelas_saat_ini, status_siswa, password
                    ) VALUES (
                        :id, :tenant_id, :nisn, :nis, :nama_lengkap,
                        :tempat_lahir, :tanggal_lahir, :jenis_kelamin, :alamat, :agama,
                        :angkatan, :jurusan, :kelas_saat_ini, :status_siswa, :password
                    )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                'id'            => $id,
                'tenant_id'     => $this->tenantId ?: ($data['tenant_id'] ?? null),
                'nisn'          => !empty($data['nisn']) ? $data['nisn'] : null,
                'nis'           => !empty($data['nis']) ? $data['nis'] : null,
                'nama_lengkap'  => $data['nama_lengkap'],
                'tempat_lahir'  => $data['tempat_lahir'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'jenis_kelamin' => $data['jenis_kelamin'],
                'alamat'        => $data['alamat_kk'] ?? ($data['alamat'] ?? null),
                'agama'         => $data['agama'] ?? null,
                'angkatan'      => $data['id_angkatan'] ?? ($data['angkatan'] ?? null),
                'jurusan'       => $data['id_jurusan'] ?? ($data['jurusan'] ?? null),
                'kelas_saat_ini'=> $data['id_kelas'] ?? ($data['kelas_saat_ini'] ?? null),
                'status_siswa'  => $data['status'] ?? ($data['status_siswa'] ?? 'Aktif'),
                'password'      => !empty($data['password'])
                                    ? password_hash($data['password'], PASSWORD_BCRYPT)
                                    : password_hash($data['tanggal_lahir'] ?? '123456', PASSWORD_BCRYPT),
            ]);

            $this->saveOrUpdateSubTables($db, $id, $data, true);

            if ($isOuterTransaction) $db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($isOuterTransaction && $db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Update data siswa (native PostgreSQL, multi-schema).
     */
    public function update(string $id, array $data): bool {
        $db = self::getPdo();
        $isOuterTransaction = !$db->inTransaction();
        try {
            if ($isOuterTransaction) $db->beginTransaction();

            // Kolom yang benar-benar ada di tabel siswa.siswa utama
            $siswaCols = [
                'nisn', 'nis', 'nama_lengkap', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
                'agama', 'foto_url', 'email', 'no_hp'
            ];

            $setParts = [];
            $params   = ['id' => $id];

            $isSuperAdmin = (empty($this->tenantId) || $this->tenantId === '00000000-0000-0000-0000-000000000000');
            if (!$isSuperAdmin) {
                $params['tenant_id'] = $this->tenantId;
            }

            $setParts = [];
            $params   = ['id' => $id];

            $isSuperAdmin = (empty($this->tenantId) || $this->tenantId === '00000000-0000-0000-0000-000000000000');
            if (!$isSuperAdmin) {
                $params['tenant_id'] = $this->tenantId;
            }

            foreach ($siswaCols as $col) {
                if (array_key_exists($col, $data)) {
                    $setParts[]     = "{$col} = :{$col}";
                    $params[$col]   = ($data[$col] !== '') ? $data[$col] : null;
                }
            }

            // Field mapping: input lama → kolom baru di siswa.siswa
            $mappings = [
                'status'        => 'status_siswa',
                'status_siswa'  => 'status_siswa',
                'id_kelas'      => 'kelas_saat_ini',
                'id_jurusan'    => 'jurusan',
                'id_angkatan'   => 'angkatan',
                'id_tahun_ajaran' => 'tahun_ajaran',
                'id_jenjang'    => 'jenjang',
                'alamat_kk'     => 'alamat',
                'alamat'        => 'alamat',
            ];

            foreach ($mappings as $inputKey => $dbCol) {
                if (array_key_exists($inputKey, $data)) {
                    $val = ($data[$inputKey] !== '') ? $data[$inputKey] : null;
                    // Hindari duplikat param jika input_key === dbCol sudah ditambahkan sebelumnya
                    if (!isset($params[$dbCol])) {
                        $setParts[]       = "{$dbCol} = :{$dbCol}";
                        $params[$dbCol]   = ($dbCol === 'status_siswa' && $val === null) ? 'Aktif' : $val;
                    }
                }
            }

            // Password
            if (array_key_exists('password', $data) && !empty($data['password'])) {
                $setParts[]        = "password = :password";
                $params['password'] = $data['password']; // sudah di-hash di controller
                $setParts[]        = "is_first_login = false";
            }

            if (!empty($setParts)) {
                $sql = "UPDATE siswa.siswa SET " . implode(', ', $setParts) . " WHERE id::text = :id";
                if (!$isSuperAdmin) {
                    $sql .= " AND tenant_id = :tenant_id";
                }
                $db->prepare($sql)->execute($params);
            }

            $this->saveOrUpdateSubTables($db, $id, $data, false);

            if ($isOuterTransaction) $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($isOuterTransaction && $db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Alias untuk backward compat
     */
    public function updateSiswa(string $id, array $data): bool {
        return $this->update($id, $data);
    }

    /**
     * Soft delete siswa.
     */
    public function delete(string $id): bool {
        $isSuperAdmin = (empty($this->tenantId) || $this->tenantId === '00000000-0000-0000-0000-000000000000');
        if ($isSuperAdmin) {
            $sql    = "UPDATE siswa.siswa SET is_active = false WHERE id::text = :id";
            $params = ['id' => $id];
        } else {
            $sql    = "UPDATE siswa.siswa SET is_active = false WHERE id::text = :id AND tenant_id = :tenant_id";
            $params = ['id' => $id, 'tenant_id' => $this->tenantId];
        }
        return self::getPdo()->prepare($sql)->execute($params);
    }

    /**
     * Simpan / update data kesehatan siswa (tabel fisik_kesehatan_siswa).
     */
    public function saveKesehatanSiswa(string $idSiswa, array $kesehatanData): void {
        $db = self::getPdo();
        foreach ($kesehatanData as $semester => $data) {
            $stmt = $db->prepare(
                "SELECT id FROM siswa.fisik_kesehatan_siswa WHERE siswa_id::text = ? AND semester = ? LIMIT 1"
            );
            $stmt->execute([$idSiswa, $semester]);
            $existingId = $stmt->fetchColumn();

            $tinggi       = !empty($data['tinggi_badan'])   ? (int) $data['tinggi_badan']   : null;
            $berat        = !empty($data['berat_badan'])    ? (int) $data['berat_badan']    : null;
            $pendengaran  = !empty($data['pendengaran'])    ? $data['pendengaran']           : null;
            $pengelihatan = !empty($data['pengelihatan'])   ? $data['pengelihatan']          : null;
            $gigi         = !empty($data['gigi'])           ? $data['gigi']                  : null;

            if ($existingId) {
                $db->prepare(
                    "UPDATE siswa.fisik_kesehatan_siswa SET
                        tinggi_badan=:tb, berat_badan=:bb, pendengaran=:pd, pengelihatan=:pe, gigi=:gi
                     WHERE id = :id"
                )->execute([
                    'tb' => $tinggi, 'bb' => $berat,
                    'pd' => $pendengaran, 'pe' => $pengelihatan, 'gi' => $gigi,
                    'id' => $existingId,
                ]);
            } else {
                if ($tinggi || $berat || $pendengaran || $pengelihatan || $gigi) {
                    $db->prepare(
                        "INSERT INTO siswa.fisik_kesehatan_siswa
                            (id, siswa_id, semester, tinggi_badan, berat_badan, pendengaran, pengelihatan, gigi)
                         VALUES (gen_random_uuid(), :siswa_id, :semester, :tb, :bb, :pd, :pe, :gi)"
                    )->execute([
                        'siswa_id' => $idSiswa, 'semester' => $semester,
                        'tb' => $tinggi, 'bb' => $berat,
                        'pd' => $pendengaran, 'pe' => $pengelihatan, 'gi' => $gigi,
                    ]);
                }
            }
        }
    }

    // ═══════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Save or update data in all student sub-tables (native PostgreSQL multi-schema).
     */
    private function saveOrUpdateSubTables(\PDO $db, string $idSiswa, array $data, bool $isCreate): void {
        $subTables = [
            'rincian_pelajar' => [
                'lingkar_kepala'       => (int)  ($data['lingkar_kepala']     ?? 0),
                'tinggi_badan'         => (int)  ($data['tinggi_badan']       ?? 0),
                'berat_badan'          => (int)  ($data['berat_badan']        ?? 0),
                'golongan_darah'       => $data['golongan_darah']              ?? 'A',
                'anak_ke'              => (int)  ($data['anak_ke']            ?? 1),
                'jarak_rumah'          => (int)  ($data['jarak_rumah']        ?? 0),
                'transportasi'         => $data['transportasi']               ?? 'Lainnya',
                'jumlah_saudara'       => (int)  ($data['jumlah_saudara']     ?? 0),
                'saudara_tiri'         => (int)  ($data['saudara_tiri']       ?? 0),
                'saudara_angkat'       => (int)  ($data['saudara_angkat']     ?? 0),
                'penyakit_yang_diderita' => $data['penyakit_yang_diderita']   ?? null,
                'foto_profil'          => $data['foto_profil']                ?? null,
                'kelainan_jasmani'     => $data['kelainan_jasmani']           ?? 'Tidak Ada',
            ],
            'rincian_alamat' => [
                'id_kelurahan'   => !empty($data['id_kelurahan']) ? (int) $data['id_kelurahan'] : null,
                'alamat_kk'      => $data['alamat_kk']            ?? '',
                'alamat_domisili'=> $data['alamat_domisili']       ?? '',
                'rt'             => $data['rt']                    ?? '',
                'rw'             => $data['rw']                    ?? '',
                'kode_pos'       => $data['kode_pos']              ?? '',
                'status_tinggal' => $data['status_tinggal']        ?? 'Lainnya',
                'tinggal_dengan' => $data['tinggal_dengan']        ?? 'Orang Tua',
            ],
            'orang_tua' => [
                'id_tempat_lahir_ayah'  => !empty($data['id_tempat_lahir_ayah']) ? (int) $data['id_tempat_lahir_ayah'] : null,
                'nik_ayah'              => $data['nik_ayah']              ?? null,
                'nama_ayah'             => $data['nama_ayah']             ?? null,
                'tahun_lahir_ayah'      => !empty($data['tahun_lahir_ayah']) ? (int) $data['tahun_lahir_ayah'] : null,
                'pendidikan_ayah'       => $data['pendidikan_ayah']       ?? null,
                'pekerjaan_ayah'        => $data['pekerjaan_ayah']        ?? null,
                'penghasilan_ayah'      => $data['penghasilan_ayah']      ?? null,
                'agama_ayah'            => $data['agama_ayah']            ?? null,
                'tanggal_lahir_ayah'    => $data['tanggal_lahir_ayah']    ?? null,
                'kewarganegaraan_ayah'  => $data['kewarganegaraan_ayah']  ?? 'WNI',
                'status_hidup_ayah'     => $data['status_hidup_ayah']     ?? 'Hidup',
                'id_tempat_lahir_ibu'   => !empty($data['id_tempat_lahir_ibu']) ? (int) $data['id_tempat_lahir_ibu'] : null,
                'nik_ibu'               => $data['nik_ibu']               ?? '',
                'nama_ibu'              => $data['nama_ibu']              ?? '',
                'tahun_lahir_ibu'       => !empty($data['tahun_lahir_ibu']) ? (int) $data['tahun_lahir_ibu'] : null,
                'pendidikan_ibu'        => $data['pendidikan_ibu']        ?? 'SMP',
                'pekerjaan_ibu'         => $data['pekerjaan_ibu']         ?? 'Tidak Bekerja',
                'penghasilan_ibu'       => $data['penghasilan_ibu']       ?? 'Tidak Berpenghasilan',
                'agama_ibu'             => $data['agama_ibu']             ?? 'Islam',
                'tanggal_lahir_ibu'     => $data['tanggal_lahir_ibu']     ?? null,
                'kewarganegaraan_ibu'   => $data['kewarganegaraan_ibu']   ?? 'WNI',
                'status_hidup_ibu'      => $data['status_hidup_ibu']      ?? 'Hidup',
                'id_tempat_lahir_wali'  => !empty($data['id_tempat_lahir_wali']) ? (int) $data['id_tempat_lahir_wali'] : null,
                'nik_wali'              => $data['nik_wali']              ?? null,
                'nama_wali'             => $data['nama_wali']             ?? null,
                'tahun_lahir_wali'      => !empty($data['tahun_lahir_wali']) ? (int) $data['tahun_lahir_wali'] : null,
                'pendidikan_wali'       => $data['pendidikan_wali']       ?? null,
                'pekerjaan_wali'        => $data['pekerjaan_wali']        ?? null,
                'penghasilan_wali'      => $data['penghasilan_wali']      ?? null,
                'agama_wali'            => $data['agama_wali']            ?? null,
                'tanggal_lahir_wali'    => $data['tanggal_lahir_wali']    ?? null,
                'kewarganegaraan_wali'  => $data['kewarganegaraan_wali']  ?? null,
                'hubungan_wali'         => $data['hubungan_wali']         ?? null,
            ],
            'kontak' => [
                'email'                 => $data['email']                  ?? '',
                'no_telepon_rumah'      => $data['no_telepon_rumah']       ?? null,
                'no_telepon_orang_tua'  => $data['no_telepon_orang_tua']   ?? null,
                'no_telepon_siswa'      => $data['no_telepon_siswa']       ?? '',
            ],
            'kip' => [
                'penerima_kps' => isset($data['penerima_kps']) ? (int) $data['penerima_kps'] : 0,
                'punya_kip'    => isset($data['punya_kip'])    ? (int) $data['punya_kip']    : 0,
                'layak_kip'    => isset($data['layak_kip'])    ? (int) $data['layak_kip']    : 0,
                'alasan_layak' => $data['alasan_layak']        ?? 'Tidak Ada',
                'no_kip'       => $data['no_kip']              ?? null,
                'status_anak'  => $data['status_anak']         ?? null,
            ],
            'registrasi' => [
                'jalur_diterima'        => $data['jalur_diterima']     ?? null,
                'jenis_pendaftaran'     => $data['jenis_pendaftaran']  ?? 'Siswa Baru',
                'tanggal_masuk'         => $data['tanggal_masuk']      ?? date('Y-m-d'),
                'paud_formal'           => isset($data['paud_formal'])     ? (int) $data['paud_formal']    : 1,
                'paud_non_formal'       => isset($data['paud_non_formal']) ? (int) $data['paud_non_formal'] : 0,
                'hobi'                  => $data['hobi']               ?? '',
                'keluar_karena'         => $data['keluar_karena']      ?? null,
                'tanggal_keluar'        => $data['tanggal_keluar']     ?? null,
                'alasan_keluar'         => $data['alasan_keluar']      ?? null,
                'sekolah_asal_mutasi'   => $data['sekolah_asal_mutasi'] ?? null,
                'pindah_dari_tingkat'   => $data['pindah_dari_tingkat'] ?? null,
                'pindah_no_surat'       => $data['pindah_no_surat']    ?? null,
                'tingkat_ditinggalkan'  => $data['tingkat_ditinggalkan'] ?? null,
                'diterima_di_tingkat'   => $data['diterima_di_tingkat'] ?? null,
                'sekolah_tujuan'        => $data['sekolah_tujuan']     ?? null,
                'nomor_skp'             => $data['nomor_skp']          ?? null,
            ],
            'dokumen' => [
                'berkas_kk'               => $data['berkas_kk']               ?? null,
                'berkas_akta'             => $data['berkas_akta']             ?? null,
                'berkas_ijazah_sd'        => $data['berkas_ijazah_sd']        ?? null,
                'berkas_ijazah_smp'       => $data['berkas_ijazah_smp']       ?? null,
                'berkas_ijazah_sma'       => $data['berkas_ijazah_sma']       ?? null,
                'berkas_mutasi_masuk'     => $data['berkas_mutasi_masuk']     ?? null,
                'berkas_mutasi_keluar'    => $data['berkas_mutasi_keluar']    ?? null,
                'berkas_kip'              => $data['berkas_kip']              ?? null,
                'berkas_pernyataan_baru'  => $data['berkas_pernyataan_baru']  ?? null,
                'berkas_pernyataan_tka'   => $data['berkas_pernyataan_tka']   ?? null,
                'file_sizes'              => !empty($data['file_sizes'])
                    ? (is_array($data['file_sizes']) ? json_encode($data['file_sizes']) : $data['file_sizes'])
                    : null,
            ],
        ];

        // Required fields per sub-tabel (untuk cek sebelum INSERT baru pada operasi update)
        $requiredKeys = [
            'rincian_pelajar' => ['tinggi_badan', 'berat_badan'],
            'rincian_alamat'  => ['alamat_kk', 'id_kelurahan'],
            'orang_tua'       => ['nama_ibu', 'nik_ibu'],
            'kontak'          => ['email', 'no_telepon_siswa'],
            'kip'             => ['punya_kip', 'layak_kip'],
            'registrasi'      => ['jenis_pendaftaran', 'hobi'],
            'dokumen'         => ['berkas_kk', 'berkas_akta'],
        ];

        foreach ($subTables as $table => $cols) {
            // Saring kolom yang benar-benar ada di payload $data
            $passedCols = [];
            foreach ($cols as $colName => $processedValue) {
                if (array_key_exists($colName, $data)) {
                    $passedCols[$colName] = $processedValue;
                }
            }

            // Jika update dan tidak ada kolom yang dikirim untuk tabel ini, lewati
            if (!$isCreate && empty($passedCols)) continue;

            $stmt = $db->prepare("SELECT COUNT(*) FROM siswa.{$table} WHERE id_siswa::text = ?");
            $stmt->execute([$idSiswa]);
            $exists = $stmt->fetchColumn() > 0;

            if ($exists) {
                if (empty($passedCols)) continue;
                $setParts = [];
                $params   = [];
                foreach ($passedCols as $colName => $value) {
                    $setParts[]         = "{$colName} = :{$colName}";
                    $params[$colName]   = $value;
                }
                $params['id_siswa'] = $idSiswa;
                $db->prepare(
                    "UPDATE siswa.{$table} SET " . implode(', ', $setParts) . " WHERE id_siswa::text = :id_siswa"
                )->execute($params);
            } else {
                // Pada operasi UPDATE, cek dulu apakah ada minimal 1 field wajib yang dikirim
                if (!$isCreate && isset($requiredKeys[$table])) {
                    $hasRequired = false;
                    foreach ($requiredKeys[$table] as $reqKey) {
                        if (array_key_exists($reqKey, $data) && $data[$reqKey] !== '') {
                            $hasRequired = true;
                            break;
                        }
                    }
                    if (!$hasRequired) continue;
                }

                // INSERT dengan semua kolom default dari definisi $cols
                $insertData = $cols; // gunakan nilai default dari $cols
                $colNames   = array_keys($insertData);
                $placeholders = array_map(fn($c) => ":{$c}", $colNames);
                $colNames[]   = 'id_siswa';
                $placeholders[] = ':id_siswa';
                $params       = $insertData;
                $params['id_siswa'] = $idSiswa;

                $db->prepare(
                    "INSERT INTO siswa.{$table} (" . implode(', ', $colNames) . ")
                     VALUES (" . implode(', ', $placeholders) . ")"
                )->execute($params);
            }
        }
    }

    /**
     * Generate UUID v4 (PHP native)
     */
    private function generateUuidV4(): string {
        $bytes    = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
