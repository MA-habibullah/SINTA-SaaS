<?php

namespace App\Modules\Kesiswaan\Models;

use App\Config\Database;
use PDO;

class EkskulModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /* ═══════════════════════════════════════════════════════════════════════
       1. MASTER EKSKUL
       ═══════════════════════════════════════════════════════════════════════ */

    public function getAllEkskul(string $tenantId, bool $activeOnly = false, ?string $tahunAjaranId = null, ?string $semester = null): array {
        $countCondition = "";
        $params = [':tenant_id' => $tenantId];

        if (!empty($tahunAjaranId)) {
            $countCondition .= " AND (ae.tahun_ajaran_id = :ta_id OR ae.tahun_ajaran_id IS NULL)";
            $params[':ta_id'] = $tahunAjaranId;
        }
        if (!empty($semester)) {
            $countCondition .= " AND ae.semester = :semester";
            $params[':semester'] = $semester;
        }

        $sql = "
            SELECT 
                me.id,
                me.tenant_id,
                COALESCE(me.nama_ekskul, me.nama_master_ekskul, '') AS nama_ekskul,
                COALESCE(me.kategori, 'Pilihan') AS kategori,
                me.deskripsi,
                me.pembina_id,
                COALESCE(me.nama_pembina, dp.nama_pembina, '') AS nama_pembina,
                me.hari_latihan,
                me.jam_mulai,
                me.jam_selesai,
                me.tempat_latihan,
                COALESCE(me.kuota_maksimal, 0) AS kuota_maksimal,
                me.is_active,
                me.created_at,
                (
                    SELECT COUNT(ae.id) 
                    FROM kesiswaan.anggota_ekskul ae 
                    WHERE ae.ekskul_id = me.id 
                      AND ae.tenant_id = me.tenant_id
                      AND ae.status_keanggotaan = 'Aktif'
                      {$countCondition}
                ) AS total_anggota
            FROM kesiswaan.master_ekskul me
            LEFT JOIN kesiswaan.data_pembina dp ON me.pembina_id = dp.id AND dp.tenant_id = me.tenant_id
            WHERE me.tenant_id = :tenant_id 
              AND me.deleted_at IS NULL
        ";

        if ($activeOnly) {
            $sql .= " AND me.is_active = true";
        }

        $sql .= " ORDER BY me.kategori ASC, COALESCE(me.nama_ekskul, me.nama_master_ekskul) ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getEkskulById(string $tenantId, string $id): ?array {
        $stmt = $this->db->prepare("
            SELECT me.*, COALESCE(me.nama_ekskul, me.nama_master_ekskul, '') AS nama_ekskul
            FROM kesiswaan.master_ekskul me
            WHERE me.tenant_id = :tenant_id AND me.id = :id AND me.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function saveEkskul(string $tenantId, array $data): string {
        if (!empty($data['id'])) {
            // Update
            $stmt = $this->db->prepare("
                UPDATE kesiswaan.master_ekskul
                SET nama_ekskul = :nama_ekskul,
                    nama_master_ekskul = :nama_ekskul,
                    kategori = :kategori,
                    deskripsi = :deskripsi,
                    pembina_id = :pembina_id,
                    nama_pembina = :nama_pembina,
                    hari_latihan = :hari_latihan,
                    jam_mulai = :jam_mulai,
                    jam_selesai = :jam_selesai,
                    tempat_latihan = :tempat_latihan,
                    kuota_maksimal = :kuota_maksimal,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->bindValue(':id', $data['id']);
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':nama_ekskul', $data['nama_ekskul']);
            $stmt->bindValue(':kategori', $data['kategori'] ?? 'Pilihan');
            $stmt->bindValue(':deskripsi', $data['deskripsi'] ?? null);
            $stmt->bindValue(':pembina_id', !empty($data['pembina_id']) ? $data['pembina_id'] : null);
            $stmt->bindValue(':nama_pembina', $data['nama_pembina'] ?? null);
            $stmt->bindValue(':hari_latihan', $data['hari_latihan'] ?? null);
            $stmt->bindValue(':jam_mulai', $data['jam_mulai'] ?? null);
            $stmt->bindValue(':jam_selesai', $data['jam_selesai'] ?? null);
            $stmt->bindValue(':tempat_latihan', $data['tempat_latihan'] ?? null);
            $stmt->bindValue(':kuota_maksimal', (int)($data['kuota_maksimal'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':is_active', isset($data['is_active']) ? (bool)$data['is_active'] : true, PDO::PARAM_BOOL);
            $stmt->execute();
            return $data['id'];
        } else {
            // Insert
            $stmt = $this->db->prepare("
                INSERT INTO kesiswaan.master_ekskul (
                    tenant_id, nama_ekskul, nama_master_ekskul, kategori, deskripsi, pembina_id, nama_pembina,
                    hari_latihan, jam_mulai, jam_selesai, tempat_latihan, kuota_maksimal, is_active
                ) VALUES (
                    :tenant_id, :nama_ekskul, :nama_ekskul, :kategori, :deskripsi, :pembina_id, :nama_pembina,
                    :hari_latihan, :jam_mulai, :jam_selesai, :tempat_latihan, :kuota_maksimal, :is_active
                ) RETURNING id
            ");
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':nama_ekskul', $data['nama_ekskul']);
            $stmt->bindValue(':kategori', $data['kategori'] ?? 'Pilihan');
            $stmt->bindValue(':deskripsi', $data['deskripsi'] ?? null);
            $stmt->bindValue(':pembina_id', !empty($data['pembina_id']) ? $data['pembina_id'] : null);
            $stmt->bindValue(':nama_pembina', $data['nama_pembina'] ?? null);
            $stmt->bindValue(':hari_latihan', $data['hari_latihan'] ?? null);
            $stmt->bindValue(':jam_mulai', $data['jam_mulai'] ?? null);
            $stmt->bindValue(':jam_selesai', $data['jam_selesai'] ?? null);
            $stmt->bindValue(':tempat_latihan', $data['tempat_latihan'] ?? null);
            $stmt->bindValue(':kuota_maksimal', (int)($data['kuota_maksimal'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':is_active', isset($data['is_active']) ? (bool)$data['is_active'] : true, PDO::PARAM_BOOL);
            $stmt->execute();
            return (string)$stmt->fetchColumn();
        }
    }

    public function deleteEkskul(string $tenantId, string $id): bool {
        $stmt = $this->db->prepare("
            UPDATE kesiswaan.master_ekskul 
            SET deleted_at = CURRENT_TIMESTAMP, is_active = false
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $tenantId);
        return $stmt->execute();
    }

    public function toggleStatusEkskul(string $tenantId, string $id, ?bool $newStatus = null): bool {
        if ($newStatus === null) {
            $stmt = $this->db->prepare("
                UPDATE kesiswaan.master_ekskul 
                SET is_active = NOT is_active, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':tenant_id', $tenantId);
            return $stmt->execute();
        } else {
            $stmt = $this->db->prepare("
                UPDATE kesiswaan.master_ekskul 
                SET is_active = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':status', $newStatus, PDO::PARAM_BOOL);
            return $stmt->execute();
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       2. DATA PEMBINA EKSKUL
       ═══════════════════════════════════════════════════════════════════════ */

    public function getAllPembina(string $tenantId): array {
        $stmt = $this->db->prepare("
            SELECT 
                dp.id,
                dp.tenant_id,
                COALESCE(dp.nama_pembina, dp.nama_data_pembina, '') AS nama_pembina,
                dp.guru_id,
                dp.nip,
                dp.jenis_kelamin,
                dp.no_hp,
                dp.email,
                COALESCE(dp.kategori_pembina, 'Guru Internal') AS kategori_pembina,
                dp.is_active,
                dp.created_at,
                (
                    SELECT COUNT(me.id) 
                    FROM kesiswaan.master_ekskul me 
                    WHERE me.pembina_id = dp.id 
                      AND me.tenant_id = dp.tenant_id 
                      AND me.deleted_at IS NULL
                ) AS total_bimbingan
            FROM kesiswaan.data_pembina dp
            WHERE dp.tenant_id = :tenant_id
            ORDER BY COALESCE(dp.nama_pembina, dp.nama_data_pembina) ASC
        ");
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function savePembina(string $tenantId, array $data): string {
        if (!empty($data['id'])) {
            $stmt = $this->db->prepare("
                UPDATE kesiswaan.data_pembina
                SET nama_pembina = :nama_pembina,
                    nama_data_pembina = :nama_pembina,
                    guru_id = :guru_id,
                    nip = :nip,
                    jenis_kelamin = :jenis_kelamin,
                    no_hp = :no_hp,
                    email = :email,
                    kategori_pembina = :kategori_pembina,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->bindValue(':id', $data['id']);
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':nama_pembina', $data['nama_pembina']);
            $stmt->bindValue(':guru_id', !empty($data['guru_id']) ? $data['guru_id'] : null);
            $stmt->bindValue(':nip', $data['nip'] ?? null);
            $stmt->bindValue(':jenis_kelamin', $data['jenis_kelamin'] ?? null);
            $stmt->bindValue(':no_hp', $data['no_hp'] ?? null);
            $stmt->bindValue(':email', $data['email'] ?? null);
            $stmt->bindValue(':kategori_pembina', $data['kategori_pembina'] ?? 'Guru Internal');
            $stmt->bindValue(':is_active', isset($data['is_active']) ? (bool)$data['is_active'] : true, PDO::PARAM_BOOL);
            $stmt->execute();
            return $data['id'];
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO kesiswaan.data_pembina (
                    tenant_id, nama_pembina, nama_data_pembina, guru_id, nip, jenis_kelamin, no_hp, email, kategori_pembina, is_active
                ) VALUES (
                    :tenant_id, :nama_pembina, :nama_pembina, :guru_id, :nip, :jenis_kelamin, :no_hp, :email, :kategori_pembina, :is_active
                ) RETURNING id
            ");
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':nama_pembina', $data['nama_pembina']);
            $stmt->bindValue(':guru_id', !empty($data['guru_id']) ? $data['guru_id'] : null);
            $stmt->bindValue(':nip', $data['nip'] ?? null);
            $stmt->bindValue(':jenis_kelamin', $data['jenis_kelamin'] ?? null);
            $stmt->bindValue(':no_hp', $data['no_hp'] ?? null);
            $stmt->bindValue(':email', $data['email'] ?? null);
            $stmt->bindValue(':kategori_pembina', $data['kategori_pembina'] ?? 'Guru Internal');
            $stmt->bindValue(':is_active', isset($data['is_active']) ? (bool)$data['is_active'] : true, PDO::PARAM_BOOL);
            $stmt->execute();
            return (string)$stmt->fetchColumn();
        }
    }

    public function deletePembina(string $tenantId, string $id): bool {
        // Set pembina_id in master_ekskul to null
        $stmtClear = $this->db->prepare("
            UPDATE kesiswaan.master_ekskul 
            SET pembina_id = NULL 
            WHERE pembina_id = :id AND tenant_id = :tenant_id
        ");
        $stmtClear->bindValue(':id', $id);
        $stmtClear->bindValue(':tenant_id', $tenantId);
        $stmtClear->execute();

        $stmt = $this->db->prepare("DELETE FROM kesiswaan.data_pembina WHERE id = :id AND tenant_id = :tenant_id");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $tenantId);
        return $stmt->execute();
    }

    public function toggleStatusPembina(string $tenantId, string $id, ?bool $newStatus = null): bool {
        if ($newStatus === null) {
            $stmt = $this->db->prepare("
                UPDATE kesiswaan.data_pembina 
                SET is_active = NOT is_active, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':tenant_id', $tenantId);
            return $stmt->execute();
        } else {
            $stmt = $this->db->prepare("
                UPDATE kesiswaan.data_pembina 
                SET is_active = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':status', $newStatus, PDO::PARAM_BOOL);
            return $stmt->execute();
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       3. ANGGOTA EKSKUL
       ═══════════════════════════════════════════════════════════════════════ */

    public function getAnggotaEkskul(string $tenantId, string $ekskulId, ?string $tahunAjaranId = null, ?string $semester = null): array {
        $sql = "
            SELECT 
                ae.id,
                ae.tenant_id,
                ae.ekskul_id,
                ae.siswa_id,
                ae.tahun_ajaran_id,
                ae.semester,
                COALESCE(ae.jabatan, 'Anggota') AS jabatan,
                ae.nomor_anggota,
                ae.tanggal_bergabung,
                ae.status_keanggotaan,
                ae.catatan,
                s.nama_lengkap,
                s.nisn,
                s.nis,
                COALESCE(k.nama_kelas, s.kelas_saat_ini, '—') AS nama_kelas,
                COALESCE(s.jenis_kelamin, 'L') AS jenis_kelamin
            FROM kesiswaan.anggota_ekskul ae
            JOIN siswa.siswa s ON ae.siswa_id = s.id AND s.tenant_id = ae.tenant_id
            LEFT JOIN akademik.kelas k ON (s.kelas_saat_ini = k.nama_kelas OR s.kelas_saat_ini = k.id::varchar) AND k.tenant_id = ae.tenant_id
            WHERE ae.tenant_id = :tenant_id 
              AND ae.ekskul_id = :ekskul_id
        ";

        $params = [
            ':tenant_id' => $tenantId,
            ':ekskul_id' => $ekskulId
        ];

        if (!empty($tahunAjaranId)) {
            $sql .= " AND (ae.tahun_ajaran_id = :ta_id OR ae.tahun_ajaran_id IS NULL)";
            $params[':ta_id'] = $tahunAjaranId;
        }

        if (!empty($semester)) {
            $sql .= " AND ae.semester = :semester";
            $params[':semester'] = $semester;
        }

        $sql .= " ORDER BY 
            CASE 
                WHEN ae.jabatan = 'Ketua' THEN 1 
                WHEN ae.jabatan = 'Wakil Ketua' THEN 2 
                WHEN ae.jabatan = 'Sekretaris' THEN 3 
                WHEN ae.jabatan = 'Bendahara' THEN 4 
                ELSE 5 
            END ASC, 
            s.nama_lengkap ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function addAnggotaBulk(string $tenantId, string $ekskulId, array $siswaIds, string $tahunAjaranId, string $semester, string $jabatan = 'Anggota'): int {
        $added = 0;
        $stmtCheck = $this->db->prepare("
            SELECT id FROM kesiswaan.anggota_ekskul
            WHERE tenant_id = :tenant_id 
              AND ekskul_id = :ekskul_id 
              AND siswa_id = :siswa_id 
              AND tahun_ajaran_id = :ta_id 
              AND semester = :semester
            LIMIT 1
        ");

        $stmtInsert = $this->db->prepare("
            INSERT INTO kesiswaan.anggota_ekskul (
                tenant_id, ekskul_id, siswa_id, tahun_ajaran_id, semester, jabatan, status_keanggotaan, tanggal_bergabung
            ) VALUES (
                :tenant_id, :ekskul_id, :siswa_id, :ta_id, :semester, :jabatan, 'Aktif', CURRENT_DATE
            )
        ");

        foreach ($siswaIds as $sid) {
            if (empty($sid)) continue;
            $stmtCheck->execute([
                ':tenant_id' => $tenantId,
                ':ekskul_id' => $ekskulId,
                ':siswa_id' => $sid,
                ':ta_id' => $tahunAjaranId,
                ':semester' => $semester
            ]);
            if (!$stmtCheck->fetchColumn()) {
                $stmtInsert->execute([
                    ':tenant_id' => $tenantId,
                    ':ekskul_id' => $ekskulId,
                    ':siswa_id' => $sid,
                    ':ta_id' => $tahunAjaranId,
                    ':semester' => $semester,
                    ':jabatan' => $jabatan
                ]);
                $added++;
            }
        }

        return $added;
    }

    public function removeAnggota(string $tenantId, string $id): bool {
        $stmt = $this->db->prepare("DELETE FROM kesiswaan.anggota_ekskul WHERE id = :id AND tenant_id = :tenant_id");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $tenantId);
        return $stmt->execute();
    }

    public function updateJabatanAnggota(string $tenantId, string $id, string $jabatan): bool {
        $stmt = $this->db->prepare("
            UPDATE kesiswaan.anggota_ekskul 
            SET jabatan = :jabatan, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':jabatan', $jabatan);
        return $stmt->execute();
    }

    /* ═══════════════════════════════════════════════════════════════════════
       4. JURNAL & KEGIATAN EKSKUL
       ═══════════════════════════════════════════════════════════════════════ */

    public function getJurnalEkskul(string $tenantId, string $ekskulId, ?string $tahunAjaranId = null, ?string $semester = null): array {
        $sql = "
            SELECT 
                je.id,
                je.tenant_id,
                je.ekskul_id,
                je.pembina_id,
                je.tahun_ajaran_id,
                je.semester,
                je.tanggal_kegiatan,
                je.jam_mulai,
                je.jam_selesai,
                je.materi_kegiatan,
                je.lokasi,
                COALESCE(je.jumlah_hadir, 0) AS jumlah_hadir,
                COALESCE(je.jumlah_absen, 0) AS jumlah_absen,
                je.foto_kegiatan,
                je.catatan_evaluasi,
                je.created_at,
                COALESCE(dp.nama_pembina, me.nama_pembina, '') AS nama_pembina
            FROM kesiswaan.jurnal_ekskul je
            JOIN kesiswaan.master_ekskul me ON je.ekskul_id = me.id AND me.tenant_id = je.tenant_id
            LEFT JOIN kesiswaan.data_pembina dp ON je.pembina_id = dp.id AND dp.tenant_id = je.tenant_id
            WHERE je.tenant_id = :tenant_id 
              AND je.ekskul_id = :ekskul_id
        ";

        $params = [
            ':tenant_id' => $tenantId,
            ':ekskul_id' => $ekskulId
        ];

        if (!empty($tahunAjaranId)) {
            $sql .= " AND (je.tahun_ajaran_id = :ta_id OR je.tahun_ajaran_id IS NULL)";
            $params[':ta_id'] = $tahunAjaranId;
        }

        if (!empty($semester)) {
            $sql .= " AND je.semester = :semester";
            $params[':semester'] = $semester;
        }

        $sql .= " ORDER BY je.tanggal_kegiatan DESC, je.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveJurnal(string $tenantId, array $data): string {
        if (!empty($data['id'])) {
            $stmt = $this->db->prepare("
                UPDATE kesiswaan.jurnal_ekskul
                SET tanggal_kegiatan = :tanggal_kegiatan,
                    jam_mulai = :jam_mulai,
                    jam_selesai = :jam_selesai,
                    materi_kegiatan = :materi_kegiatan,
                    lokasi = :lokasi,
                    jumlah_hadir = :jumlah_hadir,
                    jumlah_absen = :jumlah_absen,
                    foto_kegiatan = :foto_kegiatan,
                    catatan_evaluasi = :catatan_evaluasi,
                    pembina_id = :pembina_id,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->bindValue(':id', $data['id']);
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':tanggal_kegiatan', $data['tanggal_kegiatan']);
            $stmt->bindValue(':jam_mulai', $data['jam_mulai'] ?? null);
            $stmt->bindValue(':jam_selesai', $data['jam_selesai'] ?? null);
            $stmt->bindValue(':materi_kegiatan', $data['materi_kegiatan']);
            $stmt->bindValue(':lokasi', $data['lokasi'] ?? null);
            $stmt->bindValue(':jumlah_hadir', (int)($data['jumlah_hadir'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':jumlah_absen', (int)($data['jumlah_absen'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':foto_kegiatan', $data['foto_kegiatan'] ?? null);
            $stmt->bindValue(':catatan_evaluasi', $data['catatan_evaluasi'] ?? null);
            $stmt->bindValue(':pembina_id', !empty($data['pembina_id']) ? $data['pembina_id'] : null);
            $stmt->execute();
            return $data['id'];
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO kesiswaan.jurnal_ekskul (
                    tenant_id, ekskul_id, pembina_id, tahun_ajaran_id, semester,
                    tanggal_kegiatan, jam_mulai, jam_selesai, materi_kegiatan, lokasi,
                    jumlah_hadir, jumlah_absen, foto_kegiatan, catatan_evaluasi
                ) VALUES (
                    :tenant_id, :ekskul_id, :pembina_id, :ta_id, :semester,
                    :tanggal_kegiatan, :jam_mulai, :jam_selesai, :materi_kegiatan, :lokasi,
                    :jumlah_hadir, :jumlah_absen, :foto_kegiatan, :catatan_evaluasi
                ) RETURNING id
            ");
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':ekskul_id', $data['ekskul_id']);
            $stmt->bindValue(':pembina_id', !empty($data['pembina_id']) ? $data['pembina_id'] : null);
            $stmt->bindValue(':ta_id', $data['tahun_ajaran_id']);
            $stmt->bindValue(':semester', $data['semester'] ?? 'Ganjil');
            $stmt->bindValue(':tanggal_kegiatan', $data['tanggal_kegiatan']);
            $stmt->bindValue(':jam_mulai', $data['jam_mulai'] ?? null);
            $stmt->bindValue(':jam_selesai', $data['jam_selesai'] ?? null);
            $stmt->bindValue(':materi_kegiatan', $data['materi_kegiatan']);
            $stmt->bindValue(':lokasi', $data['lokasi'] ?? null);
            $stmt->bindValue(':jumlah_hadir', (int)($data['jumlah_hadir'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':jumlah_absen', (int)($data['jumlah_absen'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':foto_kegiatan', $data['foto_kegiatan'] ?? null);
            $stmt->bindValue(':catatan_evaluasi', $data['catatan_evaluasi'] ?? null);
            $stmt->execute();
            return (string)$stmt->fetchColumn();
        }
    }

    public function deleteJurnal(string $tenantId, string $id): bool {
        $stmt = $this->db->prepare("DELETE FROM kesiswaan.jurnal_ekskul WHERE id = :id AND tenant_id = :tenant_id");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':tenant_id', $tenantId);
        return $stmt->execute();
    }

    /* ═══════════════════════════════════════════════════════════════════════
       5. PENILAIAN EKSKUL (RAPOR)
       ═══════════════════════════════════════════════════════════════════════ */

    public function getNilaiEkskul(string $tenantId, string $ekskulId, string $tahunAjaranId, string $semester): array {
        // Ambil seluruh anggota ekskul dan gabungkan dengan nilai yang sudah ada
        $sql = "
            SELECT 
                ae.id AS anggota_id,
                ae.siswa_id,
                s.nama_lengkap,
                s.nisn,
                s.nis,
                COALESCE(k.nama_kelas, s.kelas_saat_ini, '—') AS nama_kelas,
                COALESCE(ne.id::varchar, '') AS nilai_id,
                COALESCE(ne.predikat, 'A') AS predikat,
                ne.nilai_angka,
                COALESCE(ne.keterangan, '') AS keterangan,
                COALESCE(ne.is_locked, false) AS is_locked
            FROM kesiswaan.anggota_ekskul ae
            JOIN siswa.siswa s ON ae.siswa_id = s.id AND s.tenant_id = ae.tenant_id
            LEFT JOIN akademik.kelas k ON (s.kelas_saat_ini = k.nama_kelas OR s.kelas_saat_ini = k.id::varchar) AND k.tenant_id = ae.tenant_id
            LEFT JOIN kesiswaan.nilai_ekskul ne ON (
                ne.ekskul_id = ae.ekskul_id 
                AND ne.siswa_id = ae.siswa_id 
                AND ne.tahun_ajaran_id = ae.tahun_ajaran_id 
                AND ne.semester = ae.semester 
                AND ne.tenant_id = ae.tenant_id
            )
            WHERE ae.tenant_id = :tenant_id
              AND ae.ekskul_id = :ekskul_id
              AND (ae.tahun_ajaran_id = :ta_id OR ae.tahun_ajaran_id IS NULL)
              AND ae.semester = :semester
              AND ae.status_keanggotaan = 'Aktif'
            ORDER BY s.nama_lengkap ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':ekskul_id' => $ekskulId,
            ':ta_id' => $tahunAjaranId,
            ':semester' => $semester
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveNilaiBatch(string $tenantId, string $ekskulId, string $tahunAjaranId, string $semester, array $grades): int {
        $saved = 0;
        $stmtUpsert = $this->db->prepare("
            INSERT INTO kesiswaan.nilai_ekskul (
                tenant_id, ekskul_id, siswa_id, tahun_ajaran_id, semester, predikat, nilai_angka, keterangan, updated_at
            ) VALUES (
                :tenant_id, :ekskul_id, :siswa_id, :ta_id, :semester, :predikat, :nilai_angka, :keterangan, CURRENT_TIMESTAMP
            )
            ON CONFLICT (id) DO UPDATE SET
                predikat = EXCLUDED.predikat,
                nilai_angka = EXCLUDED.nilai_angka,
                keterangan = EXCLUDED.keterangan,
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmtCheck = $this->db->prepare("
            SELECT id FROM kesiswaan.nilai_ekskul
            WHERE tenant_id = :tenant_id 
              AND ekskul_id = :ekskul_id 
              AND siswa_id = :siswa_id 
              AND tahun_ajaran_id = :ta_id 
              AND semester = :semester
            LIMIT 1
        ");

        $stmtUpdate = $this->db->prepare("
            UPDATE kesiswaan.nilai_ekskul
            SET predikat = :predikat,
                nilai_angka = :nilai_angka,
                keterangan = :keterangan,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND tenant_id = :tenant_id
        ");

        $stmtInsert = $this->db->prepare("
            INSERT INTO kesiswaan.nilai_ekskul (
                tenant_id, ekskul_id, siswa_id, tahun_ajaran_id, semester, predikat, nilai_angka, keterangan
            ) VALUES (
                :tenant_id, :ekskul_id, :siswa_id, :ta_id, :semester, :predikat, :nilai_angka, :keterangan
            )
        ");

        foreach ($grades as $g) {
            $siswaId = $g['siswa_id'] ?? '';
            if (empty($siswaId)) continue;

            $predikat = $g['predikat'] ?? 'A';
            $nilaiAngka = isset($g['nilai_angka']) && $g['nilai_angka'] !== '' ? (float)$g['nilai_angka'] : null;
            $keterangan = $g['keterangan'] ?? '';

            $stmtCheck->execute([
                ':tenant_id' => $tenantId,
                ':ekskul_id' => $ekskulId,
                ':siswa_id' => $siswaId,
                ':ta_id' => $tahunAjaranId,
                ':semester' => $semester
            ]);
            $existingId = $stmtCheck->fetchColumn();

            if ($existingId) {
                $stmtUpdate->execute([
                    ':id' => $existingId,
                    ':tenant_id' => $tenantId,
                    ':predikat' => $predikat,
                    ':nilai_angka' => $nilaiAngka,
                    ':keterangan' => $keterangan
                ]);
            } else {
                $stmtInsert->execute([
                    ':tenant_id' => $tenantId,
                    ':ekskul_id' => $ekskulId,
                    ':siswa_id' => $siswaId,
                    ':ta_id' => $tahunAjaranId,
                    ':semester' => $semester,
                    ':predikat' => $predikat,
                    ':nilai_angka' => $nilaiAngka,
                    ':keterangan' => $keterangan
                ]);
            }
            $saved++;
        }

        return $saved;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       6. STATUS LOCK PENGUNCIAN EKSKUL
       ═══════════════════════════════════════════════════════════════════════ */

    public function getLockStatus(string $tenantId, string $ekskulId, string $tahunAjaranId, string $semester): array {
        $stmt = $this->db->prepare("
            SELECT lock_anggota, lock_nilai, locked_by, locked_at
            FROM kesiswaan.kunci_ekskul
            WHERE tenant_id = :tenant_id 
              AND ekskul_id = :ekskul_id 
              AND tahun_ajaran_id = :ta_id 
              AND semester = :semester
            LIMIT 1
        ");
        $stmt->execute([
            ':tenant_id' => $tenantId,
            ':ekskul_id' => $ekskulId,
            ':ta_id' => $tahunAjaranId,
            ':semester' => $semester
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'lock_anggota' => (bool)$row['lock_anggota'],
                'lock_nilai' => (bool)$row['lock_nilai'],
                'locked_by' => $row['locked_by'],
                'locked_at' => $row['locked_at']
            ];
        }

        return [
            'lock_anggota' => false,
            'lock_nilai' => false,
            'locked_by' => null,
            'locked_at' => null
        ];
    }

    public function toggleLock(string $tenantId, string $ekskulId, string $tahunAjaranId, string $semester, string $type, string $userName): array {
        $current = $this->getLockStatus($tenantId, $ekskulId, $tahunAjaranId, $semester);
        
        $newLockAnggota = $current['lock_anggota'];
        $newLockNilai = $current['lock_nilai'];

        if ($type === 'anggota') {
            $newLockAnggota = !$current['lock_anggota'];
        } elseif ($type === 'nilai') {
            $newLockNilai = !$current['lock_nilai'];
        }

        $stmtCheck = $this->db->prepare("
            SELECT id FROM kesiswaan.kunci_ekskul
            WHERE tenant_id = :tenant_id AND ekskul_id = :ekskul_id AND tahun_ajaran_id = :ta_id AND semester = :semester
            LIMIT 1
        ");
        $stmtCheck->execute([
            ':tenant_id' => $tenantId,
            ':ekskul_id' => $ekskulId,
            ':ta_id' => $tahunAjaranId,
            ':semester' => $semester
        ]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            $stmt = $this->db->prepare("
                UPDATE kesiswaan.kunci_ekskul
                SET lock_anggota = :lock_anggota,
                    lock_nilai = :lock_nilai,
                    locked_by = :locked_by,
                    locked_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->bindValue(':id', $existingId);
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':lock_anggota', $newLockAnggota, PDO::PARAM_BOOL);
            $stmt->bindValue(':lock_nilai', $newLockNilai, PDO::PARAM_BOOL);
            $stmt->bindValue(':locked_by', $userName);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO kesiswaan.kunci_ekskul (
                    tenant_id, ekskul_id, tahun_ajaran_id, semester, lock_anggota, lock_nilai, locked_by, locked_at
                ) VALUES (
                    :tenant_id, :ekskul_id, :ta_id, :semester, :lock_anggota, :lock_nilai, :locked_by, CURRENT_TIMESTAMP
                )
            ");
            $stmt->bindValue(':tenant_id', $tenantId);
            $stmt->bindValue(':ekskul_id', $ekskulId);
            $stmt->bindValue(':ta_id', $tahunAjaranId);
            $stmt->bindValue(':semester', $semester);
            $stmt->bindValue(':lock_anggota', $newLockAnggota, PDO::PARAM_BOOL);
            $stmt->bindValue(':lock_nilai', $newLockNilai, PDO::PARAM_BOOL);
            $stmt->bindValue(':locked_by', $userName);
            $stmt->execute();
        }

        return [
            'lock_anggota' => $newLockAnggota,
            'lock_nilai' => $newLockNilai,
            'locked_by' => $userName,
            'locked_at' => date('Y-m-d H:i:s')
        ];
    }

    /* ═══════════════════════════════════════════════════════════════════════
       7. STATS SUMMARY & LOOKUPS
       ═══════════════════════════════════════════════════════════════════════ */

    public function getSummaryStats(string $tenantId): array {
        $totalEkskul = (int)$this->db->query("SELECT COUNT(*) FROM kesiswaan.master_ekskul WHERE tenant_id = '{$tenantId}' AND deleted_at IS NULL AND is_active = true")->fetchColumn();
        $totalPembina = (int)$this->db->query("SELECT COUNT(*) FROM kesiswaan.data_pembina WHERE tenant_id = '{$tenantId}' AND is_active = true")->fetchColumn();
        $totalAnggota = (int)$this->db->query("SELECT COUNT(DISTINCT siswa_id) FROM kesiswaan.anggota_ekskul WHERE tenant_id = '{$tenantId}' AND status_keanggotaan = 'Aktif'")->fetchColumn();
        $totalJurnal = (int)$this->db->query("SELECT COUNT(*) FROM kesiswaan.jurnal_ekskul WHERE tenant_id = '{$tenantId}'")->fetchColumn();

        return [
            'total_ekskul' => $totalEkskul,
            'total_pembina' => $totalPembina,
            'total_anggota' => $totalAnggota,
            'total_jurnal' => $totalJurnal
        ];
    }
}
