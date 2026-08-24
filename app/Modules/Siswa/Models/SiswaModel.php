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
    protected static string $table  = 'siswa.siswa';
    protected static string $schema = 'siswa.siswa';
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
                       COALESCE(j.nama_jurusan, s.jurusan, '-') as nama_jurusan,
                       k.id::text AS id_kelas,
                       j.id::text AS id_jurusan,
                       k.id_jenjang::text AS id_jenjang,
                       (SELECT id::text FROM akademik.angkatan WHERE tenant_id = s.tenant_id AND (nama_angkatan = s.angkatan::text OR id::text = s.angkatan::text OR nama_angkatan = 'Angkatan ' || s.angkatan::text) LIMIT 1) AS id_angkatan,
                       (SELECT id::text FROM akademik.tahun_ajaran WHERE tenant_id = s.tenant_id AND is_active = true LIMIT 1) AS id_tahun_ajaran,
                       (SELECT id::text FROM akademik.pendidikan WHERE (tenant_id = s.tenant_id OR tenant_id IS NULL) LIMIT 1) AS id_pendidikan
                FROM {$table} s
                LEFT JOIN akademik.kelas k ON (s.tenant_id = k.tenant_id AND (s.kelas_saat_ini = k.id::text OR s.kelas_saat_ini = k.nama_kelas OR s.kelas_saat_ini = k.kode_kelas))
                LEFT JOIN core.tenants t ON s.tenant_id = t.id
                LEFT JOIN akademik.jurusan j ON (s.tenant_id = j.tenant_id AND (s.jurusan = j.id::text OR s.jurusan = j.nama_jurusan))
                WHERE (s.id::text = :id OR s.id IN (SELECT siswa_id FROM siswa.registrasi WHERE id::text = :id))";

        $hasTenantFilter = (!empty($this->tenantId) && $this->tenantId !== 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12');
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
        if (!$result) return null;

        $actualSiswaId = $result['id'];

        // Sub-table: siswa.orang_tua (Ayah, Ibu, Wali)
        $sqlOrtu = "SELECT * FROM siswa.orang_tua WHERE siswa_id::text = :sid";
        $paramsOrtu = [':sid' => $actualSiswaId];
        if ($hasTenantFilter) {
            $sqlOrtu .= " AND tenant_id = :tenant_id";
            $paramsOrtu[':tenant_id'] = $this->tenantId;
        }
        $stOrtu = self::getPdo()->prepare($sqlOrtu);
        $stOrtu->execute($paramsOrtu);
        $rowsOrtu = $stOrtu->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rowsOrtu as $r) {
            $h = strtolower($r['hubungan'] ?? '');
            if ($h === 'ayah') {
                $result['nama_ayah']             = $r['nama_lengkap'] ?? '';
                $result['nik_ayah']              = $r['nik'] ?? '';
                $result['pekerjaan_ayah']        = $r['pekerjaan'] ?? '';
                $result['pendidikan_ayah']       = self::normalizePendidikan($r['pendidikan'] ?? '');
                $result['penghasilan_ayah']      = self::formatPenghasilanToRange($r['penghasilan'] ?? null);
                $result['penghasilan_ayah_raw']  = $r['penghasilan'] ?? '';
                $result['tahun_lahir_ayah']      = $r['tahun_lahir'] ?? '';
                $result['tanggal_lahir_ayah']    = $r['tanggal_lahir'] ?? '';
                $result['agama_ayah']            = $r['agama'] ?? '';
                $result['kewarganegaraan_ayah']  = $r['kewarganegaraan'] ?? 'WNI';
                $result['status_hidup_ayah']     = $r['status_hidup'] ?? 'Hidup';
                $result['tempat_lahir_ayah']     = $r['tempat_lahir'] ?? '';
                $result['id_tempat_lahir_ayah']  = $r['id_tempat_lahir'] ?? '';
                if (empty($result['no_telepon_orang_tua']) && !empty($r['no_hp'])) {
                    $result['no_telepon_orang_tua'] = $r['no_hp'];
                }
            } elseif ($h === 'ibu') {
                $result['nama_ibu']              = $r['nama_lengkap'] ?? '';
                $result['nik_ibu']               = $r['nik'] ?? '';
                $result['pekerjaan_ibu']         = $r['pekerjaan'] ?? '';
                $result['pendidikan_ibu']        = self::normalizePendidikan($r['pendidikan'] ?? '');
                $result['penghasilan_ibu']       = self::formatPenghasilanToRange($r['penghasilan'] ?? null);
                $result['penghasilan_ibu_raw']   = $r['penghasilan'] ?? '';
                $result['tahun_lahir_ibu']       = $r['tahun_lahir'] ?? '';
                $result['tanggal_lahir_ibu']     = $r['tanggal_lahir'] ?? '';
                $result['agama_ibu']             = $r['agama'] ?? '';
                $result['kewarganegaraan_ibu']   = $r['kewarganegaraan'] ?? 'WNI';
                $result['status_hidup_ibu']      = $r['status_hidup'] ?? 'Hidup';
                $result['tempat_lahir_ibu']      = $r['tempat_lahir'] ?? '';
                $result['id_tempat_lahir_ibu']   = $r['id_tempat_lahir'] ?? '';
                if (empty($result['no_telepon_orang_tua']) && !empty($r['no_hp'])) {
                    $result['no_telepon_orang_tua'] = $r['no_hp'];
                }
            } elseif ($h === 'wali') {
                $result['nama_wali']             = $r['nama_lengkap'] ?? '';
                $result['nik_wali']              = $r['nik'] ?? '';
                $result['pekerjaan_wali']        = $r['pekerjaan'] ?? '';
                $result['pendidikan_wali']       = self::normalizePendidikan($r['pendidikan'] ?? '');
                $result['penghasilan_wali']      = self::formatPenghasilanToRange($r['penghasilan'] ?? null);
                $result['penghasilan_wali_raw']  = $r['penghasilan'] ?? '';
                $result['tahun_lahir_wali']      = $r['tahun_lahir'] ?? '';
                $result['tanggal_lahir_wali']    = $r['tanggal_lahir'] ?? '';
                $result['agama_wali']            = $r['agama'] ?? '';
                $result['kewarganegaraan_wali']  = $r['kewarganegaraan'] ?? 'WNI';
                $result['hubungan_wali']         = $r['hubungan_wali'] ?? '';
                $result['tempat_lahir_wali']     = $r['tempat_lahir'] ?? '';
                $result['id_tempat_lahir_wali']  = $r['id_tempat_lahir'] ?? '';
                if (empty($result['no_telepon_orang_tua']) && !empty($r['no_hp'])) {
                    $result['no_telepon_orang_tua'] = $r['no_hp'];
                }
            }
        }

        // Sub-table: siswa.registrasi
        $sqlReg = "SELECT * FROM siswa.registrasi WHERE siswa_id::text = :sid";
        $paramsReg = [':sid' => $actualSiswaId];
        if ($hasTenantFilter) {
            $sqlReg .= " AND tenant_id = :tenant_id";
            $paramsReg[':tenant_id'] = $this->tenantId;
        }
        $sqlReg .= " LIMIT 1";
        $stReg = self::getPdo()->prepare($sqlReg);
        $stReg->execute($paramsReg);
        $rowReg = $stReg->fetch(PDO::FETCH_ASSOC);
        if ($rowReg) {
            $result['id_registrasi'] = $rowReg['id'];
            foreach ($rowReg as $rk => $rv) {
                if (in_array($rk, ['id', 'siswa_id', 'tenant_id', 'created_at', 'updated_at'], true)) {
                    continue;
                }
                if ($rv !== null) $result[$rk] = $rv;
            }
            if (!empty($rowReg['asal_sekolah'])) {
                $result['sekolah_asal'] = $rowReg['asal_sekolah'];
            }
        }

        // Sub-table: siswa.fisik_kesehatan_siswa
        $sqlFisik = "SELECT * FROM siswa.fisik_kesehatan_siswa WHERE siswa_id::text = :sid";
        $paramsFisik = [':sid' => $actualSiswaId];
        if ($hasTenantFilter) {
            $sqlFisik .= " AND tenant_id = :tenant_id";
            $paramsFisik[':tenant_id'] = $this->tenantId;
        }
        $sqlFisik .= " LIMIT 1";
        $stFisik = self::getPdo()->prepare($sqlFisik);
        $stFisik->execute($paramsFisik);
        $rowFisik = $stFisik->fetch(PDO::FETCH_ASSOC);
        if ($rowFisik) {
            if (!empty($rowFisik['tinggi_badan'])) $result['tinggi_badan'] = $rowFisik['tinggi_badan'];
            if (!empty($rowFisik['berat_badan'])) $result['berat_badan'] = $rowFisik['berat_badan'];
            if (!empty($rowFisik['lingkar_kepala'])) $result['lingkar_kepala'] = $rowFisik['lingkar_kepala'];
            if (!empty($rowFisik['golongan_darah'])) $result['golongan_darah'] = $rowFisik['golongan_darah'];
            if (!empty($rowFisik['riwayat_penyakit'])) $result['penyakit_yang_diderita'] = $rowFisik['riwayat_penyakit'];
            if (!empty($rowFisik['disabilitas'])) $result['kelainan_jasmani'] = $rowFisik['disabilitas'];
            if (!empty($rowFisik['detail_semester'])) {
                $result['kesehatan'] = is_string($rowFisik['detail_semester']) ? json_decode($rowFisik['detail_semester'], true) : $rowFisik['detail_semester'];
            }
        }

        // Sub-table: siswa.dokumen
        $sqlDocs = "SELECT jenis_dokumen, url_file, nama_file FROM siswa.dokumen WHERE siswa_id::text = :sid";
        $paramsDocs = [':sid' => $actualSiswaId];
        if ($hasTenantFilter) {
            $sqlDocs .= " AND tenant_id = :tenant_id";
            $paramsDocs[':tenant_id'] = $this->tenantId;
        }
        $stDocs = self::getPdo()->prepare($sqlDocs);
        $stDocs->execute($paramsDocs);
        $rowsDocs = $stDocs->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rowsDocs as $doc) {
            if (!empty($doc['jenis_dokumen']) && !empty($doc['url_file'])) {
                $result[$doc['jenis_dokumen']] = $doc['url_file'];
            }
        }
        if (!empty($result['foto_url']) && empty($result['foto_profil'])) {
            $result['foto_profil'] = $result['foto_url'];
        }

        $result['id'] = $actualSiswaId;
        return $result;
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
        $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($res && !empty($res['detail_semester'])) {
            $semData = is_string($res['detail_semester']) ? json_decode($res['detail_semester'], true) : $res['detail_semester'];
            if (is_array($semData)) {
                $res['semester'] = $semData;
            }
        }
        return $res;
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
        $isSuperAdmin = (empty($this->tenantId) || $this->tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12');
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
                        angkatan, jurusan, kelas_saat_ini, status_siswa, password,
                        nik, no_kk, nama_panggilan, kewarganegaraan, bahasa_sehari_hari,
                        ukuran_seragam_sekolah, ukuran_seragam_olahraga,
                        alamat_domisili, rt, rw, kode_pos,
                        id_provinsi, id_kota, id_kecamatan, id_kelurahan,
                        status_tinggal, tinggal_dengan, no_telepon_rumah, no_telepon_orang_tua,
                        anak_ke, jumlah_saudara, saudara_tiri, saudara_angkat,
                        status_anak, jarak_rumah, transportasi,
                        penerima_kps, punya_kip, layak_kip, no_kip, alasan_layak
                    ) VALUES (
                        :id, :tenant_id, :nisn, :nis, :nama_lengkap,
                        :tempat_lahir, :tanggal_lahir, :jenis_kelamin, :alamat, :agama,
                        :angkatan, :jurusan, :kelas_saat_ini, :status_siswa, :password,
                        :nik, :no_kk, :nama_panggilan, :kewarganegaraan, :bahasa_sehari_hari,
                        :ukuran_seragam_sekolah, :ukuran_seragam_olahraga,
                        :alamat_domisili, :rt, :rw, :kode_pos,
                        :id_provinsi, :id_kota, :id_kecamatan, :id_kelurahan,
                        :status_tinggal, :tinggal_dengan, :no_telepon_rumah, :no_telepon_orang_tua,
                        :anak_ke, :jumlah_saudara, :saudara_tiri, :saudara_angkat,
                        :status_anak, :jarak_rumah, :transportasi,
                        :penerima_kps, :punya_kip, :layak_kip, :no_kip, :alasan_layak
                    )";

            $angkatanVal = $data['id_angkatan'] ?? ($data['angkatan'] ?? null);
            if ($angkatanVal !== null) {
                if (is_numeric($angkatanVal)) {
                    $angkatanVal = (int) $angkatanVal;
                } else {
                    $hasTenant = !empty($this->tenantId) && $this->tenantId !== 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
                    $sqlAng = "SELECT nama_angkatan FROM akademik.angkatan WHERE id::text = :aid";
                    $paramsAng = ['aid' => (string)$angkatanVal];
                    if ($hasTenant) {
                        $sqlAng .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
                        $paramsAng['tenant_id'] = $this->tenantId;
                    }
                    $sqlAng .= " LIMIT 1";
                    $stAng = $db->prepare($sqlAng);
                    $stAng->execute($paramsAng);
                    $namaAng = $stAng->fetchColumn();
                    if ($namaAng && preg_match('/\d{4}/', $namaAng, $m)) {
                        $angkatanVal = (int) $m[0];
                    } else {
                        $angkatanVal = (int) date('Y');
                    }
                }
            }

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
                'angkatan'      => $angkatanVal,
                'jurusan'       => $data['id_jurusan'] ?? ($data['jurusan'] ?? null),
                'kelas_saat_ini'=> $data['id_kelas'] ?? ($data['kelas_saat_ini'] ?? null),
                'status_siswa'  => $data['status'] ?? ($data['status_siswa'] ?? 'Aktif'),
                'password'      => !empty($data['password'])
                                    ? password_hash($data['password'], PASSWORD_BCRYPT)
                                    : password_hash($data['tanggal_lahir'] ?? '123456', PASSWORD_BCRYPT),
                'nik'           => !empty($data['nik']) ? $data['nik'] : null,
                'no_kk'         => !empty($data['no_kk']) ? $data['no_kk'] : null,
                'nama_panggilan'=> $data['nama_panggilan'] ?? null,
                'kewarganegaraan'=> $data['kewarganegaraan'] ?? 'WNI',
                'bahasa_sehari_hari' => $data['bahasa_sehari_hari'] ?? null,
                'ukuran_seragam_sekolah' => $data['ukuran_seragam_sekolah'] ?? null,
                'ukuran_seragam_olahraga' => $data['ukuran_seragam_olahraga'] ?? null,
                'alamat_domisili' => $data['alamat_domisili'] ?? null,
                'rt'            => $data['rt'] ?? null,
                'rw'            => $data['rw'] ?? null,
                'kode_pos'      => $data['kode_pos'] ?? null,
                'id_provinsi'   => !empty($data['id_provinsi']) ? (int)$data['id_provinsi'] : null,
                'id_kota'       => !empty($data['id_kota']) ? (int)$data['id_kota'] : null,
                'id_kecamatan'  => !empty($data['id_kecamatan']) ? (int)$data['id_kecamatan'] : null,
                'id_kelurahan'  => !empty($data['id_kelurahan']) ? (int)$data['id_kelurahan'] : null,
                'status_tinggal'=> $data['status_tinggal'] ?? null,
                'tinggal_dengan'=> $data['tinggal_dengan'] ?? 'Orang Tua',
                'no_telepon_rumah' => $data['no_telepon_rumah'] ?? null,
                'no_telepon_orang_tua' => $data['no_telepon_orang_tua'] ?? null,
                'anak_ke'       => !empty($data['anak_ke']) ? (int)$data['anak_ke'] : null,
                'jumlah_saudara'=> isset($data['jumlah_saudara']) && $data['jumlah_saudara'] !== '' ? (int)$data['jumlah_saudara'] : null,
                'saudara_tiri'  => isset($data['saudara_tiri']) && $data['saudara_tiri'] !== '' ? (int)$data['saudara_tiri'] : null,
                'saudara_angkat'=> isset($data['saudara_angkat']) && $data['saudara_angkat'] !== '' ? (int)$data['saudara_angkat'] : null,
                'status_anak'   => $data['status_anak'] ?? null,
                'jarak_rumah'   => !empty($data['jarak_rumah']) ? (int)$data['jarak_rumah'] : null,
                'transportasi'  => $data['transportasi'] ?? null,
                'penerima_kps'  => !empty($data['penerima_kps']) ? 'true' : 'false',
                'punya_kip'     => !empty($data['punya_kip']) ? 'true' : 'false',
                'layak_kip'     => !empty($data['layak_kip']) ? 'true' : 'false',
                'no_kip'        => $data['no_kip'] ?? null,
                'alasan_layak'  => $data['alasan_layak'] ?? null,
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

            // Kolom yang ada di tabel siswa.siswa utama
            $siswaCols = [
                'nisn', 'nis', 'nama_lengkap', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
                'agama', 'foto_url', 'email', 'no_hp',
                'nik', 'no_kk', 'nama_panggilan', 'kewarganegaraan', 'bahasa_sehari_hari',
                'ukuran_seragam_sekolah', 'ukuran_seragam_olahraga',
                'alamat_domisili', 'rt', 'rw', 'kode_pos',
                'id_provinsi', 'id_kota', 'id_kecamatan', 'id_kelurahan',
                'status_tinggal', 'tinggal_dengan', 'no_telepon_rumah', 'no_telepon_orang_tua',
                'anak_ke', 'jumlah_saudara', 'saudara_tiri', 'saudara_angkat',
                'status_anak', 'jarak_rumah', 'transportasi',
                'penerima_kps', 'punya_kip', 'layak_kip', 'no_kip', 'alasan_layak'
            ];

            $setParts = [];
            $params   = ['id' => $id];

            $isSuperAdmin = (empty($this->tenantId) || $this->tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12');
            if (!$isSuperAdmin) {
                $params['tenant_id'] = $this->tenantId;
            }

            foreach ($siswaCols as $col) {
                if (array_key_exists($col, $data)) {
                    $setParts[]     = "{$col} = :{$col}";
                    $val = $data[$col];
                    if (in_array($col, ['penerima_kps', 'punya_kip', 'layak_kip'], true)) {
                        $params[$col] = !empty($val) ? 'true' : 'false';
                    } elseif (in_array($col, ['id_provinsi', 'id_kota', 'id_kecamatan', 'id_kelurahan', 'anak_ke', 'jumlah_saudara', 'saudara_tiri', 'saudara_angkat', 'jarak_rumah'], true)) {
                        $params[$col] = ($val !== '' && $val !== null) ? (int)$val : null;
                    } else {
                        $params[$col] = ($val !== '' && $val !== null) ? $val : null;
                    }
                }
            }

            // Mapped columns prioritizing non-empty Vue inputs over empty legacy fields
            $mappedFields = [
                'status_siswa'   => ['id_status', 'status_siswa', 'status'],
                'kelas_saat_ini' => ['id_kelas', 'kelas_saat_ini', 'kelas'],
                'jurusan'        => ['id_jurusan', 'jurusan'],
                'angkatan'       => ['id_angkatan', 'angkatan'],
                'alamat'         => ['alamat_kk', 'alamat_domisili', 'alamat'],
            ];

            foreach ($mappedFields as $dbCol => $possibleKeys) {
                $val = null;
                $keyFound = false;
                foreach ($possibleKeys as $pk) {
                    if (array_key_exists($pk, $data)) {
                        $keyFound = true;
                        if ($data[$pk] !== '' && $data[$pk] !== null) {
                            $val = $data[$pk];
                            break;
                        }
                    }
                }

                if ($keyFound && !isset($params[$dbCol])) {
                    if ($dbCol === 'angkatan' && $val !== null) {
                        if (is_numeric($val)) {
                            $val = (int) $val;
                        } else {
                            $hasTenant = !empty($this->tenantId) && $this->tenantId !== 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
                            $sqlAng = "SELECT nama_angkatan FROM akademik.angkatan WHERE id::text = :aid";
                            $paramsAng = ['aid' => (string)$val];
                            if ($hasTenant) {
                                $sqlAng .= " AND (tenant_id = :tenant_id OR tenant_id IS NULL)";
                                $paramsAng['tenant_id'] = $this->tenantId;
                            }
                            $sqlAng .= " LIMIT 1";
                            $stAng = $db->prepare($sqlAng);
                            $stAng->execute($paramsAng);
                            $namaAng = $stAng->fetchColumn();
                            if ($namaAng && preg_match('/\d{4}/', $namaAng, $m)) {
                                $val = (int) $m[0];
                            } else {
                                $val = (int) date('Y');
                            }
                        }
                    }
                    if ($dbCol === 'status_siswa' && $val === null) {
                        $val = 'Aktif';
                    }
                    $setParts[]     = "{$dbCol} = :{$dbCol}";
                    $params[$dbCol] = $val;
                }
            }

            // Password
            if (array_key_exists('password', $data) && !empty($data['password'])) {
                $setParts[]        = "password = :password";
                $params['password'] = $data['password']; // sudah di-hash di controller
                $setParts[]        = "is_first_login = false";
            }

            if (!empty($setParts)) {
                $sql = "UPDATE siswa.siswa SET " . implode(', ', $setParts) . " WHERE id::text = :id AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)";
                $params['tenant_id'] = $this->tenantId;
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
     * Nonaktifkan siswa (Soft Delete)
     */
    public function delete(string $id): bool {
        $db = self::getPdo();
        $sql = "UPDATE siswa.siswa SET is_active = false, updated_at = CURRENT_TIMESTAMP WHERE id::text = :id AND (:tenant_id::uuid IS NULL OR tenant_id = :tenant_id::uuid OR tenant_id IS NULL)";
        $params = [
            'id'        => $id,
            'tenant_id' => $this->tenantId
        ];
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Simpan / update data kesehatan siswa (tabel fisik_kesehatan_siswa).
     */
    public function saveKesehatanSiswa(string $idSiswa, array $kesehatanData): void {
        $db = self::getPdo();
        $stmtT = $db->prepare("SELECT tenant_id FROM siswa.siswa WHERE id::text = :sid LIMIT 1");
        $stmtT->execute(['sid' => $idSiswa]);
        $tenantId = $stmtT->fetchColumn() ?: $this->tenantId;

        $tb = null; $bb = null; $lk = null; $goldar = null; $penyakit = null; $alergi = null; $disab = null;
        $detailSemesterJson = null;

        if (isset($kesehatanData['tinggi_badan']) || isset($kesehatanData['berat_badan']) || isset($kesehatanData['golongan_darah'])) {
            $data = $kesehatanData;
            $tb       = !empty($data['tinggi_badan']) ? (int)$data['tinggi_badan'] : null;
            $bb       = !empty($data['berat_badan']) ? (int)$data['berat_badan'] : null;
            $lk       = !empty($data['lingkar_kepala']) ? (int)$data['lingkar_kepala'] : null;
            $goldar   = $data['golongan_darah'] ?? null;
            $penyakit = $data['riwayat_penyakit'] ?? ($data['penyakit_yang_diderita'] ?? null);
            $alergi   = $data['alergi'] ?? null;
            $disab    = $data['disabilitas'] ?? ($data['kelainan_jasmani'] ?? null);
        } else {
            $detailSemesterJson = json_encode($kesehatanData);
            foreach ($kesehatanData as $sem => $data) {
                if (is_array($data)) {
                    if (!empty($data['tinggi_badan'])) $tb = (int)$data['tinggi_badan'];
                    if (!empty($data['berat_badan'])) $bb = (int)$data['berat_badan'];
                    if (!empty($data['lingkar_kepala'])) $lk = (int)$data['lingkar_kepala'];
                    if (!empty($data['golongan_darah'])) $goldar = $data['golongan_darah'];
                    if (!empty($data['riwayat_penyakit']) || !empty($data['penyakit_yang_diderita'])) {
                        $penyakit = $data['riwayat_penyakit'] ?? $data['penyakit_yang_diderita'];
                    }
                    if (!empty($data['alergi'])) $alergi = $data['alergi'];
                    if (!empty($data['disabilitas']) || !empty($data['kelainan_jasmani'])) {
                        $disab = $data['disabilitas'] ?? $data['kelainan_jasmani'];
                    }
                }
            }
        }

        $sqlK = "SELECT id FROM siswa.fisik_kesehatan_siswa WHERE siswa_id::text = :sid";
        $paramsK = ['sid' => $idSiswa];
        if (!empty($tenantId)) {
            $sqlK .= " AND tenant_id = :tenant_id";
            $paramsK['tenant_id'] = $tenantId;
        }
        $sqlK .= " LIMIT 1";
        $stK = $db->prepare($sqlK);
        $stK->execute($paramsK);
        $kId = $stK->fetchColumn();

        if ($kId) {
            $sqlUpdK = "UPDATE siswa.fisik_kesehatan_siswa SET 
                tinggi_badan = COALESCE(:tb, tinggi_badan), 
                berat_badan = COALESCE(:bb, berat_badan), 
                lingkar_kepala = COALESCE(:lk, lingkar_kepala),
                golongan_darah = COALESCE(:goldar, golongan_darah), 
                riwayat_penyakit = COALESCE(:penyakit, riwayat_penyakit), 
                alergi = COALESCE(:alergi, alergi), 
                disabilitas = COALESCE(:disab, disabilitas), 
                detail_semester = COALESCE(:detail_sem, detail_semester),
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";
            $updParamsK = [
                'tb' => $tb, 'bb' => $bb, 'lk' => $lk, 'goldar' => $goldar, 'penyakit' => $penyakit, 'alergi' => $alergi, 'disab' => $disab,
                'detail_sem' => $detailSemesterJson, 'id' => $kId
            ];
            if (!empty($tenantId)) {
                $sqlUpdK .= " AND tenant_id = :tenant_id";
                $updParamsK['tenant_id'] = $tenantId;
            }
            $db->prepare($sqlUpdK)->execute($updParamsK);
        } else if ($tb || $bb || $lk || $goldar || $penyakit || $alergi || $disab || $detailSemesterJson) {
            $db->prepare("INSERT INTO siswa.fisik_kesehatan_siswa (id, siswa_id, tenant_id, tinggi_badan, berat_badan, lingkar_kepala, golongan_darah, riwayat_penyakit, alergi, disabilitas, detail_semester) VALUES (gen_random_uuid(), :siswa_id, :tenant_id, :tb, :bb, :lk, :goldar, :penyakit, :alergi, :disab, :detail_sem)")->execute([
                'siswa_id' => $idSiswa, 'tenant_id' => $tenantId,
                'tb' => $tb, 'bb' => $bb, 'lk' => $lk, 'goldar' => $goldar, 'penyakit' => $penyakit, 'alergi' => $alergi, 'disab' => $disab,
                'detail_sem' => $detailSemesterJson
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Save or update data in all student sub-tables (native PostgreSQL multi-schema).
     */
    private function saveOrUpdateSubTables(\PDO $db, string $idSiswa, array $data, bool $isCreate): void {
        // Fetch student's tenant_id
        $stmtT = $db->prepare("SELECT tenant_id FROM siswa.siswa WHERE id::text = :sid LIMIT 1");
        $stmtT->execute(['sid' => $idSiswa]);
        $tenantId = $stmtT->fetchColumn() ?: $this->tenantId;

        // 1. Update foto_url, email, no_hp, alamat directly on siswa.siswa if present
        $mainUpdates = [];
        $mainParams  = ['id_siswa' => $idSiswa];
        if (array_key_exists('foto_profil', $data) && !empty($data['foto_profil'])) {
            $mainUpdates[] = "foto_url = :foto_url";
            $mainParams['foto_url'] = $data['foto_profil'];
        } elseif (array_key_exists('foto_url', $data) && !empty($data['foto_url'])) {
            $mainUpdates[] = "foto_url = :foto_url";
            $mainParams['foto_url'] = $data['foto_url'];
        }
        if (array_key_exists('email', $data) && $data['email'] !== '') {
            $mainUpdates[] = "email = :email";
            $mainParams['email'] = $data['email'];
        }
        if (array_key_exists('no_telepon_siswa', $data) && $data['no_telepon_siswa'] !== '') {
            $mainUpdates[] = "no_hp = :no_hp";
            $mainParams['no_hp'] = $data['no_telepon_siswa'];
        }
        if (array_key_exists('alamat_kk', $data) && $data['alamat_kk'] !== '') {
            $mainUpdates[] = "alamat = :alamat";
            $mainParams['alamat'] = $data['alamat_kk'];
        } elseif (array_key_exists('alamat_domisili', $data) && $data['alamat_domisili'] !== '') {
            $mainUpdates[] = "alamat = :alamat";
            $mainParams['alamat'] = $data['alamat_domisili'];
        }
        if (!empty($mainUpdates)) {
            $sqlMain = "UPDATE siswa.siswa SET " . implode(', ', $mainUpdates) . " WHERE id::text = :id_siswa";
            if (!empty($tenantId)) {
                $sqlMain .= " AND tenant_id = :tenant_id";
                $mainParams['tenant_id'] = $tenantId;
            }
            $db->prepare($sqlMain)->execute($mainParams);
        }

        // 2. Sub-table: siswa.fisik_kesehatan_siswa
        $kesehatanFields = ['tinggi_badan', 'berat_badan', 'lingkar_kepala', 'golongan_darah', 'penyakit_yang_diderita', 'riwayat_penyakit', 'alergi', 'disabilitas', 'kelainan_jasmani', 'kesehatan'];
        $hasKesehatan = false;
        foreach ($kesehatanFields as $kf) {
            if (array_key_exists($kf, $data)) {
                $hasKesehatan = true;
                break;
            }
        }
        if ($hasKesehatan || $isCreate) {
            $sqlK = "SELECT id FROM siswa.fisik_kesehatan_siswa WHERE siswa_id::text = :sid";
            $paramsK = ['sid' => $idSiswa];
            if (!empty($tenantId)) {
                $sqlK .= " AND tenant_id = :tenant_id";
                $paramsK['tenant_id'] = $tenantId;
            }
            $sqlK .= " LIMIT 1";
            $stK = $db->prepare($sqlK);
            $stK->execute($paramsK);
            $kId = $stK->fetchColumn();

            $tb       = !empty($data['tinggi_badan']) ? (int)$data['tinggi_badan'] : null;
            $bb       = !empty($data['berat_badan']) ? (int)$data['berat_badan'] : null;
            $lk       = !empty($data['lingkar_kepala']) ? (int)$data['lingkar_kepala'] : null;
            $goldar   = $data['golongan_darah'] ?? 'A';
            $penyakit = $data['penyakit_yang_diderita'] ?? ($data['riwayat_penyakit'] ?? null);
            $alergi   = $data['alergi'] ?? null;
            $disab    = $data['kelainan_jasmani'] ?? ($data['disabilitas'] ?? null);
            $detailSem= isset($data['kesehatan']) && is_array($data['kesehatan']) ? json_encode($data['kesehatan']) : null;

            if ($kId) {
                $sqlUpdK = "UPDATE siswa.fisik_kesehatan_siswa SET tinggi_badan = COALESCE(:tb, tinggi_badan), berat_badan = COALESCE(:bb, berat_badan), lingkar_kepala = COALESCE(:lk, lingkar_kepala), golongan_darah = COALESCE(:goldar, golongan_darah), riwayat_penyakit = COALESCE(:penyakit, riwayat_penyakit), alergi = COALESCE(:alergi, alergi), disabilitas = COALESCE(:disab, disabilitas), detail_semester = COALESCE(:detail_sem, detail_semester), updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $updParamsK = [
                    'tb' => $tb, 'bb' => $bb, 'lk' => $lk, 'goldar' => $goldar, 'penyakit' => $penyakit, 'alergi' => $alergi, 'disab' => $disab, 'detail_sem' => $detailSem, 'id' => $kId
                ];
                if (!empty($tenantId)) {
                    $sqlUpdK .= " AND tenant_id = :tenant_id";
                    $updParamsK['tenant_id'] = $tenantId;
                }
                $db->prepare($sqlUpdK)->execute($updParamsK);
            } else if ($tb || $bb || $lk || $penyakit || $alergi || $disab || $detailSem) {
                $db->prepare("INSERT INTO siswa.fisik_kesehatan_siswa (id, siswa_id, tenant_id, tinggi_badan, berat_badan, lingkar_kepala, golongan_darah, riwayat_penyakit, alergi, disabilitas, detail_semester) VALUES (:id, :siswa_id, :tenant_id, :tb, :bb, :lk, :goldar, :penyakit, :alergi, :disab, :detail_sem)")->execute([
                    'id' => $this->generateUuidV4(), 'siswa_id' => $idSiswa, 'tenant_id' => $tenantId,
                    'tb' => $tb, 'bb' => $bb, 'lk' => $lk, 'goldar' => $goldar, 'penyakit' => $penyakit, 'alergi' => $alergi, 'disab' => $disab, 'detail_sem' => $detailSem
                ]);
            }
        }

        // 3. Sub-table: siswa.orang_tua (Ayah, Ibu, Wali)
        $parentTypes = [
            'Ayah' => [
                'nama' => 'nama_ayah', 'nik' => 'nik_ayah', 'pekerjaan' => 'pekerjaan_ayah', 
                'pendidikan' => 'pendidikan_ayah', 'penghasilan' => 'penghasilan_ayah', 
                'tahun_lahir' => 'tahun_lahir_ayah', 'tanggal_lahir' => 'tanggal_lahir_ayah', 
                'agama' => 'agama_ayah', 'kewarganegaraan' => 'kewarganegaraan_ayah', 
                'status_hidup' => 'status_hidup_ayah', 'tempat_lahir' => 'tempat_lahir_ayah', 
                'id_tempat_lahir' => 'id_tempat_lahir_ayah'
            ],
            'Ibu'  => [
                'nama' => 'nama_ibu', 'nik' => 'nik_ibu', 'pekerjaan' => 'pekerjaan_ibu', 
                'pendidikan' => 'pendidikan_ibu', 'penghasilan' => 'penghasilan_ibu', 
                'tahun_lahir' => 'tahun_lahir_ibu', 'tanggal_lahir' => 'tanggal_lahir_ibu', 
                'agama' => 'agama_ibu', 'kewarganegaraan' => 'kewarganegaraan_ibu', 
                'status_hidup' => 'status_hidup_ibu', 'tempat_lahir' => 'tempat_lahir_ibu', 
                'id_tempat_lahir' => 'id_tempat_lahir_ibu'
            ],
            'Wali' => [
                'nama' => 'nama_wali', 'nik' => 'nik_wali', 'pekerjaan' => 'pekerjaan_wali', 
                'pendidikan' => 'pendidikan_wali', 'penghasilan' => 'penghasilan_wali', 
                'tahun_lahir' => 'tahun_lahir_wali', 'tanggal_lahir' => 'tanggal_lahir_wali', 
                'agama' => 'agama_wali', 'kewarganegaraan' => 'kewarganegaraan_wali', 
                'hubungan_wali' => 'hubungan_wali', 'tempat_lahir' => 'tempat_lahir_wali', 
                'id_tempat_lahir' => 'id_tempat_lahir_wali'
            ],
        ];

        foreach ($parentTypes as $hub => $pKeys) {
            $namaVal = $data[$pKeys['nama']] ?? null;
            $nikVal  = $data[$pKeys['nik']] ?? null;
            if ($namaVal !== null || $nikVal !== null || $isCreate) {
                $sqlO = "SELECT id FROM siswa.orang_tua WHERE siswa_id::text = :sid AND hubungan = :hub";
                $paramsO = ['sid' => $idSiswa, 'hub' => $hub];
                if (!empty($tenantId)) {
                    $sqlO .= " AND tenant_id = :tenant_id";
                    $paramsO['tenant_id'] = $tenantId;
                }
                $sqlO .= " LIMIT 1";
                $stO = $db->prepare($sqlO);
                $stO->execute($paramsO);
                $oId = $stO->fetchColumn();

                $pekerjaanVal  = $data[$pKeys['pekerjaan']] ?? null;
                $pendidikanVal = $data[$pKeys['pendidikan']] ?? null;
                $thnLahirVal   = !empty($data[$pKeys['tahun_lahir']]) ? (int)$data[$pKeys['tahun_lahir']] : null;
                $tglLahirVal   = $data[$pKeys['tanggal_lahir']] ?? null;
                $agamaVal      = $data[$pKeys['agama']] ?? null;
                $kwgVal        = $data[$pKeys['kewarganegaraan']] ?? 'WNI';
                $statusHidupVal= isset($pKeys['status_hidup']) ? ($data[$pKeys['status_hidup']] ?? 'Hidup') : null;
                $hubWaliVal    = isset($pKeys['hubungan_wali']) ? ($data[$pKeys['hubungan_wali']] ?? null) : null;
                $tempatLahirVal= $data[$pKeys['tempat_lahir']] ?? null;
                $idTmptLahirVal= !empty($data[$pKeys['id_tempat_lahir']]) ? (int)$data[$pKeys['id_tempat_lahir']] : null;
                
                $penghasilanRaw = $data[$pKeys['penghasilan']] ?? null;
                $penghasilanVal = null;
                if ($penghasilanRaw !== null && $penghasilanRaw !== '') {
                    if (is_numeric($penghasilanRaw)) {
                        $penghasilanVal = (int) $penghasilanRaw;
                    } else {
                        $cleanDigits = preg_replace('/[^\d]/', '', (string)$penghasilanRaw);
                        if ($cleanDigits !== '' && is_numeric($cleanDigits) && (int)$cleanDigits >= 500000) {
                            $penghasilanVal = (int) $cleanDigits;
                        } else {
                            $lower = strtolower((string)$penghasilanRaw);
                            if (strpos($lower, '< 1') !== false || strpos($lower, 'kurang') !== false) {
                                $penghasilanVal = 500000;
                            } elseif (strpos($lower, '1 - 2') !== false || strpos($lower, '1 juta') !== false || strpos($lower, '1.000.000') !== false) {
                                $penghasilanVal = 1500000;
                            } elseif (strpos($lower, '2 - 5') !== false || strpos($lower, '2 juta') !== false || strpos($lower, '2.000.000') !== false) {
                                $penghasilanVal = 3500000;
                            } elseif (strpos($lower, '5 - 20') !== false || strpos($lower, '5 juta') !== false || strpos($lower, '5.000.000') !== false) {
                                $penghasilanVal = 10000000;
                            } elseif (strpos($lower, '> 20') !== false || strpos($lower, 'lebih') !== false || strpos($lower, '20.000.000') !== false) {
                                $penghasilanVal = 25000000;
                            } else {
                                $penghasilanVal = 0;
                            }
                        }
                    }
                }
                $noHpVal       = $data['no_telepon_orang_tua'] ?? null;

                if ($oId) {
                    $sqlUpdO = "UPDATE siswa.orang_tua SET 
                        nama_lengkap = COALESCE(:nama, nama_lengkap), 
                        nik = COALESCE(:nik, nik), 
                        pekerjaan = COALESCE(:pekerjaan, pekerjaan), 
                        pendidikan = COALESCE(:pendidikan, pendidikan), 
                        penghasilan = COALESCE(:penghasilan, penghasilan), 
                        no_hp = COALESCE(:no_hp, no_hp), 
                        tahun_lahir = COALESCE(:thn_lahir, tahun_lahir),
                        tanggal_lahir = COALESCE(:tgl_lahir, tanggal_lahir),
                        agama = COALESCE(:agama, agama),
                        kewarganegaraan = COALESCE(:kwg, kewarganegaraan),
                        status_hidup = COALESCE(:status_hidup, status_hidup),
                        hubungan_wali = COALESCE(:hub_wali, hubungan_wali),
                        tempat_lahir = COALESCE(:tempat_lahir, tempat_lahir),
                        id_tempat_lahir = COALESCE(:id_tempat_lahir, id_tempat_lahir),
                        updated_at = CURRENT_TIMESTAMP 
                        WHERE id = :id";
                    $updParamsO = [
                        'nama' => $namaVal, 'nik' => $nikVal, 'pekerjaan' => $pekerjaanVal, 'pendidikan' => $pendidikanVal,
                        'penghasilan' => $penghasilanVal, 'no_hp' => $noHpVal, 'thn_lahir' => $thnLahirVal,
                        'tgl_lahir' => $tglLahirVal, 'agama' => $agamaVal, 'kwg' => $kwgVal,
                        'status_hidup' => $statusHidupVal, 'hub_wali' => $hubWaliVal, 
                        'tempat_lahir' => $tempatLahirVal, 'id_tempat_lahir' => $idTmptLahirVal,
                        'id' => $oId
                    ];
                    if (!empty($tenantId)) {
                        $sqlUpdO .= " AND tenant_id = :tenant_id";
                        $updParamsO['tenant_id'] = $tenantId;
                    }
                    $db->prepare($sqlUpdO)->execute($updParamsO);
                } else if ($namaVal || $nikVal) {
                    $db->prepare("INSERT INTO siswa.orang_tua (
                        id, siswa_id, tenant_id, hubungan, nama_lengkap, nik, pekerjaan, pendidikan, penghasilan, no_hp,
                        tahun_lahir, tanggal_lahir, agama, kewarganegaraan, status_hidup, hubungan_wali,
                        tempat_lahir, id_tempat_lahir
                    ) VALUES (
                        :id, :siswa_id, :tenant_id, :hub, :nama, :nik, :pekerjaan, :pendidikan, :penghasilan, :no_hp,
                        :thn_lahir, :tgl_lahir, :agama, :kwg, :status_hidup, :hub_wali,
                        :tempat_lahir, :id_tempat_lahir
                    )")->execute([
                        'id' => $this->generateUuidV4(), 'siswa_id' => $idSiswa, 'tenant_id' => $tenantId, 'hub' => $hub,
                        'nama' => $namaVal ?: 'Tidak Diisi', 'nik' => $nikVal, 'pekerjaan' => $pekerjaanVal, 'pendidikan' => $pendidikanVal,
                        'penghasilan' => $penghasilanVal, 'no_hp' => $noHpVal, 'thn_lahir' => $thnLahirVal,
                        'tgl_lahir' => $tglLahirVal, 'agama' => $agamaVal, 'kwg' => $kwgVal,
                        'status_hidup' => $statusHidupVal, 'hub_wali' => $hubWaliVal,
                        'tempat_lahir' => $tempatLahirVal, 'id_tempat_lahir' => $idTmptLahirVal
                    ]);
                }
            }
        }

        // 4. Sub-table: siswa.registrasi
        $regFields = [
            'jenis_pendaftaran', 'asal_sekolah', 'sekolah_asal', 'npsn_asal', 'tahun_daftar', 'no_pendaftaran', 'status_ppdb', 'catatan', 'hobi',
            'jalur_diterima', 'tanggal_masuk', 'paud_formal', 'paud_non_formal',
            'no_ijazah_sebelumnya', 'tanggal_ijazah_sebelumnya', 'lama_belajar_sebelumnya',
            'keluar_karena', 'tanggal_keluar', 'alasan_keluar', 'sekolah_tujuan', 'nomor_skp',
            'tingkat_ditinggalkan', 'diterima_di_tingkat', 'nomor_ijazah_kelulusan', 'nomor_skl', 'keterangan_setelah_lulus',
            'sekolah_asal_mutasi', 'pindah_dari_tingkat', 'pindah_no_surat'
        ];
        $hasReg = false;
        foreach ($regFields as $rf) {
            if (array_key_exists($rf, $data)) {
                $hasReg = true;
                break;
            }
        }
        if ($hasReg || $isCreate) {
            $sqlR = "SELECT id FROM siswa.registrasi WHERE siswa_id::text = :sid";
            $paramsR = ['sid' => $idSiswa];
            if (!empty($tenantId)) {
                $sqlR .= " AND tenant_id = :tenant_id";
                $paramsR['tenant_id'] = $tenantId;
            }
            $sqlR .= " LIMIT 1";
            $stR = $db->prepare($sqlR);
            $stR->execute($paramsR);
            $rId = $stR->fetchColumn();

            $jnsReg    = $data['jenis_pendaftaran'] ?? 'Siswa Baru';
            $asalSek   = $data['asal_sekolah'] ?? ($data['sekolah_asal'] ?? ($data['sekolah_asal_mutasi'] ?? null));
            $npsnAsal  = $data['npsn_asal'] ?? null;
            $thnDaftar = !empty($data['tahun_daftar']) ? (int)$data['tahun_daftar'] : (int)date('Y');
            $noReg     = $data['no_pendaftaran'] ?? null;
            $statusPpdb= $data['status_ppdb'] ?? 'Diterima';
            $catatan   = $data['catatan'] ?? ($data['hobi'] ?? null);

            $jalur     = $data['jalur_diterima'] ?? null;
            $tglMasuk  = $data['tanggal_masuk'] ?? null;
            $hobi      = $data['hobi'] ?? null;
            $paudF     = !empty($data['paud_formal']) ? 'true' : 'false';
            $paudNf    = !empty($data['paud_non_formal']) ? 'true' : 'false';
            $noIjz     = $data['no_ijazah_sebelumnya'] ?? null;
            $tglIjz    = $data['tanggal_ijazah_sebelumnya'] ?? null;
            $lamaBljr  = !empty($data['lama_belajar_sebelumnya']) ? (int)$data['lama_belajar_sebelumnya'] : null;

            $keluarKrna= $data['keluar_karena'] ?? null;
            $tglKeluar = $data['tanggal_keluar'] ?? null;
            $alasanKlr = $data['alasan_keluar'] ?? null;
            $sekTujuan = $data['sekolah_tujuan'] ?? null;
            $noSkp     = $data['nomor_skp'] ?? null;
            $tingDiting= $data['tingkat_ditinggalkan'] ?? null;
            $terimaTing= $data['diterima_di_tingkat'] ?? null;
            $noIjzLls  = $data['nomor_ijazah_kelulusan'] ?? null;
            $noSkl     = $data['nomor_skl'] ?? null;
            $ketLls    = $data['keterangan_setelah_lulus'] ?? null;
            $sekAsalMut= $data['sekolah_asal_mutasi'] ?? null;
            $pindTing  = $data['pindah_dari_tingkat'] ?? null;
            $pindSurat = $data['pindah_no_surat'] ?? null;

            if ($rId) {
                $sqlUpdR = "UPDATE siswa.registrasi SET 
                    jenis_pendaftaran = COALESCE(:jns, jenis_pendaftaran), 
                    asal_sekolah = COALESCE(:asal, asal_sekolah), 
                    npsn_asal = COALESCE(:npsn, npsn_asal), 
                    tahun_daftar = COALESCE(:thn, tahun_daftar), 
                    no_pendaftaran = COALESCE(:noreg, no_pendaftaran), 
                    status_ppdb = COALESCE(:status, status_ppdb), 
                    catatan = COALESCE(:catatan, catatan),
                    jalur_diterima = COALESCE(:jalur, jalur_diterima),
                    tanggal_masuk = COALESCE(:tgl_masuk, tanggal_masuk),
                    hobi = COALESCE(:hobi, hobi),
                    paud_formal = :paud_f,
                    paud_non_formal = :paud_nf,
                    no_ijazah_sebelumnya = COALESCE(:no_ijz, no_ijazah_sebelumnya),
                    tanggal_ijazah_sebelumnya = COALESCE(:tgl_ijz, tanggal_ijazah_sebelumnya),
                    lama_belajar_sebelumnya = COALESCE(:lama_bljr, lama_belajar_sebelumnya),
                    keluar_karena = COALESCE(:keluar_krna, keluar_karena),
                    tanggal_keluar = COALESCE(:tgl_keluar, tanggal_keluar),
                    alasan_keluar = COALESCE(:alasan_klr, alasan_keluar),
                    sekolah_tujuan = COALESCE(:sek_tujuan, sekolah_tujuan),
                    nomor_skp = COALESCE(:no_skp, nomor_skp),
                    tingkat_ditinggalkan = COALESCE(:ting_diting, tingkat_ditinggalkan),
                    diterima_di_tingkat = COALESCE(:terima_ting, diterima_di_tingkat),
                    nomor_ijazah_kelulusan = COALESCE(:no_ijz_lls, nomor_ijazah_kelulusan),
                    nomor_skl = COALESCE(:no_skl, nomor_skl),
                    keterangan_setelah_lulus = COALESCE(:ket_lls, keterangan_setelah_lulus),
                    sekolah_asal_mutasi = COALESCE(:sek_asal_mut, sekolah_asal_mutasi),
                    pindah_dari_tingkat = COALESCE(:pind_ting, pindah_dari_tingkat),
                    pindah_no_surat = COALESCE(:pind_surat, pindah_no_surat),
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE id = :id";
                $updParamsR = [
                    'jns' => $jnsReg, 'asal' => $asalSek, 'npsn' => $npsnAsal, 'thn' => $thnDaftar, 'noreg' => $noReg,
                    'status' => $statusPpdb, 'catatan' => $catatan,
                    'jalur' => $jalur, 'tgl_masuk' => $tglMasuk, 'hobi' => $hobi, 'paud_f' => $paudF, 'paud_nf' => $paudNf,
                    'no_ijz' => $noIjz, 'tgl_ijz' => $tglIjz, 'lama_bljr' => $lamaBljr,
                    'keluar_krna' => $keluarKrna, 'tgl_keluar' => $tglKeluar, 'alasan_klr' => $alasanKlr,
                    'sek_tujuan' => $sekTujuan, 'no_skp' => $noSkp, 'ting_diting' => $tingDiting,
                    'terima_ting' => $terimaTing, 'no_ijz_lls' => $noIjzLls, 'no_skl' => $noSkl,
                    'ket_lls' => $ketLls, 'sek_asal_mut' => $sekAsalMut, 'pind_ting' => $pindTing,
                    'pind_surat' => $pindSurat, 'id' => $rId
                ];
                if (!empty($tenantId)) {
                    $sqlUpdR .= " AND tenant_id = :tenant_id";
                    $updParamsR['tenant_id'] = $tenantId;
                }
                $db->prepare($sqlUpdR)->execute($updParamsR);
            } else if ($jnsReg || $asalSek) {
                $db->prepare("INSERT INTO siswa.registrasi (
                    id, siswa_id, tenant_id, jenis_pendaftaran, asal_sekolah, npsn_asal, tahun_daftar, no_pendaftaran, status_ppdb, catatan,
                    jalur_diterima, tanggal_masuk, hobi, paud_formal, paud_non_formal,
                    no_ijazah_sebelumnya, tanggal_ijazah_sebelumnya, lama_belajar_sebelumnya,
                    keluar_karena, tanggal_keluar, alasan_keluar, sekolah_tujuan, nomor_skp,
                    tingkat_ditinggalkan, diterima_di_tingkat, nomor_ijazah_kelulusan, nomor_skl, keterangan_setelah_lulus,
                    sekolah_asal_mutasi, pindah_dari_tingkat, pindah_no_surat
                ) VALUES (
                    :id, :siswa_id, :tenant_id, :jns, :asal, :npsn, :thn, :noreg, :status, :catatan,
                    :jalur, :tgl_masuk, :hobi, :paud_f, :paud_nf,
                    :no_ijz, :tgl_ijz, :lama_bljr,
                    :keluar_krna, :tgl_keluar, :alasan_klr, :sek_tujuan, :no_skp,
                    :ting_diting, :terima_ting, :no_ijz_lls, :no_skl, :ket_lls,
                    :sek_asal_mut, :pind_ting, :pind_surat
                )")->execute([
                    'id' => $this->generateUuidV4(), 'siswa_id' => $idSiswa, 'tenant_id' => $tenantId,
                    'jns' => $jnsReg, 'asal' => $asalSek, 'npsn' => $npsnAsal, 'thn' => $thnDaftar, 'noreg' => $noReg,
                    'status' => $statusPpdb, 'catatan' => $catatan,
                    'jalur' => $jalur, 'tgl_masuk' => $tglMasuk, 'hobi' => $hobi, 'paud_f' => $paudF, 'paud_nf' => $paudNf,
                    'no_ijz' => $noIjz, 'tgl_ijz' => $tglIjz, 'lama_bljr' => $lamaBljr,
                    'keluar_krna' => $keluarKrna, 'tgl_keluar' => $tglKeluar, 'alasan_klr' => $alasanKlr,
                    'sek_tujuan' => $sekTujuan, 'no_skp' => $noSkp, 'ting_diting' => $tingDiting,
                    'terima_ting' => $terimaTing, 'no_ijz_lls' => $noIjzLls, 'no_skl' => $noSkl,
                    'ket_lls' => $ketLls, 'sek_asal_mut' => $sekAsalMut, 'pind_ting' => $pindTing,
                    'pind_surat' => $pindSurat
                ]);
            }
        }

        // 5. Sub-table: siswa.dokumen
        $docKeys = ['berkas_kk', 'berkas_akta', 'berkas_ijazah_sd', 'berkas_ijazah_smp', 'berkas_ijazah_sma', 'berkas_mutasi_masuk', 'berkas_mutasi_keluar', 'berkas_kip', 'berkas_pernyataan_baru', 'berkas_pernyataan_tka'];
        foreach ($docKeys as $dKey) {
            if (array_key_exists($dKey, $data) && !empty($data[$dKey])) {
                $fileUrl = $data[$dKey];
                $fileName = basename($fileUrl);

                $sqlD = "SELECT id FROM siswa.dokumen WHERE siswa_id::text = :sid AND jenis_dokumen = :jenis";
                $paramsD = ['sid' => $idSiswa, 'jenis' => $dKey];
                if (!empty($tenantId)) {
                    $sqlD .= " AND tenant_id = :tenant_id";
                    $paramsD['tenant_id'] = $tenantId;
                }
                $sqlD .= " LIMIT 1";
                $stD = $db->prepare($sqlD);
                $stD->execute($paramsD);
                $dId = $stD->fetchColumn();

                if ($dId) {
                    $sqlUpdD = "UPDATE siswa.dokumen SET url_file = :url, nama_file = :nama, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                    $updParamsD = ['url' => $fileUrl, 'nama' => $fileName, 'id' => $dId];
                    if (!empty($tenantId)) {
                        $sqlUpdD .= " AND tenant_id = :tenant_id";
                        $updParamsD['tenant_id'] = $tenantId;
                    }
                    $db->prepare($sqlUpdD)->execute($updParamsD);
                } else {
                    $db->prepare("INSERT INTO siswa.dokumen (id, siswa_id, tenant_id, jenis_dokumen, nama_file, url_file) VALUES (:id, :siswa_id, :tenant_id, :jenis, :nama, :url)")->execute([
                        'id' => $this->generateUuidV4(), 'siswa_id' => $idSiswa, 'tenant_id' => $tenantId,
                        'jenis' => $dKey, 'nama' => $fileName, 'url' => $fileUrl
                    ]);
                }
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

    /**
     * Normalisasi string pendidikan agar sesuai opsi dropdown di UI
     */
    public static function normalizePendidikan(?string $pendidikan): string {
        if (!$pendidikan) return '';
        $p = trim($pendidikan);
        if (stripos($p, 'tidak tamat') !== false) return 'Tidak Tamat SD';
        if (preg_match('/\b(S3|Doktor)\b/i', $p)) return 'S3';
        if (preg_match('/\b(S2|Magister)\b/i', $p)) return 'S2';
        if (preg_match('/\b(S1|Sarjana)\b/i', $p)) return 'S1';
        if (preg_match('/\b(D4|Diploma 4)\b/i', $p)) return 'D4';
        if (preg_match('/\b(D3|Diploma 3)\b/i', $p)) return 'D3';
        if (preg_match('/\b(D2|Diploma 2)\b/i', $p)) return 'D2';
        if (preg_match('/\b(D1|Diploma 1)\b/i', $p)) return 'D1';
        if (preg_match('/\b(SMA|SMK|MA|Sederajat)\b/i', $p)) return 'SMA/Sederajat';
        if (preg_match('/\b(SMP|MTs)\b/i', $p)) return 'SMP/Sederajat';
        if (preg_match('/\b(SD|MI)\b/i', $p)) return 'SD/Sederajat';
        return $p;
    }

    /**
     * Konversi nilai integer / string penghasilan ke label rentang dropdown di UI
     */
    public static function formatPenghasilanToRange($penghasilan): string {
        if ($penghasilan === null || $penghasilan === '') return '';
        if (!is_numeric($penghasilan)) {
            return (string)$penghasilan;
        }
        $num = (int)$penghasilan;
        if ($num <= 0) return 'Tidak Berpenghasilan';
        if ($num < 500000) return 'Kurang dari Rp500.000';
        if ($num <= 999999) return 'Rp500.000 sampai Rp999.999';
        if ($num <= 1999999) return 'Rp1.000.000 sampai Rp1.999.999';
        if ($num <= 4999999) return 'Rp2.000.000 sampai Rp4.999.999';
        if ($num <= 20000000) return 'Rp5.000.000 sampai Rp20.000.000';
        return 'Lebih dari Rp20.000.000';
    }
}
