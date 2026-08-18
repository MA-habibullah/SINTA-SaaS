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
        if (!$tenantId || $tenantId === '00000000-0000-0000-0000-000000000000') {
            $tenantId = null;
        }
        if (!$tenantId && !empty($_GET['tenant_id']))  $tenantId = $_GET['tenant_id'];
        if (!$tenantId && !empty($_POST['tenant_id'])) $tenantId = $_POST['tenant_id'];
        if (!$tenantId && !empty($kelasId)) {
            $st = $db->prepare("SELECT tenant_id FROM akademik.kelas WHERE id::text = :id LIMIT 1");
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

    private function getStudentsInKelas(PDO $db, string $kelasId, string $tenantId, string $tahunAjaran, string $semester): array {
        $st = $db->prepare(
            "SELECT s.id, s.nama_lengkap, s.nisn, s.nis, s.agama
             FROM   siswa.siswa s
             WHERE  s.tenant_id = :tenant_id
               AND  (s.is_active = true OR s.is_active IS NULL)
               AND  (
                     -- Match by UUID langsung
                     s.kelas_saat_ini::text = :kelas_id1
                     -- Match by nama_kelas (kelas_saat_ini menyimpan nama, bukan UUID)
                  OR s.kelas_saat_ini IN (
                         SELECT nama_kelas FROM akademik.kelas
                         WHERE id::text = :kelas_id4
                     )
                  OR s.id::text IN (
                         SELECT siswa_id::text
                         FROM   akademik.detail_nilai_rapor
                         WHERE  kelas_id::text = :kelas_id2
                           AND  tahun_ajaran = :tahun_ajaran
                           AND  semester     = :semester
                           AND  is_active    = true
                     )
                  OR s.id::text IN (
                         SELECT siswa_id::text
                         FROM   siswa.riwayat_kenaikan_kelas
                         WHERE  dari_kelas::text = :kelas_id3
                           AND  tahun_ajaran = :tahun_ajaran2
                     )
               )
             ORDER BY s.nama_lengkap ASC"
        );
        $st->execute([
            'kelas_id1'    => $kelasId,
            'kelas_id2'    => $kelasId,
            'kelas_id3'    => $kelasId,
            'kelas_id4'    => $kelasId,
            'tahun_ajaran' => $tahunAjaran,
            'tahun_ajaran2'=> $tahunAjaran,
            'semester'     => $semester,
            'tenant_id'    => $tenantId,
        ]);
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

        $stMapel = $db->prepare(
            "SELECT DISTINCT p.mapel_id,
                    m.id::text AS kode_mapel,
                    COALESCE(m.nama_mata_pelajaran, m.id::text) AS nama_mapel
             FROM   akademik.pemetaan_mapel p
             JOIN   akademik.mata_pelajaran m ON (p.mapel_id = m.id::text OR p.mapel_id::text = m.id::text)
             WHERE  (p.kelas_id = :kelas_id OR p.kelas_id::text = :kelas_id)
               AND  p.tahun_ajaran = :tahun_ajaran
               AND  p.semester     = :semester
               AND  p.tenant_id    = :tenant_id
               AND  p.is_active    = true
               AND  m.is_active    = true
             ORDER BY nama_mapel ASC"
        );
        $stMapel->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'semester' => $semester, 'tenant_id' => $tenantId]);
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
               AND  kk.tenant_id   = :tenant_id
             LIMIT 1"
        );
        $stActive->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'tenant_id' => $tenantId]);
        $activeKur = $stActive->fetch(PDO::FETCH_ASSOC) ?: [
            'nama_kurikulum' => 'Kurikulum 2013 (K-13)',
            'tipe_penilaian' => 'kompleks',
            'is_locked'      => false,
        ];

        $stKunci = $db->prepare("SELECT is_locked_nilai FROM akademik.kunci_akademik WHERE tenant_id = :tid AND tahun_ajaran = :ta AND semester = :sem AND is_active = true LIMIT 1");
        $stKunci->execute(['tid' => $tenantId, 'ta' => $tahunAjaran, 'sem' => $semester]);
        $isLocked = (bool)($stKunci->fetchColumn() ?: false);

        $stGrades = $db->prepare(
            "SELECT siswa_id, mapel_id, nilai_akhir, capaian_kompetensi
             FROM   akademik.detail_nilai_rapor
             WHERE  (kelas_id = :kelas_id OR kelas_id::text = :kelas_id)
               AND  tahun_ajaran = :tahun_ajaran
               AND  semester     = :semester
               AND  tenant_id    = :tenant_id
               AND  is_active    = true"
        );
        $stGrades->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'semester' => $semester, 'tenant_id' => $tenantId]);
        $gradesList = $stGrades->fetchAll(PDO::FETCH_ASSOC);

        $gradesMatrix = [];
        foreach ($gradesList as $row) {
            $sId = $row['siswa_id'];
            $mId = $row['mapel_id'];
            if (!isset($gradesMatrix[$sId])) $gradesMatrix[$sId] = [];
            $gradesMatrix[$sId][$mId] = [
                'nilai_akhir'       => $row['nilai_akhir'] !== null ? (float)$row['nilai_akhir'] : null,
                'capaian_kompetensi'=> $row['capaian_kompetensi'] ?? '',
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

        $stLock = $db->prepare("SELECT is_locked_nilai FROM akademik.kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ? AND is_active = true");
        $stLock->execute([$tenantId, $tahunAjaran, $semester]);
        if ($stLock->fetchColumn()) {
            $this->jsonResponse(false, null, 'Input Nilai Rapor telah dikunci oleh administrator.', 403); return;
        }

        $stSiswaList = $db->prepare("SELECT id, agama FROM siswa.siswa WHERE (kelas_saat_ini::text = :kelas_id OR kelas_saat_ini IN (SELECT nama_kelas FROM akademik.kelas WHERE id::text = :kelas_id)) AND tenant_id = :tenant_id AND is_active = true");
        $stSiswaList->execute(['kelas_id' => $kelasId, 'tenant_id' => $tenantId]);
        $studentsMap = [];
        foreach ($stSiswaList->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $studentsMap[$s['id']] = $s['agama'] ?? null;
        }

        $stMapelNames = $db->prepare(
            "SELECT DISTINCT p.mapel_id, COALESCE(m.nama_mata_pelajaran, m.id::text) AS nama_mapel
             FROM   akademik.pemetaan_mapel p
             JOIN   akademik.mata_pelajaran m ON (p.mapel_id = m.id::text OR p.mapel_id::text = m.id::text)
             WHERE  (p.kelas_id = :kelas_id OR p.kelas_id::text = :kelas_id)
               AND  p.tahun_ajaran = :tahun_ajaran AND p.semester = :semester
               AND  p.tenant_id = :tenant_id AND p.is_active = true AND m.is_active = true"
        );
        $stMapelNames->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'semester' => $semester, 'tenant_id' => $tenantId]);
        $subjectsMap = [];
        foreach ($stMapelNames->fetchAll(PDO::FETCH_ASSOC) as $sub) {
            $subjectsMap[$sub['mapel_id']] = $sub['nama_mapel'];
        }

        $db->beginTransaction();
        try {
            $stDel = $db->prepare(
                "DELETE FROM akademik.detail_nilai_rapor
                 WHERE  tenant_id    = :tenant_id
                   AND  (siswa_id = :siswa_id OR siswa_id::text = :siswa_id)
                   AND  (kelas_id = :kelas_id OR kelas_id::text = :kelas_id)
                   AND  tahun_ajaran = :tahun_ajaran
                   AND  semester     = :semester
                   AND  (mapel_id = :mapel_id OR mapel_id::text = :mapel_id)"
            );
            $stIns = $db->prepare(
                "INSERT INTO akademik.detail_nilai_rapor
                     (tenant_id, siswa_id, kelas_id, tahun_ajaran, semester, mapel_id,
                      nilai_akhir, capaian_kompetensi)
                 VALUES
                     (:tenant_id, :siswa_id, :kelas_id, :tahun_ajaran, :semester, :mapel_id,
                      :nilai_akhir, :capaian_kompetensi)"
            );

            foreach ($grades as $entry) {
                $sId  = $entry['siswa_id'] ?? '';
                $mId  = $entry['mapel_id'] ?? '';
                $val  = isset($entry['nilai_akhir']) && $entry['nilai_akhir'] !== '' ? $entry['nilai_akhir'] : null;
                $cap  = $entry['capaian_kompetensi'] ?? $entry['detail']['capaian_tertinggi'] ?? null;
                if (empty($sId) || empty($mId)) continue;

                $studentReligion = $studentsMap[$sId] ?? null;
                $mapelName       = $subjectsMap[$mId]  ?? '';
                if ($this->isReligionSubjectMismatch($studentReligion, $mapelName)) continue;

                $stDel->execute([
                    'tenant_id'    => $tenantId, 'siswa_id' => $sId,
                    'kelas_id'     => $kelasId,  'tahun_ajaran' => $tahunAjaran,
                    'semester'     => $semester,  'mapel_id' => $mId,
                ]);
                $stIns->execute([
                    'tenant_id'         => $tenantId, 'siswa_id' => $sId,
                    'kelas_id'          => $kelasId,  'tahun_ajaran' => $tahunAjaran,
                    'semester'          => $semester,  'mapel_id' => $mId,
                    'nilai_akhir'       => $val,       'capaian_kompetensi' => $cap,
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

        $stKelas = $db->prepare("SELECT nama_kelas FROM akademik.kelas WHERE id::text = :id LIMIT 1");
        $stKelas->execute(['id' => $kelasId]);
        $kelasName = $stKelas->fetchColumn() ?: 'Kelas';

        $stMapel = $db->prepare(
            "SELECT DISTINCT p.mapel_id, COALESCE(m.nama_mata_pelajaran, m.id::text) AS nama_mapel
             FROM   akademik.pemetaan_mapel p
             JOIN   akademik.mata_pelajaran m ON (p.mapel_id = m.id::text OR p.mapel_id::text = m.id::text)
             WHERE  (p.kelas_id = :kelas_id OR p.kelas_id::text = :kelas_id)
               AND  p.tahun_ajaran = :tahun_ajaran AND p.semester = :semester
               AND  p.tenant_id = :tenant_id AND p.is_active = true AND m.is_active = true
             ORDER BY nama_mapel ASC"
        );
        $stMapel->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'semester' => $semester, 'tenant_id' => $tenantId]);
        $subjects = $stMapel->fetchAll(PDO::FETCH_ASSOC);

        $students = $this->getStudentsInKelas($db, $kelasId, $tenantId, $tahunAjaran, $semester);

        $stGrades = $db->prepare(
            "SELECT siswa_id, mapel_id, nilai_akhir, capaian_kompetensi
             FROM   akademik.detail_nilai_rapor
             WHERE  (kelas_id = :kelas_id OR kelas_id::text = :kelas_id)
               AND  tahun_ajaran = :tahun_ajaran AND semester = :semester
               AND  tenant_id = :tenant_id AND is_active = true"
        );
        $stGrades->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'semester' => $semester, 'tenant_id' => $tenantId]);
        $gradesList = $stGrades->fetchAll(PDO::FETCH_ASSOC);

        $gradesMatrix = [];
        foreach ($gradesList as $row) {
            $gradesMatrix[$row['siswa_id']][$row['mapel_id']] = [
                'nilai_akhir'        => $row['nilai_akhir'],
                'capaian_kompetensi' => $row['capaian_kompetensi'] ?? '',
            ];
        }

        $header = ['Siswa ID', 'NISN', 'Nama Siswa'];
        foreach ($subjects as $sub) {
            $header[] = "{$sub['nama_mapel']} - Nilai Akhir [{$sub['mapel_id']}]";
            $header[] = "{$sub['nama_mapel']} - Capaian Kompetensi [{$sub['mapel_id']}]";
        }
        $excelData = [$header];

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

        $filename = "format_nilai_" . str_replace(' ', '_', $kelasName) . "_" . str_replace('/', '-', $tahunAjaran) . "_{$semester}.xlsx";
        \Shuchkin\SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
        exit;
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

        $stSiswaList = $db->prepare("SELECT id, agama FROM siswa.siswa WHERE (kelas_saat_ini::text = :kelas_id OR kelas_saat_ini IN (SELECT nama_kelas FROM akademik.kelas WHERE id::text = :kelas_id)) AND tenant_id = :tenant_id AND is_active = true");
        $stSiswaList->execute(['kelas_id' => $kelasId, 'tenant_id' => $tenantId]);
        $studentsMap = [];
        foreach ($stSiswaList->fetchAll(PDO::FETCH_ASSOC) as $s) $studentsMap[$s['id']] = $s['agama'] ?? null;

        $stMapelNames = $db->prepare("SELECT DISTINCT p.mapel_id, COALESCE(m.nama_mata_pelajaran, m.id::text) AS nama_mapel FROM akademik.pemetaan_mapel p JOIN akademik.mata_pelajaran m ON (p.mapel_id = m.id::text OR p.mapel_id::text = m.id::text) WHERE (p.kelas_id = :kelas_id OR p.kelas_id::text = :kelas_id) AND p.tahun_ajaran = :tahun_ajaran AND p.semester = :semester AND p.tenant_id = :tenant_id AND p.is_active = true AND m.is_active = true");
        $stMapelNames->execute(['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'semester' => $semester, 'tenant_id' => $tenantId]);
        $subjectsMap = [];
        foreach ($stMapelNames->fetchAll(PDO::FETCH_ASSOC) as $sub) $subjectsMap[$sub['mapel_id']] = $sub['nama_mapel'];

        $db->beginTransaction();
        $successCount = 0;
        try {
            $stDel = $db->prepare("DELETE FROM akademik.detail_nilai_rapor WHERE tenant_id = :tenant_id AND (siswa_id = :siswa_id OR siswa_id::text = :siswa_id) AND (kelas_id = :kelas_id OR kelas_id::text = :kelas_id) AND tahun_ajaran = :tahun_ajaran AND semester = :semester AND (mapel_id = :mapel_id OR mapel_id::text = :mapel_id)");
            $stIns = $db->prepare("INSERT INTO akademik.detail_nilai_rapor (tenant_id, siswa_id, kelas_id, tahun_ajaran, semester, mapel_id, nilai_akhir, capaian_kompetensi) VALUES (:tenant_id, :siswa_id, :kelas_id, :tahun_ajaran, :semester, :mapel_id, :nilai_akhir, :capaian_kompetensi)");

            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue;
                $siswaId = trim((string)($row[0] ?? ''));
                if (empty($siswaId)) continue;

                $studentGrades = [];
                foreach ($mapelCols as $idx => $colInfo) {
                    $rawVal = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                    if ($rawVal === '' || strtolower($rawVal) === 'n/a') continue;
                    $studentGrades[$colInfo['mapel_id']][$colInfo['type']] = $rawVal;
                }

                foreach ($studentGrades as $mapelId => $data) {
                    $studentReligion = $studentsMap[$siswaId] ?? null;
                    $mapelName       = $subjectsMap[$mapelId] ?? '';
                    if ($this->isReligionSubjectMismatch($studentReligion, $mapelName)) continue;

                    $val = isset($data['nilai_akhir']) ? (is_numeric($data['nilai_akhir']) ? (float)$data['nilai_akhir'] : null) : null;
                    $cap = $data['capaian'] ?? null;

                    $stDel->execute(['tenant_id' => $tenantId, 'siswa_id' => $siswaId, 'kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'semester' => $semester, 'mapel_id' => $mapelId]);
                    $stIns->execute(['tenant_id' => $tenantId, 'siswa_id' => $siswaId, 'kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'semester' => $semester, 'mapel_id' => $mapelId, 'nilai_akhir' => $val, 'capaian_kompetensi' => $cap]);
                }
                $successCount++;
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
