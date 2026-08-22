<?php

namespace App\Modules\Akademik\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Config\Database;
use PDO;

class NilaiRaporModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        
        $roles   = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $allowed = array_intersect($roles, ['super_admin', 'operator_sekolah', 'guru']);
        if (empty($allowed)) {
            $this->jsonResponse(false, null, 'Anda tidak memiliki akses ke fitur ini.', 403);
        }
    }

    private function resolveTenantId(PDO $db, string $kelasId = ''): ?string {
        $tenantId = SessionManager::getTenantId();
        if (!$tenantId || $tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12') {
            $tenantId = null;
        }
        if (!$tenantId && !empty($_GET['tenant_id']))        $tenantId = $_GET['tenant_id'];
        if (!$tenantId && !empty($_GET['filter_tenant_id'])) $tenantId = $_GET['filter_tenant_id'];
        if (!$tenantId && !empty($_POST['tenant_id']))       $tenantId = $_POST['tenant_id'];
        if (!$tenantId && !empty($_POST['filter_tenant_id'])) $tenantId = $_POST['filter_tenant_id'];
        if (!$tenantId && !empty($kelasId)) {
            $st = $db->prepare("SELECT tenant_id::text FROM akademik.kelas WHERE id::text = :id LIMIT 1");
            $st->execute(['id' => $kelasId]);
            $tenantId = $st->fetchColumn() ?: null;
        }
        return $tenantId ?: null;
    }

    private function isReligionSubjectMismatch(?string $studentReligion, string $subjectName): bool {
        $lower = strtolower($subjectName);
        if (strpos($lower, 'agama') === false && strpos($lower, 'keagamaan') === false) return false;

        $religions = [
            'islam'    => ['islam'],
            'kristen'  => ['kristen', 'protestan'],
            'katolik'  => ['katolik'],
            'hindu'    => ['hindu'],
            'buddha'   => ['buddha', 'budha'],
            'konghucu' => ['khonghucu', 'konghucu'],
        ];

        $subKey = null;
        foreach ($religions as $key => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($lower, $kw) !== false) { $subKey = $key; break 2; }
            }
        }
        if ($subKey === null || empty($studentReligion)) return false;

        $stuLower = strtolower(trim($studentReligion));
        $stuKey   = null;
        foreach ($religions as $key => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($stuLower, $kw) !== false) { $stuKey = $key; break 2; }
            }
        }
        return $stuKey !== $subKey;
    }

    private function getCandidateClassNames(string $namaKelas): array {
        $clean = trim($namaKelas);
        if (empty($clean)) return [];

        $suffix = preg_replace('/^(XII|XI|X|IX|VIII|VII|12|11|10|9|8|7)\s*/i', '', $clean);
        $suffix = trim($suffix);

        $level = null;
        if (preg_match('/^XII\b/i', $clean) || preg_match('/^12\b/i', $clean)) $level = 12;
        elseif (preg_match('/^XI\b/i', $clean) || preg_match('/^11\b/i', $clean)) $level = 11;
        elseif (preg_match('/^X\b/i', $clean) || preg_match('/^10\b/i', $clean)) $level = 10;
        elseif (preg_match('/^IX\b/i', $clean) || preg_match('/^9\b/i', $clean)) $level = 9;
        elseif (preg_match('/^VIII\b/i', $clean) || preg_match('/^8\b/i', $clean)) $level = 8;
        elseif (preg_match('/^VII\b/i', $clean) || preg_match('/^7\b/i', $clean)) $level = 7;

        $candidates = [$clean];
        if (!empty($suffix) && $level !== null) {
            $prefixes = [];
            if ($level <= 10) { $prefixes = ['X', 'XI', 'XII', '10', '11', '12']; }
            elseif ($level <= 11) { $prefixes = ['XI', 'XII', '11', '12']; }
            elseif ($level <= 12) { $prefixes = ['XII', '12']; }
            
            foreach ($prefixes as $p) {
                $candidates[] = $p . ' ' . $suffix;
            }
        }
        return array_values(array_unique($candidates));
    }

    private function getStudentsInKelas(PDO $db, string $kelasId, string $tenantId, string $tahunAjaran, string $semester): array {
        $stK = $db->prepare("SELECT nama_kelas FROM akademik.kelas WHERE id::text = :id LIMIT 1");
        $stK->execute(['id' => $kelasId]);
        $namaKelas = $stK->fetchColumn() ?: '';

        $params = [
            'tenant_id'    => $tenantId,
            'kelas_id'     => $kelasId,
            'nama_kelas'   => $namaKelas,
            'tahun_ajaran' => $tahunAjaran,
            'semester'     => $semester,
        ];

        $st = $db->prepare(
            "SELECT DISTINCT s.id, s.nama_lengkap, s.nisn, s.nis, s.agama, s.kelas_saat_ini,
                    COALESCE(
                        (SELECT k.nama_kelas FROM siswa.anggota_kelas ak JOIN akademik.kelas k ON k.id::text = ak.kelas_id::text WHERE ak.siswa_id::text = s.id::text AND ak.tahun_ajaran = :tahun_ajaran LIMIT 1),
                        (SELECT r.dari_kelas FROM siswa.riwayat_kenaikan_kelas r WHERE r.siswa_id::text = s.id::text AND r.tahun_ajaran = :tahun_ajaran LIMIT 1),
                        (SELECT k.nama_kelas FROM akademik.detail_nilai_rapor d JOIN akademik.kelas k ON k.id::text = d.kelas_id::text WHERE d.siswa_id::text = s.id::text AND d.tahun_ajaran = :tahun_ajaran LIMIT 1),
                        :nama_kelas,
                        s.kelas_saat_ini
                    ) AS kelas_historis
             FROM   siswa.siswa s
             WHERE  (s.tenant_id::text = :tenant_id OR s.tenant_id IS NULL)
               AND  (s.is_active = true OR s.is_active IS NULL)
               AND  (
                     -- 1. Match penempatan di siswa.anggota_kelas untuk TA & kelas spesifik ini
                     s.id::text IN (
                         SELECT siswa_id::text
                         FROM   siswa.anggota_kelas
                         WHERE  (kelas_id::text = :kelas_id OR kelas_id::text IN (SELECT id::text FROM akademik.kelas WHERE nama_kelas = :nama_kelas))
                           AND  tahun_ajaran = :tahun_ajaran
                     )

                  -- 2. Match riwayat_kenaikan_kelas (dari_kelas) untuk TA & kelas spesifik ini
                  OR s.id::text IN (
                         SELECT siswa_id::text
                         FROM   siswa.riwayat_kenaikan_kelas
                         WHERE  (dari_kelas::text = :kelas_id OR dari_kelas = :nama_kelas)
                           AND  tahun_ajaran = :tahun_ajaran
                     )

                  -- 3. Match detail_nilai_rapor yang sudah diinput pada TA, semester & kelas spesifik ini
                  OR s.id::text IN (
                         SELECT siswa_id::text
                         FROM   akademik.detail_nilai_rapor
                         WHERE  (kelas_id::text = :kelas_id OR kelas_id::text IN (SELECT id::text FROM akademik.kelas WHERE nama_kelas = :nama_kelas))
                           AND  tahun_ajaran = :tahun_ajaran
                           AND  semester     = :semester
                           AND  is_active    = true
                     )
               )
             ORDER BY s.nama_lengkap ASC"
        );
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * GET /api/v1/nilai-rapor/grid
     */
    public function getGrid(): void {
        $db          = Database::getConnection();
        $kelasId     = $_GET['kelas_id']    ?? '';
        $tahunAjaran = $_GET['tahun_ajaran'] ?? '';
        $semester    = $_GET['semester']     ?? '';

        $tenantId = $this->resolveTenantId($db, $kelasId);
        if (!$tenantId) { $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi.', 400); return; }
        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(false, null, 'Parameter kelas_id, tahun_ajaran, dan semester wajib diisi.', 400); return;
        }

        $semList = ($semester === 'Ganjil' || $semester === '1') ? ['Ganjil', '1'] : ['Genap', '2'];

        $stMapel = $db->prepare(
            "SELECT DISTINCT p.mapel_id,
                    m.id::text AS kode_mapel,
                    COALESCE(m.nama_mata_pelajaran, m.id::text) AS nama_mapel,
                    COALESCE(p.kkm, 75) AS kkm,
                    COALESCE(p.jam_pelajaran, 2) AS jam_pelajaran,
                    p.guru_id,
                    u.nama_lengkap AS nama_guru
             FROM   akademik.pemetaan_mapel p
             JOIN   akademik.mata_pelajaran m ON (p.mapel_id = m.id::text OR p.mapel_id::text = m.id::text)
             LEFT JOIN core.users u ON (p.guru_id = u.id OR p.guru_id::text = u.id::text)
             WHERE  (p.kelas_id = :kelas_id OR p.kelas_id::text = :kelas_id)
               AND  p.tahun_ajaran = :tahun_ajaran
               AND  (p.semester = :sem1 OR p.semester = :sem2)
               AND  (p.tenant_id::text = :tenant_id OR p.tenant_id IS NULL)
               AND  (p.is_active = true OR p.is_active IS NULL)
               AND  (m.is_active = true OR m.is_active IS NULL)
             ORDER BY nama_mapel ASC"
        );
        $stMapel->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'sem1' => $semList[0], 'sem2' => $semList[1], 'tenant_id' => $tenantId]);
        $subjects = $stMapel->fetchAll(PDO::FETCH_ASSOC);

        $students = $this->getStudentsInKelas($db, $kelasId, $tenantId, $tahunAjaran, $semester);

        $stActive = $db->prepare(
            "SELECT r.nama_ref_kurikulum AS nama_kurikulum,
                    r.kategori           AS tipe_penilaian,
                    kk.is_active         AS is_locked
             FROM   akademik.kelas_kurikulum kk
             JOIN   akademik.ref_kurikulum r ON (kk.kurikulum_id = r.id::text OR kk.kurikulum_id::text = r.id::text)
             WHERE  (kk.kelas_id = :kelas_id OR kk.kelas_id::text = :kelas_id)
               AND  kk.tahun_ajaran = :tahun_ajaran
               AND  (kk.tenant_id::text = :tenant_id OR kk.tenant_id IS NULL)
             LIMIT 1"
        );
        $stActive->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'tenant_id' => $tenantId]);
        $activeKur = $stActive->fetch(PDO::FETCH_ASSOC) ?: [
            'nama_kurikulum' => 'Kurikulum Merdeka',
            'tipe_penilaian' => 'sederhana',
            'is_locked'      => false,
        ];

        $stKunci = $db->prepare("SELECT is_locked_nilai FROM akademik.kunci_akademik WHERE (tenant_id::text = :tid OR tenant_id IS NULL) AND tahun_ajaran = :ta AND (semester = :sem1 OR semester = :sem2) AND is_active = true LIMIT 1");
        $stKunci->execute(['tid' => $tenantId, 'ta' => $tahunAjaran, 'sem1' => $semList[0], 'sem2' => $semList[1]]);
        $isLocked = (bool)($stKunci->fetchColumn() ?: false);

        $stGrades = $db->prepare(
            "SELECT siswa_id, mapel_id, nilai_akhir, capaian_kompetensi, deskripsi_capaian, deskripsi_keterampilan, nilai_tp, nilai_sts, nilai_sas
             FROM   akademik.detail_nilai_rapor
             WHERE  (kelas_id = :kelas_id OR kelas_id::text = :kelas_id)
               AND  tahun_ajaran = :tahun_ajaran
               AND  (semester = :sem1 OR semester = :sem2)
               AND  (tenant_id::text = :tenant_id OR tenant_id IS NULL)
               AND  (is_active = true OR is_active IS NULL)"
        );
        $stGrades->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'sem1' => $semList[0], 'sem2' => $semList[1], 'tenant_id' => $tenantId]);
        $gradesList = $stGrades->fetchAll(PDO::FETCH_ASSOC);

        $gradesMatrix = [];
        foreach ($gradesList as $row) {
            $sId = $row['siswa_id'];
            $mId = $row['mapel_id'];
            if (!isset($gradesMatrix[$sId])) $gradesMatrix[$sId] = [];
            $gradesMatrix[$sId][$mId] = [
                'nilai_akhir'            => $row['nilai_akhir'] !== null ? (float)$row['nilai_akhir'] : null,
                'capaian_kompetensi'     => $row['capaian_kompetensi'] ?? '',
                'deskripsi_capaian'      => $row['deskripsi_capaian'] ?? '',
                'deskripsi_keterampilan' => $row['deskripsi_keterampilan'] ?? '',
                'nilai_tp'               => $row['nilai_tp'] !== null ? (float)$row['nilai_tp'] : null,
                'nilai_sts'              => $row['nilai_sts'] !== null ? (float)$row['nilai_sts'] : null,
                'nilai_sas'              => $row['nilai_sas'] !== null ? (float)$row['nilai_sas'] : null,
            ];
        }

        $this->jsonResponse(true, [
            'subjects'         => $subjects,
            'students'         => $students,
            'grades'           => $gradesMatrix,
            'kurikulum'        => $activeKur,
            'is_rombel_locked' => $isLocked,
        ]);
    }

    /**
     * POST /api/v1/nilai-rapor/save
     */
    public function save(): void {
        $input       = $this->getJsonInput();
        $kelasId     = $input['kelas_id']    ?? '';
        $tahunAjaran = $input['tahun_ajaran'] ?? '';
        $semester    = $input['semester']     ?? '';
        $grades      = $input['grades']       ?? [];

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(false, null, 'Parameter wajib tidak lengkap.', 400); return;
        }

        $db       = Database::getConnection();
        $tenantId = $this->resolveTenantId($db, $kelasId);
        if (!$tenantId) { $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi.', 400); return; }

        $semList = ($semester === 'Ganjil' || $semester === '1') ? ['Ganjil', '1'] : ['Genap', '2'];

        $stLock = $db->prepare("SELECT is_locked_nilai FROM akademik.kunci_akademik WHERE (tenant_id::text = ? OR tenant_id IS NULL) AND tahun_ajaran = ? AND (semester = ? OR semester = ?) AND is_active = true");
        $stLock->execute([$tenantId, $tahunAjaran, $semList[0], $semList[1]]);
        if ($stLock->fetchColumn()) {
            $this->jsonResponse(false, null, 'Input Nilai Rapor telah dikunci oleh administrator.', 403); return;
        }

        $stSiswaList = $db->prepare("SELECT id, agama FROM siswa.siswa WHERE (tenant_id::text = :tenant_id OR tenant_id IS NULL) AND (is_active = true OR is_active IS NULL)");
        $stSiswaList->execute(['tenant_id' => $tenantId]);
        $studentsMap = [];
        foreach ($stSiswaList->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $studentsMap[$s['id']] = $s['agama'] ?? null;
        }

        $stMapelNames = $db->prepare(
            "SELECT DISTINCT p.mapel_id, COALESCE(m.nama_mata_pelajaran, m.id::text) AS nama_mapel
             FROM   akademik.pemetaan_mapel p
             JOIN   akademik.mata_pelajaran m ON (p.mapel_id = m.id::text OR p.mapel_id::text = m.id::text)
             WHERE  (p.kelas_id = :kelas_id OR p.kelas_id::text = :kelas_id)
               AND  p.tahun_ajaran = :tahun_ajaran
               AND  (p.semester = :sem1 OR p.semester = :sem2)
               AND  (p.tenant_id::text = :tenant_id OR p.tenant_id IS NULL)
               AND  (p.is_active = true OR p.is_active IS NULL)
               AND  (m.is_active = true OR m.is_active IS NULL)"
        );
        $stMapelNames->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'sem1' => $semList[0], 'sem2' => $semList[1], 'tenant_id' => $tenantId]);
        $subjectsMap = [];
        foreach ($stMapelNames->fetchAll(PDO::FETCH_ASSOC) as $sub) {
            $subjectsMap[$sub['mapel_id']] = $sub['nama_mapel'];
        }

        $db->beginTransaction();
        try {
            $stDel = $db->prepare(
                "DELETE FROM akademik.detail_nilai_rapor
                 WHERE  (tenant_id::text = :tenant_id OR tenant_id IS NULL)
                   AND  (siswa_id = :siswa_id OR siswa_id::text = :siswa_id)
                   AND  (kelas_id = :kelas_id OR kelas_id::text = :kelas_id)
                   AND  tahun_ajaran = :tahun_ajaran
                   AND  (semester = :sem1 OR semester = :sem2)
                   AND  (mapel_id = :mapel_id OR mapel_id::text = :mapel_id)"
            );
            $stIns = $db->prepare(
                "INSERT INTO akademik.detail_nilai_rapor
                     (tenant_id, siswa_id, kelas_id, tahun_ajaran, semester, mapel_id,
                      nilai_akhir, capaian_kompetensi, deskripsi_capaian, deskripsi_keterampilan,
                      nilai_tp, nilai_sts, nilai_sas)
                 VALUES
                     (:tenant_id, :siswa_id, :kelas_id, :tahun_ajaran, :semester, :mapel_id,
                      :nilai_akhir, :capaian_kompetensi, :deskripsi_capaian, :deskripsi_keterampilan,
                      :nilai_tp, :nilai_sts, :nilai_sas)"
            );

            foreach ($grades as $entry) {
                $sId  = $entry['siswa_id'] ?? '';
                $mId  = $entry['mapel_id'] ?? '';
                $val  = isset($entry['nilai_akhir']) && $entry['nilai_akhir'] !== '' ? (float)$entry['nilai_akhir'] : null;
                $cap  = $entry['capaian_kompetensi'] ?? $entry['detail']['capaian_tertinggi'] ?? null;
                $desk = $entry['deskripsi_capaian'] ?? $cap;
                $deskKet = $entry['deskripsi_keterampilan'] ?? null;
                $tp   = isset($entry['nilai_tp']) && $entry['nilai_tp'] !== '' ? (float)$entry['nilai_tp'] : null;
                $sts  = isset($entry['nilai_sts']) && $entry['nilai_sts'] !== '' ? (float)$entry['nilai_sts'] : null;
                $sas  = isset($entry['nilai_sas']) && $entry['nilai_sas'] !== '' ? (float)$entry['nilai_sas'] : null;

                if (empty($sId) || empty($mId)) continue;

                $studentReligion = $studentsMap[$sId] ?? null;
                $mapelName       = $subjectsMap[$mId]  ?? '';
                if ($this->isReligionSubjectMismatch($studentReligion, $mapelName)) continue;

                // Auto-generate capaian jika belum terisi dan nilai akhir ada
                if (empty($desk) && $val !== null) {
                    $desk = $this->generateCapaianNarrative($mapelName, $val);
                }

                $stDel->execute([
                    'tenant_id'    => $tenantId, 'siswa_id' => $sId,
                    'kelas_id'     => $kelasId,  'tahun_ajaran' => $tahunAjaran,
                    'sem1'         => $semList[0], 'sem2' => $semList[1],  'mapel_id' => $mId,
                ]);
                $stIns->execute([
                    'tenant_id'              => $tenantId,
                    'siswa_id'               => $sId,
                    'kelas_id'               => $kelasId,
                    'tahun_ajaran'           => $tahunAjaran,
                    'semester'               => $semester,
                    'mapel_id'               => $mId,
                    'nilai_akhir'            => $val,
                    'capaian_kompetensi'     => $cap ?: $desk,
                    'deskripsi_capaian'      => $desk,
                    'deskripsi_keterampilan' => $deskKet,
                    'nilai_tp'               => $tp,
                    'nilai_sts'              => $sts,
                    'nilai_sas'              => $sas
                ]);
            }

            $db->commit();
            $this->jsonResponse(true, ['message' => 'Nilai rapor berhasil disimpan.']);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->jsonResponse(false, null, 'Gagal menyimpan nilai: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper Generator Narasi Capaian Pembelajaran (Standar Kemendikbudristek)
     */
    private function generateCapaianNarrative(string $mapelName, float $score): string {
        if ($score >= 88) {
            return "Menunjukkan penguasaan materi " . $mapelName . " yang sangat baik dan mampu menerapkannya secara mandiri.";
        } elseif ($score >= 75) {
            return "Menunjukkan pemahaman yang baik dan tuntas dalam capaian pembelajaran " . $mapelName . ".";
        } elseif ($score >= 65) {
            return "Cukup menguasai konsep dasar " . $mapelName . ", perlu latihan tambahan pada materi lanjutan.";
        } else {
            return "Perlu pendampingan dan bimbingan lebih intensif dalam menguasai materi pokok " . $mapelName . ".";
        }
    }

    /**
     * GET /api/v1/nilai-rapor/export
     */
    public function export(): void {
        $db          = Database::getConnection();
        $kelasId     = $_GET['kelas_id']    ?? '';
        $tahunAjaran = $_GET['tahun_ajaran'] ?? '';
        $semester    = $_GET['semester']     ?? '';

        $tenantId = $this->resolveTenantId($db, $kelasId);
        if (!$tenantId)  die("Tenant ID tidak terdeteksi.");
        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) die("Parameter tidak lengkap.");

        $stKelas = $db->prepare("SELECT nama_kelas FROM akademik.kelas WHERE (id::text = :id) LIMIT 1");
        $stKelas->execute(['id' => $kelasId]);
        $kelasName = $stKelas->fetchColumn() ?: 'Kelas';

        $semList = ($semester === 'Ganjil' || $semester === '1') ? ['Ganjil', '1'] : ['Genap', '2'];

        $stMapel = $db->prepare(
            "SELECT DISTINCT p.mapel_id, COALESCE(m.nama_mata_pelajaran, m.id::text) AS nama_mapel
             FROM   akademik.pemetaan_mapel p
             JOIN   akademik.mata_pelajaran m ON p.mapel_id::text = m.id::text
             WHERE  p.kelas_id::text = :kelas_id
               AND  p.tahun_ajaran = :tahun_ajaran
               AND  (p.semester = :sem1 OR p.semester = :sem2)
               AND  (p.tenant_id::text = :tenant_id OR p.tenant_id IS NULL)
               AND  (p.is_active = true OR p.is_active IS NULL)
               AND  (m.is_active = true OR m.is_active IS NULL)
             ORDER BY nama_mapel ASC"
        );
        $stMapel->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'sem1' => $semList[0], 'sem2' => $semList[1], 'tenant_id' => $tenantId]);
        $subjects = $stMapel->fetchAll(PDO::FETCH_ASSOC);

        $students = $this->getStudentsInKelas($db, $kelasId, $tenantId, $tahunAjaran, $semester);

        $stGrades = $db->prepare(
            "SELECT siswa_id, mapel_id, nilai_akhir, capaian_kompetensi
             FROM   akademik.detail_nilai_rapor
             WHERE  (kelas_id = :kelas_id OR kelas_id::text = :kelas_id)
               AND  tahun_ajaran = :tahun_ajaran
               AND  (semester = :sem1 OR semester = :sem2)
               AND  (tenant_id::text = :tenant_id OR tenant_id IS NULL)
               AND  is_active = true"
        );
        $stGrades->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'sem1' => $semList[0], 'sem2' => $semList[1], 'tenant_id' => $tenantId]);
        $gradesList = $stGrades->fetchAll(PDO::FETCH_ASSOC);

        $gradesMatrix = [];
        foreach ($gradesList as $row) {
            $gradesMatrix[$row['siswa_id']][$row['mapel_id']] = [
                'nilai_akhir'        => $row['nilai_akhir'],
                'capaian_kompetensi' => $row['capaian_kompetensi'] ?? '',
            ];
        }

        $metaHeader = [
            "#METADATA:TAHUN_AJARAN={$tahunAjaran}|SEMESTER={$semester}|KELAS_ID={$kelasId}|KELAS_NAME={$kelasName}",
            "TAHUN_AJARAN: {$tahunAjaran}",
            "SEMESTER: {$semester}",
            "KELAS: {$kelasName}"
        ];

        $header = ['Siswa ID', 'NISN', 'Nama Siswa'];
        foreach ($subjects as $sub) {
            $header[] = "{$sub['nama_mapel']} - Nilai Akhir [{$sub['mapel_id']}]";
            $header[] = "{$sub['nama_mapel']} - Capaian Kompetensi [{$sub['mapel_id']}]";
        }
        $excelData = [$metaHeader, $header];

        foreach ($students as $stu) {
            $row = [(string)$stu['id'], (string)($stu['nisn'] ?: $stu['nis'] ?: '-'), (string)$stu['nama_lengkap']];
            foreach ($subjects as $sub) {
                $mid = $sub['mapel_id'];
                if ($this->isReligionSubjectMismatch($stu['agama'] ?? null, $sub['nama_mapel'])) {
                    $row[] = 'N/A'; $row[] = 'N/A';
                } else {
                    $g     = $gradesMatrix[$stu['id']][$mid] ?? null;
                    $row[] = isset($g['nilai_akhir']) && $g['nilai_akhir'] !== null ? (float)$g['nilai_akhir'] : '';
                    $row[] = (string)($g['capaian_kompetensi'] ?? '');
                }
            }
            $excelData[] = $row;
        }

        $filename = "Matriks_Nilai_Rapor_" . str_replace(' ', '_', $kelasName) . "_" . str_replace('/', '-', $tahunAjaran) . "_{$semester}.xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
        exit;
    }

    /**
     * POST /api/v1/nilai-rapor/import-validate
     */
    public function importValidate(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, null, 'Method not allowed.', 405); return;
        }

        $kelasId     = $_POST['kelas_id']    ?? '';
        $tahunAjaran = $_POST['tahun_ajaran'] ?? '';
        $semester    = $_POST['semester']     ?? '';

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(false, null, 'Parameter tidak lengkap.', 400); return;
        }

        $db       = Database::getConnection();
        $tenantId = $this->resolveTenantId($db, $kelasId);
        if (!$tenantId) { $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi.', 400); return; }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(false, null, 'File tidak terunggah.', 400); return;
        }

        $fileExt = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'xlsx') {
            $this->jsonResponse(false, null, 'Hanya file .xlsx yang didukung.', 400); return;
        }

        $xlsx = \Shuchkin\SimpleXLSX::parse($_FILES['file']['tmp_name']);
        if (!$xlsx) {
            $this->jsonResponse(false, null, 'Gagal membaca Excel: ' . \Shuchkin\SimpleXLSX::parseError(), 400); return;
        }

        $rows = $xlsx->rows();
        if (empty($rows)) { $this->jsonResponse(false, null, 'File Excel kosong.', 400); return; }

        // Check for #METADATA in Row 0
        if (isset($rows[0][0]) && strpos($rows[0][0], '#METADATA:') === 0) {
            $metaStr = array_shift($rows)[0];
            preg_match('/TAHUN_AJARAN=([^|]+)/', $metaStr, $mTA);
            preg_match('/SEMESTER=([^|]+)/', $metaStr, $mSem);
            preg_match('/KELAS_ID=([^|]+)/', $metaStr, $mKelasId);
            preg_match('/KELAS_NAME=([^|]+)/', $metaStr, $mKelasName);

            $fileTA        = trim($mTA[1] ?? '');
            $fileSem       = trim($mSem[1] ?? '');
            $fileKelasId   = trim($mKelasId[1] ?? '');
            $fileKelasName = trim($mKelasName[1] ?? '');

            $semMatch = (strtolower($fileSem) === strtolower($semester)) ||
                       (($fileSem === '1' || $fileSem === 'Ganjil') && ($semester === '1' || $semester === 'Ganjil')) ||
                       (($fileSem === '2' || $fileSem === 'Genap') && ($semester === '2' || $semester === 'Genap'));

            if ($fileTA !== $tahunAjaran || !$semMatch || (!empty($fileKelasId) && $fileKelasId !== $kelasId)) {
                $stK = $db->prepare("SELECT nama_kelas FROM akademik.kelas WHERE id::text = :id LIMIT 1");
                $stK->execute(['id' => $kelasId]);
                $selKelasName = $stK->fetchColumn() ?: 'Kelas';

                $msg = "Berkas Excel ini untuk Tahun Ajaran {$fileTA} - Semester {$fileSem} - Kelas {$fileKelasName}.\n" .
                       "TIDAK COCOK dengan filter terpilih (Tahun Ajaran {$tahunAjaran} - Semester {$semester} - Kelas {$selKelasName}).\n" .
                       "Unggah dibatalkan demi keamanan data.";
                $this->jsonResponse(false, null, $msg, 400);
                return;
            }
        }

        $header = array_shift($rows);
        if (isset($header[0])) $header[0] = preg_replace('/^[\x{FEFF}\x{200B}]+/u', '', $header[0]);

        $mapelCols = [];
        foreach ($header as $idx => $colName) {
            if ($idx < 3) continue;
            if (preg_match('/\[([^\]]+)\]/', $colName, $matches)) {
                $mapelId = $matches[1];
                $type    = strpos($colName, 'Capaian') !== false ? 'capaian' : 'nilai_akhir';
                $mapelCols[$idx] = ['mapel_id' => $mapelId, 'type' => $type];
            }
        }

        if (empty($mapelCols)) {
            $this->jsonResponse(false, null, 'Kolom mata pelajaran tidak ditemukan dalam file.', 400); return;
        }

        // Fetch official students enrolled in target class and year
        $officialStudents  = $this->getStudentsInKelas($db, $kelasId, $tenantId, $tahunAjaran, $semester);
        $officialIds       = array_column($officialStudents, 'id');
        $officialIdsMap    = array_flip($officialIds);

        $stK = $db->prepare("SELECT nama_kelas FROM akademik.kelas WHERE id::text = :id LIMIT 1");
        $stK->execute(['id' => $kelasId]);
        $selKelasName = $stK->fetchColumn() ?: 'Kelas';

        $stSiswaList = $db->prepare("SELECT id::text AS id, nisn, nis, nama_lengkap, agama FROM siswa.siswa WHERE (tenant_id::text = :tenant_id OR tenant_id IS NULL) AND (is_active = true OR is_active IS NULL)");
        $stSiswaList->execute(['tenant_id' => $tenantId]);
        $studentsMap = [];
        foreach ($stSiswaList->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $studentsMap[$s['id']] = $s;
        }

        $semList = ($semester === 'Ganjil' || $semester === '1') ? ['Ganjil', '1'] : ['Genap', '2'];

        $stMapelNames = $db->prepare("SELECT DISTINCT p.mapel_id, COALESCE(m.nama_mata_pelajaran, m.id::text) AS nama_mapel FROM akademik.pemetaan_mapel p JOIN akademik.mata_pelajaran m ON (p.mapel_id = m.id::text OR p.mapel_id::text = m.id::text) WHERE (p.kelas_id = :kelas_id OR p.kelas_id::text = :kelas_id) AND p.tahun_ajaran = :tahun_ajaran AND (p.semester = :sem1 OR p.semester = :sem2) AND (p.tenant_id::text = :tenant_id OR p.tenant_id IS NULL) AND (p.is_active = true OR p.is_active IS NULL) AND (m.is_active = true OR m.is_active IS NULL)");
        $stMapelNames->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'sem1' => $semList[0], 'sem2' => $semList[1], 'tenant_id' => $tenantId]);
        $subjectsMap = [];
        foreach ($stMapelNames->fetchAll(PDO::FETCH_ASSOC) as $sub) $subjectsMap[$sub['mapel_id']] = $sub['nama_mapel'];

        $validCount   = 0;
        $warningCount = 0;
        $errorCount   = 0;
        $previewRows  = [];

        foreach ($rows as $rIdx => $row) {
            if (empty(array_filter($row))) continue;
            $siswaId     = trim((string)($row[0] ?? ''));
            $nisn        = trim((string)($row[1] ?? ''));
            $namaLengkap = trim((string)($row[2] ?? ''));

            if (empty($siswaId)) continue;

            $errors   = [];
            $warnings = [];
            $status   = 'valid';

            $stuData = $studentsMap[$siswaId] ?? null;
            if (!$stuData) {
                foreach ($studentsMap as $sId => $sInfo) {
                    if ((!empty($nisn) && ($sInfo['nisn'] === $nisn || $sInfo['nis'] === $nisn)) ||
                        (!empty($namaLengkap) && strcasecmp($sInfo['nama_lengkap'], $namaLengkap) === 0)) {
                        $stuData = $sInfo;
                        $siswaId = $sId;
                        break;
                    }
                }
            }

            if (!$stuData) {
                $status   = 'error';
                $errors[] = "Siswa ID '{$siswaId}' (" . ($namaLengkap ?: 'Tanpa Nama') . ") tidak ditemukan di database sekolah.";
                $errorCount++;
            } else if (!isset($officialIdsMap[$stuData['id']])) {
                $status   = 'error';
                $errors[] = "Siswa '{$stuData['nama_lengkap']}' (NISN: " . ($stuData['nisn'] ?: '-') . ") TIDAK TERDAFTAR di kelas {$selKelasName} pada Tahun Ajaran {$tahunAjaran}.";
                $errorCount++;
            } else {
                $namaLengkap = $stuData['nama_lengkap'];
                $hasValues = false;
                foreach ($mapelCols as $idx => $colInfo) {
                    $rawVal = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                    if ($rawVal === '' || strtolower($rawVal) === 'n/a') continue;
                    $hasValues = true;
                    if ($colInfo['type'] === 'nilai_akhir' && !is_numeric($rawVal)) {
                        $warnings[] = "Nilai mapel '" . ($subjectsMap[$colInfo['mapel_id']] ?? $colInfo['mapel_id']) . "' ('{$rawVal}') bukan angka.";
                    }
                }
                if (!$hasValues) {
                    $warnings[] = "Tidak ada nilai baru yang diisi untuk siswa ini.";
                }

                if (!empty($errors)) {
                    $status = 'error';
                    $errorCount++;
                } else if (!empty($warnings)) {
                    $status = 'warning';
                    $warningCount++;
                } else {
                    $validCount++;
                }
            }

            $previewRows[] = [
                'siswa_id'     => $siswaId,
                'nama_lengkap' => $namaLengkap ?: "Siswa #{$rIdx}",
                'status'       => $status,
                'errors'       => $errors,
                'warnings'     => $warnings,
            ];
        }

        $summary = [
            'total_rows' => count($previewRows),
            'valid'      => $validCount,
            'warning'    => $warningCount,
            'error'      => $errorCount,
        ];

        $this->jsonResponse(true, [
            'summary' => $summary,
            'data'    => $previewRows,
        ]);
    }

    /**
     * POST /api/v1/nilai-rapor/import
     */
    public function import(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, null, 'Method not allowed.', 405); return;
        }

        $kelasId     = $_POST['kelas_id']    ?? '';
        $tahunAjaran = $_POST['tahun_ajaran'] ?? '';
        $semester    = $_POST['semester']     ?? '';

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(false, null, 'Parameter tidak lengkap.', 400); return;
        }

        $db       = Database::getConnection();
        $tenantId = $this->resolveTenantId($db, $kelasId);
        if (!$tenantId) { $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi.', 400); return; }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(false, null, 'File tidak terunggah.', 400); return;
        }

        $fileExt = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'xlsx') {
            $this->jsonResponse(false, null, 'Hanya file .xlsx yang didukung.', 400); return;
        }

        $xlsx = \Shuchkin\SimpleXLSX::parse($_FILES['file']['tmp_name']);
        if (!$xlsx) {
            $this->jsonResponse(false, null, 'Gagal membaca Excel: ' . \Shuchkin\SimpleXLSX::parseError(), 400); return;
        }

        $rows = $xlsx->rows();
        if (empty($rows)) { $this->jsonResponse(false, null, 'File Excel kosong.', 400); return; }

        // Check for #METADATA in Row 0
        if (isset($rows[0][0]) && strpos($rows[0][0], '#METADATA:') === 0) {
            $metaStr = array_shift($rows)[0];
            preg_match('/TAHUN_AJARAN=([^|]+)/', $metaStr, $mTA);
            preg_match('/SEMESTER=([^|]+)/', $metaStr, $mSem);
            preg_match('/KELAS_ID=([^|]+)/', $metaStr, $mKelasId);
            preg_match('/KELAS_NAME=([^|]+)/', $metaStr, $mKelasName);

            $fileTA        = trim($mTA[1] ?? '');
            $fileSem       = trim($mSem[1] ?? '');
            $fileKelasId   = trim($mKelasId[1] ?? '');
            $fileKelasName = trim($mKelasName[1] ?? '');

            $semMatch = (strtolower($fileSem) === strtolower($semester)) ||
                       (($fileSem === '1' || $fileSem === 'Ganjil') && ($semester === '1' || $semester === 'Ganjil')) ||
                       (($fileSem === '2' || $fileSem === 'Genap') && ($semester === '2' || $semester === 'Genap'));

            if ($fileTA !== $tahunAjaran || !$semMatch || (!empty($fileKelasId) && $fileKelasId !== $kelasId)) {
                $stK = $db->prepare("SELECT nama_kelas FROM akademik.kelas WHERE id::text = :id LIMIT 1");
                $stK->execute(['id' => $kelasId]);
                $selKelasName = $stK->fetchColumn() ?: 'Kelas';

                $msg = "Berkas Excel ini untuk Tahun Ajaran {$fileTA} - Semester {$fileSem} - Kelas {$fileKelasName}.\n" .
                       "TIDAK COCOK dengan filter terpilih (Tahun Ajaran {$tahunAjaran} - Semester {$semester} - Kelas {$selKelasName}).\n" .
                       "Unggah dibatalkan demi keamanan data.";
                $this->jsonResponse(false, null, $msg, 400);
                return;
            }
        }

        $header = array_shift($rows);
        if (isset($header[0])) $header[0] = preg_replace('/^[\x{FEFF}\x{200B}]+/u', '', $header[0]);

        $mapelCols = [];
        foreach ($header as $idx => $colName) {
            if ($idx < 3) continue;
            if (preg_match('/\[([^\]]+)\]/', $colName, $matches)) {
                $mapelId = $matches[1];
                $type    = strpos($colName, 'Capaian') !== false ? 'capaian' : 'nilai_akhir';
                $mapelCols[$idx] = ['mapel_id' => $mapelId, 'type' => $type];
            }
        }

        if (empty($mapelCols)) {
            $this->jsonResponse(false, null, 'Kolom mata pelajaran tidak ditemukan dalam file.', 400); return;
        }

        // Fetch official students enrolled in target class and year
        $officialStudents  = $this->getStudentsInKelas($db, $kelasId, $tenantId, $tahunAjaran, $semester);
        $officialIds       = array_column($officialStudents, 'id');
        $officialIdsMap    = array_flip($officialIds);

        $stK = $db->prepare("SELECT nama_kelas FROM akademik.kelas WHERE id::text = :id LIMIT 1");
        $stK->execute(['id' => $kelasId]);
        $selKelasName = $stK->fetchColumn() ?: 'Kelas';

        $stSiswaList = $db->prepare("SELECT id::text AS id, nisn, nis, nama_lengkap, agama FROM siswa.siswa WHERE (tenant_id::text = :tenant_id OR tenant_id IS NULL) AND (is_active = true OR is_active IS NULL)");
        $stSiswaList->execute(['tenant_id' => $tenantId]);
        $studentsMap = [];
        foreach ($stSiswaList->fetchAll(PDO::FETCH_ASSOC) as $s) $studentsMap[$s['id']] = $s;

        $semList = ($semester === 'Ganjil' || $semester === '1') ? ['Ganjil', '1'] : ['Genap', '2'];

        $stMapelNames = $db->prepare("SELECT DISTINCT p.mapel_id, COALESCE(m.nama_mata_pelajaran, m.id::text) AS nama_mapel FROM akademik.pemetaan_mapel p JOIN akademik.mata_pelajaran m ON (p.mapel_id = m.id::text OR p.mapel_id::text = m.id::text) WHERE (p.kelas_id = :kelas_id OR p.kelas_id::text = :kelas_id) AND p.tahun_ajaran = :tahun_ajaran AND (p.semester = :sem1 OR p.semester = :sem2) AND (p.tenant_id::text = :tenant_id OR p.tenant_id IS NULL) AND (p.is_active = true OR p.is_active IS NULL) AND (m.is_active = true OR m.is_active IS NULL)");
        $stMapelNames->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'sem1' => $semList[0], 'sem2' => $semList[1], 'tenant_id' => $tenantId]);
        $subjectsMap = [];
        foreach ($stMapelNames->fetchAll(PDO::FETCH_ASSOC) as $sub) $subjectsMap[$sub['mapel_id']] = $sub['nama_mapel'];

        $db->beginTransaction();
        $successCount = 0;
        try {
            $stDel = $db->prepare("DELETE FROM akademik.detail_nilai_rapor WHERE (tenant_id::text = :tenant_id OR tenant_id IS NULL) AND (siswa_id = :siswa_id OR siswa_id::text = :siswa_id) AND (kelas_id = :kelas_id OR kelas_id::text = :kelas_id) AND tahun_ajaran = :tahun_ajaran AND (semester = :sem1 OR semester = :sem2) AND (mapel_id = :mapel_id OR mapel_id::text = :mapel_id)");
            $stIns = $db->prepare("INSERT INTO akademik.detail_nilai_rapor (tenant_id, siswa_id, kelas_id, tahun_ajaran, semester, mapel_id, nilai_akhir, capaian_kompetensi) VALUES (:tenant_id, :siswa_id, :kelas_id, :tahun_ajaran, :semester, :mapel_id, :nilai_akhir, :capaian_kompetensi)");

            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue;
                $siswaId     = trim((string)($row[0] ?? ''));
                $nisn        = trim((string)($row[1] ?? ''));
                $namaLengkap = trim((string)($row[2] ?? ''));

                if (empty($siswaId)) continue;

                $stuData = $studentsMap[$siswaId] ?? null;
                if (!$stuData) {
                    foreach ($studentsMap as $sId => $sInfo) {
                        if ((!empty($nisn) && ($sInfo['nisn'] === $nisn || $sInfo['nis'] === $nisn)) ||
                            (!empty($namaLengkap) && strcasecmp($sInfo['nama_lengkap'], $namaLengkap) === 0)) {
                            $stuData = $sInfo;
                            $siswaId = $sId;
                            break;
                        }
                    }
                }

                if (!$stuData || !isset($officialIdsMap[$stuData['id']])) {
                    continue;
                }

                $studentGrades = [];
                foreach ($mapelCols as $idx => $colInfo) {
                    $rawVal = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                    if ($rawVal === '' || strtolower($rawVal) === 'n/a') continue;
                    $studentGrades[$colInfo['mapel_id']][$colInfo['type']] = $rawVal;
                }

                foreach ($studentGrades as $mapelId => $data) {
                    $studentReligion = $stuData['agama'] ?? null;
                    $mapelName       = $subjectsMap[$mapelId] ?? '';
                    if ($this->isReligionSubjectMismatch($studentReligion, $mapelName)) continue;

                    $val = isset($data['nilai_akhir']) ? (is_numeric($data['nilai_akhir']) ? (float)$data['nilai_akhir'] : null) : null;
                    $cap = $data['capaian'] ?? null;

                    $stDel->execute(['tenant_id' => $tenantId, 'siswa_id' => $siswaId, 'kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'sem1' => $semList[0], 'sem2' => $semList[1], 'mapel_id' => $mapelId]);
                    $stIns->execute(['tenant_id' => $tenantId, 'siswa_id' => $siswaId, 'kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'semester' => $semester, 'mapel_id' => $mapelId, 'nilai_akhir' => $val, 'capaian_kompetensi' => $cap]);
                }
                $successCount++;
            }

            if ($successCount === 0) {
                $db->rollBack();
                $this->jsonResponse(false, null, "Tidak ada siswa dalam berkas Excel yang terdaftar di kelas {$selKelasName} pada Tahun Ajaran {$tahunAjaran}. Impor dibatalkan.", 400);
                return;
            }

            $db->commit();
            $this->jsonResponse(true, ['message' => "Berhasil mengimpor nilai untuk {$successCount} siswa."]);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->jsonResponse(false, null, 'Gagal impor: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/nilai-rapor/delete-siswa
     */
    public function deleteSiswaGradesApi(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(false, null, 'Method not allowed.', 405); return;
            }

            $siswaId     = $_POST['siswa_id']    ?? '';
            $kelasId     = $_POST['kelas_id']    ?? '';
            $tahunAjaran = $_POST['tahun_ajaran'] ?? '';
            $semester    = $_POST['semester']     ?? '';

            if (!$siswaId || !$kelasId || !$tahunAjaran || !$semester) {
                $this->jsonResponse(false, null, 'Parameter tidak lengkap.', 400); return;
            }

            $role = $_SESSION['role_name'] ?? '';
            if ($role !== 'super_admin' && $role !== 'admin' && $role !== 'operator_sekolah') {
                $this->jsonResponse(false, null, 'Anda tidak memiliki akses.', 403); return;
            }

            $db       = Database::getConnection();
            $tenantId = $this->resolveTenantId($db, $kelasId);
            if (!$tenantId) {
                $this->jsonResponse(false, null, 'Sekolah belum dipilih.', 400); return;
            }

            $stLock = $db->prepare("SELECT is_locked_nilai FROM akademik.kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ? AND is_active = true");
            $stLock->execute([$tenantId, $tahunAjaran, $semester]);
            if ($stLock->fetchColumn()) {
                $this->jsonResponse(false, null, 'Gagal menghapus. Input Nilai Rapor telah dikunci oleh administrator.', 403); return;
            }

            $db->beginTransaction();
            try {
                $stmt = $db->prepare("UPDATE akademik.detail_nilai_rapor SET is_active = false, updated_at = NOW() WHERE (siswa_id = ? OR siswa_id::text = ?) AND (kelas_id = ? OR kelas_id::text = ?) AND tahun_ajaran = ? AND semester = ? AND tenant_id = ? AND is_active = true");
                $stmt->execute([$siswaId, $siswaId, $kelasId, $kelasId, $tahunAjaran, $semester, $tenantId]);
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }

            $this->jsonResponse(true, ['message' => 'Nilai rapor siswa berhasil dihapus.']);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }
}
