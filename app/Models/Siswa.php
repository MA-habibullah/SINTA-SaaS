<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Siswa extends Model {

    /**
     * Ambil semua data siswa aktif di tenant/sekolah yang sedang login
     */
    public function findAll(): array {
        $sql = "SELECT * FROM siswa WHERE tenant_id = :tenant_id AND deleted_at IS NULL ORDER BY nama_lengkap ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tenant_id' => $this->tenantId]);
        return $stmt->fetchAll();
    }

    public function findById(string $id): ?array {
        if ($this->tenantId === null) {
            $sql = "SELECT * FROM siswa WHERE id = :id AND deleted_at IS NULL LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
        } else {
            $sql = "SELECT * FROM siswa WHERE id = :id AND tenant_id = :tenant_id AND deleted_at IS NULL LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'tenant_id' => $this->tenantId
            ]);
        }
        return $stmt->fetch() ?: null;
    }

    /**
     * Ambil data lengkap siswa termasuk semua sub-tabel & wilayah (untuk edit form)
     */
    public function findFullById(string $id): ?array {
        $siswa = $this->findById($id);
        if (!$siswa) {
            return null;
        }

        $idSiswa = $siswa['id'];

        // Fetch sub-tables
        $subTables = ['rincian_pelajar', 'rincian_alamat', 'kontak', 'orang_tua', 'kip', 'registrasi', 'dokumen'];
        $fullData = $siswa;

        foreach ($subTables as $table) {
            $stmt = $this->db->prepare("SELECT * FROM `$table` WHERE id_siswa = ? LIMIT 1");
            $stmt->execute([$idSiswa]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            
            // Merge but exclude the sub-table's own auto-increment ID to prevent collisions
            $pkName = 'id_' . $table;
            unset($row[$pkName]);
            
            $fullData = array_merge($fullData, $row);
        }

        // Fetch region hierarchy if kelurahan is set
        if (!empty($fullData['id_kelurahan'])) {
            $stmt = $this->db->prepare("
                SELECT kl.id_kelurahan, kl.id_kecamatan, kc.id_kota, kt.id_provinsi
                FROM kelurahan kl
                JOIN kecamatan kc ON kl.id_kecamatan = kc.id_kecamatan
                JOIN kota kt ON kc.id_kota = kt.id_kota
                WHERE kl.id_kelurahan = ?
                LIMIT 1
            ");
            $stmt->execute([$fullData['id_kelurahan']]);
            $wilayah = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            $fullData = array_merge($fullData, $wilayah);
        }

        // Fetch birth city name if tempat_lahir is numeric (ID)
        if (!empty($fullData['tempat_lahir']) && is_numeric($fullData['tempat_lahir'])) {
            try {
                $stmtKota = $this->db->prepare("SELECT nama_kota FROM kota WHERE id_kota = ? LIMIT 1");
                $stmtKota->execute([$fullData['tempat_lahir']]);
                $cityName = $stmtKota->fetchColumn();
                if ($cityName) {
                    $fullData['tempat_lahir_id'] = $fullData['tempat_lahir']; // Keep original ID
                    $fullData['tempat_lahir'] = $cityName;
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return $fullData;
    }

    /**
     * Tambah data siswa baru dengan UUID
     */
    /**
     * Tambah data siswa baru dengan UUID dan data sub-tabel terkait
     */
    public function create(array $data): string {
        $id = $data['id'] ?? $this->generateUuidV4();
        
        $isOuterTransaction = !$this->db->inTransaction();
        try {
            if ($isOuterTransaction) {
                $this->db->beginTransaction();
            }

            $sql = "INSERT INTO siswa (
                        id, tenant_id, user_id, nisn, nis, nama_lengkap, 
                        tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, nama_wali, kontak_wali,
                        id_angkatan, id_tahun_ajaran, id_pendidikan, id_jenjang, id_jurusan, id_kelas,
                        nik, no_kk, nama_panggilan, agama, ukuran_seragam_sekolah, ukuran_seragam_olahraga,
                        sekolah_asal, status, password,
                        kewarganegaraan, bahasa_sehari_hari, no_ijazah_sebelumnya, tanggal_ijazah_sebelumnya,
                        lama_belajar_sebelumnya, nomor_ijazah_kelulusan, nomor_skl, keterangan_setelah_lulus
                    ) VALUES (
                        :id, :tenant_id, :user_id, :nisn, :nis, :nama_lengkap, 
                        :tempat_lahir, :tanggal_lahir, :jenis_kelamin, :alamat, :nama_wali, :kontak_wali,
                        :id_angkatan, :id_tahun_ajaran, :id_pendidikan, :id_jenjang, :id_jurusan, :id_kelas,
                        :nik, :no_kk, :nama_panggilan, :agama, :ukuran_seragam_sekolah, :ukuran_seragam_olahraga,
                        :sekolah_asal, :status, :password,
                        :kewarganegaraan, :bahasa_sehari_hari, :no_ijazah_sebelumnya, :tanggal_ijazah_sebelumnya,
                        :lama_belajar_sebelumnya, :nomor_ijazah_kelulusan, :nomor_skl, :keterangan_setelah_lulus
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'tenant_id' => $this->tenantId ?: ($data['tenant_id'] ?? null),
                'user_id' => $data['user_id'] ?? null,
                'nisn' => !empty($data['nisn']) ? $data['nisn'] : null,
                'nis' => !empty($data['nis']) ? $data['nis'] : null,
                'nama_lengkap' => $data['nama_lengkap'],
                'tempat_lahir' => !empty($data['tempat_lahir']) ? $data['tempat_lahir'] : null,
                'tanggal_lahir' => !empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null,
                'jenis_kelamin' => $data['jenis_kelamin'],
                'alamat' => !empty($data['alamat_kk']) ? $data['alamat_kk'] : ($data['alamat'] ?? null),
                'nama_wali' => !empty($data['nama_wali']) ? $data['nama_wali'] : null,
                'kontak_wali' => !empty($data['kontak_wali']) ? $data['kontak_wali'] : null,
                'id_angkatan' => !empty($data['id_angkatan']) ? $data['id_angkatan'] : null,
                'id_tahun_ajaran' => !empty($data['id_tahun_ajaran']) ? $data['id_tahun_ajaran'] : null,
                'id_pendidikan' => !empty($data['id_pendidikan']) ? $data['id_pendidikan'] : null,
                'id_jenjang' => !empty($data['id_jenjang']) ? $data['id_jenjang'] : null,
                'id_jurusan' => !empty($data['id_jurusan']) ? $data['id_jurusan'] : null,
                'id_kelas' => !empty($data['id_kelas']) ? $data['id_kelas'] : null,
                'nik' => !empty($data['nik']) ? $data['nik'] : null,
                'no_kk' => !empty($data['no_kk']) ? $data['no_kk'] : null,
                'nama_panggilan' => !empty($data['nama_panggilan']) ? $data['nama_panggilan'] : null,
                'agama' => !empty($data['agama']) ? $data['agama'] : null,
                'ukuran_seragam_sekolah' => !empty($data['ukuran_seragam_sekolah']) ? $data['ukuran_seragam_sekolah'] : null,
                'ukuran_seragam_olahraga' => !empty($data['ukuran_seragam_olahraga']) ? $data['ukuran_seragam_olahraga'] : null,
                'sekolah_asal' => !empty($data['sekolah_asal']) ? $data['sekolah_asal'] : null,
                'status' => !empty($data['status']) ? $data['status'] : 'Aktif',
                'password' => !empty($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : password_hash($data['tanggal_lahir'] ?? '123456', PASSWORD_BCRYPT),
                'kewarganegaraan' => $data['kewarganegaraan'] ?? 'WNI',
                'bahasa_sehari_hari' => $data['bahasa_sehari_hari'] ?? 'Indonesia',
                'no_ijazah_sebelumnya' => !empty($data['no_ijazah_sebelumnya']) ? $data['no_ijazah_sebelumnya'] : null,
                'tanggal_ijazah_sebelumnya' => !empty($data['tanggal_ijazah_sebelumnya']) ? $data['tanggal_ijazah_sebelumnya'] : null,
                'lama_belajar_sebelumnya' => !empty($data['lama_belajar_sebelumnya']) ? (int) $data['lama_belajar_sebelumnya'] : null,
                'nomor_ijazah_kelulusan' => !empty($data['nomor_ijazah_kelulusan']) ? $data['nomor_ijazah_kelulusan'] : null,
                'nomor_skl' => !empty($data['nomor_skl']) ? $data['nomor_skl'] : null,
                'keterangan_setelah_lulus' => !empty($data['keterangan_setelah_lulus']) ? $data['keterangan_setelah_lulus'] : null
            ]);

            // Simpan data sub-tabel terkait
            $this->saveOrUpdateSubTables($id, $data, true);

            if ($isOuterTransaction) {
                $this->db->commit();
            }
            return $id;
        } catch (\Throwable $e) {
            if ($isOuterTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update data siswa (Secure: wajib menyertakan tenant_id di klausa WHERE)
     */
    public function update(string $id, array $data): bool {
        $isOuterTransaction = !$this->db->inTransaction();
        try {
            if ($isOuterTransaction) {
                $this->db->beginTransaction();
            }

            $siswaCols = [
                'nisn', 'nis', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 
                'jenis_kelamin', 'nama_wali', 'kontak_wali', 'id_angkatan', 
                'id_tahun_ajaran', 'id_pendidikan', 'id_jenjang', 'id_jurusan', 'id_kelas',
                'nik', 'no_kk', 'nama_panggilan', 'agama', 'ukuran_seragam_sekolah', 'ukuran_seragam_olahraga',
                'sekolah_asal', 'status',
                'kewarganegaraan', 'bahasa_sehari_hari', 'no_ijazah_sebelumnya', 'tanggal_ijazah_sebelumnya',
                'lama_belajar_sebelumnya', 'nomor_ijazah_kelulusan', 'nomor_skl', 'keterangan_setelah_lulus'
            ];

            $setParts = [];
            $params = [
                'id' => $id,
                'tenant_id' => $this->tenantId ?: ($data['tenant_id'] ?? null)
            ];

            foreach ($siswaCols as $col) {
                if (array_key_exists($col, $data)) {
                    $setParts[] = "`$col` = :$col";
                    
                    $nullableCols = [
                        'nisn', 'nis', 'tempat_lahir', 'id_angkatan', 'id_tahun_ajaran', 
                        'id_pendidikan', 'id_jenjang', 'id_jurusan', 'id_kelas', 'nama_wali', 'kontak_wali',
                        'nik', 'no_kk', 'nama_panggilan', 'agama', 'ukuran_seragam_sekolah', 'ukuran_seragam_olahraga',
                        'sekolah_asal',
                        'kewarganegaraan', 'bahasa_sehari_hari', 'no_ijazah_sebelumnya', 'tanggal_ijazah_sebelumnya',
                        'lama_belajar_sebelumnya', 'nomor_ijazah_kelulusan', 'nomor_skl', 'keterangan_setelah_lulus'
                    ];
                    
                    if (in_array($col, $nullableCols) && $data[$col] === '') {
                        $params[$col] = null;
                    } else {
                        $params[$col] = $data[$col];
                    }
                }
            }

            // Handle khusus mapping alamat
            if (array_key_exists('alamat_kk', $data)) {
                $setParts[] = "`alamat` = :alamat";
                $params['alamat'] = $data['alamat_kk'] !== '' ? $data['alamat_kk'] : null;
            } elseif (array_key_exists('alamat', $data)) {
                $setParts[] = "`alamat` = :alamat";
                $params['alamat'] = $data['alamat'] !== '' ? $data['alamat'] : null;
            }

            // Handle khusus password jika diubah
            if (array_key_exists('password', $data) && !empty($data['password'])) {
                $setParts[] = "`password` = :password";
                $params['password'] = $data['password'];
                $setParts[] = "`is_first_login` = 0";
            }

            if (!empty($setParts)) {
                $sql = "UPDATE siswa SET " . implode(', ', $setParts) . " WHERE id = :id AND tenant_id = :tenant_id AND deleted_at IS NULL";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }

            // Perbarui data sub-tabel terkait secara dinamis
            $this->saveOrUpdateSubTables($id, $data, false);

            if ($isOuterTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($isOuterTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Soft Delete data siswa (Secure: wajib menyertakan tenant_id di klausa WHERE)
     */
    public function delete(string $id): bool {
        $sql = "UPDATE siswa SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'tenant_id' => $this->tenantId
        ]);
    }

    /**
     * Cek keunikan NISN secara nasional
     */
    public function isNisnUnique(string $nisn, ?string $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM siswa WHERE nisn = :nisn AND deleted_at IS NULL";
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = ['nisn' => $nisn];
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Cek keunikan NIS di lingkup sekolah (tenant) bersangkutan
     */
    public function isNisUnique(string $nis, ?string $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM siswa WHERE nis = :nis AND tenant_id = :tenant_id AND deleted_at IS NULL";
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [
            'nis' => $nis,
            'tenant_id' => $this->tenantId
        ];
        if ($excludeId !== null) {
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Save or update data in all student sub-tables.
     */
    private function saveOrUpdateSubTables(string $idSiswa, array $data, bool $isCreate): void {
        // List of sub-tables and their configuration
        $subTables = [
            'rincian_pelajar' => [
                'columns' => [
                    'lingkar_kepala' => (int) ($data['lingkar_kepala'] ?? 0),
                    'tinggi_badan' => (int) ($data['tinggi_badan'] ?? 0),
                    'berat_badan' => (int) ($data['berat_badan'] ?? 0),
                    'golongan_darah' => $data['golongan_darah'] ?? 'A',
                    'anak_ke' => (int) ($data['anak_ke'] ?? 1),
                    'jarak_rumah' => (int) ($data['jarak_rumah'] ?? 0),
                    'transportasi' => $data['transportasi'] ?? 'Lainnya',
                    'jumlah_saudara' => (int) ($data['jumlah_saudara'] ?? 0),
                    'penyakit_yang_diderita' => !empty($data['penyakit_yang_diderita']) ? $data['penyakit_yang_diderita'] : null,
                    'foto_profil' => !empty($data['foto_profil']) ? $data['foto_profil'] : null,
                    'kelainan_jasmani' => !empty($data['kelainan_jasmani']) ? $data['kelainan_jasmani'] : 'Tidak Ada'
                ]
            ],
            'rincian_alamat' => [
                'columns' => [
                    'id_kelurahan' => (int) ($data['id_kelurahan'] ?? 0),
                    'alamat_kk' => $data['alamat_kk'] ?? '',
                    'alamat_domisili' => $data['alamat_domisili'] ?? '',
                    'rt' => $data['rt'] ?? '',
                    'rw' => $data['rw'] ?? '',
                    'kode_pos' => $data['kode_pos'] ?? '',
                    'status_tinggal' => $data['status_tinggal'] ?? 'Lainnya',
                    'tinggal_dengan' => $data['tinggal_dengan'] ?? 'Orang Tua'
                ]
            ],
            'orang_tua' => [
                'columns' => [
                    'id_tempat_lahir_ayah' => !empty($data['id_tempat_lahir_ayah']) ? (int) $data['id_tempat_lahir_ayah'] : null,
                    'nik_ayah' => !empty($data['nik_ayah']) ? $data['nik_ayah'] : null,
                    'nama_ayah' => !empty($data['nama_ayah']) ? $data['nama_ayah'] : null,
                    'tahun_lahir_ayah' => !empty($data['tahun_lahir_ayah']) ? (int) $data['tahun_lahir_ayah'] : null,
                    'pendidikan_ayah' => !empty($data['pendidikan_ayah']) ? $data['pendidikan_ayah'] : null,
                    'pekerjaan_ayah' => !empty($data['pekerjaan_ayah']) ? $data['pekerjaan_ayah'] : null,
                    'penghasilan_ayah' => !empty($data['penghasilan_ayah']) ? $data['penghasilan_ayah'] : null,
                    'agama_ayah' => !empty($data['agama_ayah']) ? $data['agama_ayah'] : null,
                    'id_tempat_lahir_ibu' => (int) ($data['id_tempat_lahir_ibu'] ?? 0),
                    'nik_ibu' => $data['nik_ibu'] ?? '',
                    'nama_ibu' => $data['nama_ibu'] ?? '',
                    'tahun_lahir_ibu' => (int) ($data['tahun_lahir_ibu'] ?? 0),
                    'pendidikan_ibu' => $data['pendidikan_ibu'] ?? 'SMP',
                    'pekerjaan_ibu' => $data['pekerjaan_ibu'] ?? 'Tidak Bekerja',
                    'penghasilan_ibu' => $data['penghasilan_ibu'] ?? 'Tidak Berpenghasilan',
                    'agama_ibu' => $data['agama_ibu'] ?? 'Islam',
                    'id_tempat_lahir_wali' => !empty($data['id_tempat_lahir_wali']) ? (int) $data['id_tempat_lahir_wali'] : null,
                    'nik_wali' => !empty($data['nik_wali']) ? $data['nik_wali'] : null,
                    'nama_wali' => !empty($data['nama_wali']) ? $data['nama_wali'] : null,
                    'tahun_lahir_wali' => !empty($data['tahun_lahir_wali']) ? (int) $data['tahun_lahir_wali'] : null,
                    'pendidikan_wali' => !empty($data['pendidikan_wali']) ? $data['pendidikan_wali'] : null,
                    'pekerjaan_wali' => !empty($data['pekerjaan_wali']) ? $data['pekerjaan_wali'] : null,
                    'penghasilan_wali' => !empty($data['penghasilan_wali']) ? $data['penghasilan_wali'] : null,
                    'agama_wali' => !empty($data['agama_wali']) ? $data['agama_wali'] : null,
                    'tanggal_lahir_ayah' => !empty($data['tanggal_lahir_ayah']) ? $data['tanggal_lahir_ayah'] : null,
                    'kewarganegaraan_ayah' => $data['kewarganegaraan_ayah'] ?? 'WNI',
                    'status_hidup_ayah' => $data['status_hidup_ayah'] ?? 'Hidup',
                    'tanggal_lahir_ibu' => !empty($data['tanggal_lahir_ibu']) ? $data['tanggal_lahir_ibu'] : null,
                    'kewarganegaraan_ibu' => $data['kewarganegaraan_ibu'] ?? 'WNI',
                    'status_hidup_ibu' => $data['status_hidup_ibu'] ?? 'Hidup',
                    'tanggal_lahir_wali' => !empty($data['tanggal_lahir_wali']) ? $data['tanggal_lahir_wali'] : null,
                    'kewarganegaraan_wali' => $data['kewarganegaraan_wali'] ?? null,
                    'hubungan_wali' => $data['hubungan_wali'] ?? null
                ]
            ],
            'kontak' => [
                'columns' => [
                    'email' => $data['email'] ?? '',
                    'no_telepon_rumah' => !empty($data['no_telepon_rumah']) ? $data['no_telepon_rumah'] : null,
                    'no_telepon_orang_tua' => !empty($data['no_telepon_orang_tua']) ? $data['no_telepon_orang_tua'] : null,
                    'no_telepon_siswa' => $data['no_telepon_siswa'] ?? ''
                ]
            ],
            'kip' => [
                'columns' => [
                    'penerima_kps' => isset($data['penerima_kps']) ? (int) $data['penerima_kps'] : 0,
                    'punya_kip' => isset($data['punya_kip']) ? (int) $data['punya_kip'] : 0,
                    'layak_kip' => isset($data['layak_kip']) ? (int) $data['layak_kip'] : 0,
                    'alasan_layak' => !empty($data['alasan_layak']) ? $data['alasan_layak'] : 'Tidak Ada',
                    'no_kip' => !empty($data['no_kip']) ? $data['no_kip'] : null,
                    'status_anak' => !empty($data['status_anak']) ? $data['status_anak'] : null
                ]
            ],
            'registrasi' => [
                'columns' => [
                    'jalur_diterima' => !empty($data['jalur_diterima']) ? $data['jalur_diterima'] : null,
                    'jenis_pendaftaran' => $data['jenis_pendaftaran'] ?? 'Siswa Baru',
                    'tanggal_masuk' => $data['tanggal_masuk'] ?? date('Y-m-d'),
                    'paud_formal' => isset($data['paud_formal']) ? (int) $data['paud_formal'] : 1,
                    'paud_non_formal' => isset($data['paud_non_formal']) ? (int) $data['paud_non_formal'] : 0,
                    'hobi' => $data['hobi'] ?? '',
                    'keluar_karena' => !empty($data['keluar_karena']) ? $data['keluar_karena'] : null,
                    'tanggal_keluar' => !empty($data['tanggal_keluar']) ? $data['tanggal_keluar'] : null,
                    'alasan_keluar' => !empty($data['alasan_keluar']) ? $data['alasan_keluar'] : null,
                    'sekolah_tujuan' => !empty($data['sekolah_tujuan']) ? $data['sekolah_tujuan'] : null,
                    'nomor_skp' => !empty($data['nomor_skp']) ? $data['nomor_skp'] : null
                ]
            ],
            'dokumen' => [
                'columns' => [
                    'berkas_kk' => !empty($data['berkas_kk']) ? $data['berkas_kk'] : null,
                    'berkas_akta' => !empty($data['berkas_akta']) ? $data['berkas_akta'] : null,
                    'berkas_ijazah_sd' => !empty($data['berkas_ijazah_sd']) ? $data['berkas_ijazah_sd'] : null,
                    'berkas_ijazah_smp' => !empty($data['berkas_ijazah_smp']) ? $data['berkas_ijazah_smp'] : null,
                    'berkas_ijazah_sma' => !empty($data['berkas_ijazah_sma']) ? $data['berkas_ijazah_sma'] : null,
                    'berkas_mutasi_masuk' => !empty($data['berkas_mutasi_masuk']) ? $data['berkas_mutasi_masuk'] : null,
                    'berkas_mutasi_keluar' => !empty($data['berkas_mutasi_keluar']) ? $data['berkas_mutasi_keluar'] : null,
                    'berkas_kip' => !empty($data['berkas_kip']) ? $data['berkas_kip'] : null,
                    'berkas_pernyataan_baru' => !empty($data['berkas_pernyataan_baru']) ? $data['berkas_pernyataan_baru'] : null,
                    'berkas_pernyataan_tka' => !empty($data['berkas_pernyataan_tka']) ? $data['berkas_pernyataan_tka'] : null,
                    'file_sizes' => !empty($data['file_sizes']) ? (is_array($data['file_sizes']) ? json_encode($data['file_sizes']) : $data['file_sizes']) : null
                ]
            ]
        ];

        foreach ($subTables as $table => $config) {
            $cols = $config['columns'];
            
            // Saring kolom yang benar-benar dikirimkan dalam payload $data
            $passedCols = [];
            foreach ($cols as $colName => $processedValue) {
                if (array_key_exists($colName, $data)) {
                    $passedCols[$colName] = $processedValue;
                }
            }
            
            // Skip update tabel jika bukan operasi create baru dan tidak ada kolom tabel ini yang dikirim
            if (!$isCreate && empty($passedCols)) {
                continue;
            }

            // Check if record exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `$table` WHERE id_siswa = ?");
            $stmt->execute([$idSiswa]);
            $exists = $stmt->fetchColumn() > 0;

            if ($exists) {
                if (empty($passedCols)) {
                    continue;
                }
                
                // UPDATE
                $setParts = [];
                $params = [];
                foreach ($passedCols as $colName => $value) {
                    $setParts[] = "`$colName` = :$colName";
                    $params[$colName] = $value;
                }
                $params['id_siswa'] = $idSiswa;
                
                $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE id_siswa = :id_siswa";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            } else {
                // Jika record belum ada dan kita sedang update, pastikan field wajib untuk tabel ini dikirim sebelum insert
                if (!$isCreate) {
                    $requiredCheckKeys = [
                        'rincian_pelajar' => ['tinggi_badan', 'berat_badan'],
                        'rincian_alamat' => ['alamat_kk', 'id_kelurahan'],
                        'orang_tua' => ['nama_ibu', 'nik_ibu'],
                        'kontak' => ['email', 'no_telepon_siswa'],
                        'kip' => ['punya_kip', 'layak_kip'],
                        'registrasi' => ['jenis_pendaftaran', 'hobi'],
                        'dokumen' => ['berkas_kk', 'berkas_akta', 'berkas_ijazah_sd', 'berkas_ijazah_smp', 'berkas_ijazah_sma']
                    ];
                    
                    if (isset($requiredCheckKeys[$table])) {
                        $hasRequired = false;
                        foreach ($requiredCheckKeys[$table] as $reqKey) {
                            if (array_key_exists($reqKey, $data) && $data[$reqKey] !== '') {
                                $hasRequired = true;
                                break;
                            }
                        }
                        if (!$hasRequired) {
                            continue; // Skip insert karena data wajib untuk sub-tabel ini belum ada/belum diisi
                        }
                    }
                }

                // INSERT
                $insertData = [];
                foreach ($cols as $colName => $processedValue) {
                    $insertData[$colName] = $processedValue;
                }
                
                $colNames = array_keys($insertData);
                $placeholders = array_map(fn($c) => ":$c", $colNames);
                
                $colNames[] = 'id_siswa';
                $placeholders[] = ':id_siswa';
                
                $params = $insertData;
                $params['id_siswa'] = $idSiswa;

                $sql = "INSERT INTO `$table` (" . implode(', ', array_map(fn($c) => "`$c`", $colNames)) . ") 
                        VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
        }
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
}
