<?php

namespace App\Controllers;

use App\Core\SessionManager;
use PDO;

class NilaiRaporController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        
        // Ensure the role is allowed
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $allowed = array_intersect($roles, ['super_admin', 'operator_sekolah', 'guru']);
        if (empty($allowed)) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak memiliki akses ke fitur ini.</p>");
        }
    }

    /**
     * GET /api/v1/nilai-rapor/grid
     * Fetch students, mapped subjects, and existing grades matrix.
     */
    public function getGrid(): void {
        $db = \App\Config\Database::getConnection();
        
        // Resolve tenant_id
        $tenantId = SessionManager::getTenantId();
        if (!$tenantId && !empty($_GET['tenant_id'])) {
            $tenantId = $_GET['tenant_id'];
        }
        if (!$tenantId && !empty($kelasId)) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id FROM kelas WHERE id = :kelas_id LIMIT 1");
            $stmtKelasTenant->execute(['kelas_id' => $kelasId]);
            $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
        }

        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        $kelasId = $_GET['kelas_id'] ?? '';
        $tahunAjaran = $_GET['tahun_ajaran'] ?? '';
        $semester = $_GET['semester'] ?? '';

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(['error' => 'Parameter kelas_id, tahun_ajaran, dan semester wajib diisi.'], 400);
            return;
        }

        // 1. Get subjects from pemetaan_mapel
        $qMapel = "SELECT DISTINCT p.mapel_id, m.kode_mapel, m.nama_mapel 
                   FROM pemetaan_mapel p
                   JOIN mata_pelajaran m ON p.mapel_id = m.id
                   WHERE p.kelas_id = :kelas_id 
                     AND p.tahun_ajaran = :tahun_ajaran 
                     AND p.semester = :semester
                     AND p.tenant_id = :tenant_id
                     AND p.deleted_at IS NULL
                     AND m.deleted_at IS NULL
                   ORDER BY m.nama_mapel ASC";
        $stmtMapel = $db->prepare($qMapel);
        $stmtMapel->execute([
            'kelas_id' => $kelasId,
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $semester,
            'tenant_id' => $tenantId
        ]);
        $subjects = $stmtMapel->fetchAll(PDO::FETCH_ASSOC);

        // 2. Get students in this class
        $qSiswa = "SELECT s.id, s.nama_lengkap, s.nisn, s.nis, s.agama
                   FROM siswa s
                   WHERE s.id_kelas = :kelas_id
                     AND s.tenant_id = :tenant_id
                     AND s.status = 'Aktif'
                     AND s.deleted_at IS NULL
                   ORDER BY s.nama_lengkap ASC";
        $stmtSiswa = $db->prepare($qSiswa);
        $stmtSiswa->execute([
            'kelas_id' => $kelasId,
            'tenant_id' => $tenantId
        ]);
        $students = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

        // 3. Get existing grades
        $qGrades = "SELECT siswa_id, mapel_id, nilai_akhir 
                    FROM detail_nilai_rapor
                    WHERE kelas_id = :kelas_id
                      AND tahun_ajaran = :tahun_ajaran
                      AND semester = :semester
                      AND tenant_id = :tenant_id
                      AND deleted_at IS NULL";
        $stmtGrades = $db->prepare($qGrades);
        $stmtGrades->execute([
            'kelas_id' => $kelasId,
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $semester,
            'tenant_id' => $tenantId
        ]);
        $gradesList = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

        // Structure grades as [siswa_id][mapel_id] => nilai_akhir
        $gradesMatrix = [];
        foreach ($gradesList as $row) {
            $sId = $row['siswa_id'];
            $mId = $row['mapel_id'];
            $val = $row['nilai_akhir'];
            
            if (!isset($gradesMatrix[$sId])) {
                $gradesMatrix[$sId] = [];
            }
            // Format to float if not null
            $gradesMatrix[$sId][$mId] = $val !== null ? (float)$val : null;
        }

        $this->jsonResponse([
            'subjects' => $subjects,
            'students' => $students,
            'grades' => $gradesMatrix
        ]);
    }

    /**
     * POST /api/v1/nilai-rapor/save
     * Save/Sync report card grades inline.
     */
    public function save(): void {
        $input = $this->getJsonInput();

        $kelasId = $input['kelas_id'] ?? '';
        $tahunAjaran = $input['tahun_ajaran'] ?? '';
        $semester = $input['semester'] ?? '';
        $grades = $input['grades'] ?? []; // Format: [ { siswa_id, mapel_id, nilai_akhir }, ... ]

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Parameter kelas_id, tahun_ajaran, dan semester wajib diisi.'], 400);
            return;
        }

        // Resolve tenant_id
        $db = \App\Config\Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if (!$tenantId && !empty($input['tenant_id'])) {
            $tenantId = $input['tenant_id'];
        }
        if (!$tenantId && !empty($kelasId)) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id FROM kelas WHERE id = :kelas_id LIMIT 1");
            $stmtKelasTenant->execute(['kelas_id' => $kelasId]);
            $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
        }

        if (!$tenantId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        // Fetch student religion mapping for validation
        $qSiswaList = "SELECT id, agama FROM siswa WHERE id_kelas = :kelas_id AND tenant_id = :tenant_id AND status = 'Aktif' AND deleted_at IS NULL";
        $stmtSiswaList = $db->prepare($qSiswaList);
        $stmtSiswaList->execute(['kelas_id' => $kelasId, 'tenant_id' => $tenantId]);
        $studentsMap = [];
        foreach ($stmtSiswaList->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $studentsMap[$s['id']] = $s['agama'] ?? null;
        }

        // Fetch subject names mapping for validation
        $qMapelNames = "SELECT DISTINCT p.mapel_id, m.nama_mapel 
                        FROM pemetaan_mapel p
                        JOIN mata_pelajaran m ON p.mapel_id = m.id
                        WHERE p.kelas_id = :kelas_id 
                          AND p.tahun_ajaran = :tahun_ajaran 
                          AND p.semester = :semester
                          AND p.tenant_id = :tenant_id
                          AND p.deleted_at IS NULL
                          AND m.deleted_at IS NULL";
        $stmtMapelNames = $db->prepare($qMapelNames);
        $stmtMapelNames->execute([
            'kelas_id' => $kelasId,
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $semester,
            'tenant_id' => $tenantId
        ]);
        $subjectsMap = [];
        foreach ($stmtMapelNames->fetchAll(PDO::FETCH_ASSOC) as $sub) {
            $subjectsMap[$sub['mapel_id']] = $sub['nama_mapel'];
        }

        $db->beginTransaction();
        try {
            $stmtUpsert = $db->prepare("
                INSERT INTO detail_nilai_rapor 
                    (tenant_id, siswa_id, kelas_id, tahun_ajaran, semester, mapel_id, nilai_akhir)
                VALUES 
                    (:tenant_id, :siswa_id, :kelas_id, :tahun_ajaran, :semester, :mapel_id, :nilai_akhir)
                ON DUPLICATE KEY UPDATE 
                    nilai_akhir = VALUES(nilai_akhir), 
                    updated_at = NOW()
            ");

            foreach ($grades as $entry) {
                $sId = $entry['siswa_id'] ?? '';
                $mId = $entry['mapel_id'] ?? '';
                $val = isset($entry['nilai_akhir']) && $entry['nilai_akhir'] !== '' ? $entry['nilai_akhir'] : null;

                if (empty($sId) || empty($mId)) {
                    continue;
                }

                // Check for student-subject religion mismatch
                $studentReligion = $studentsMap[$sId] ?? null;
                $mapelName = $subjectsMap[$mId] ?? '';
                if ($this->isReligionSubjectMismatch($studentReligion, $mapelName)) {
                    continue; // Skip saving grade if there is a religion mismatch
                }

                $stmtUpsert->execute([
                    'tenant_id' => $tenantId,
                    'siswa_id' => $sId,
                    'kelas_id' => $kelasId,
                    'tahun_ajaran' => $tahunAjaran,
                    'semester' => $semester,
                    'mapel_id' => $mId,
                    'nilai_akhir' => $val
                ]);
            }

            $db->commit();
            $this->jsonResponse(['status' => 'success', 'message' => 'Nilai rapor berhasil disimpan.']);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->jsonResponse(['status' => 'error', 'message' => 'Gagal menyimpan nilai: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/nilai-rapor/export
     * Download CSV template for grades input.
     */
    public function export(): void {
        $db = \App\Config\Database::getConnection();
        
        $kelasId = $_GET['kelas_id'] ?? '';
        $tahunAjaran = $_GET['tahun_ajaran'] ?? '';
        $semester = $_GET['semester'] ?? '';

        // Resolve tenant_id
        $tenantId = SessionManager::getTenantId();
        if (!$tenantId && !empty($_GET['tenant_id'])) {
            $tenantId = $_GET['tenant_id'];
        }
        if (!$tenantId && !empty($kelasId)) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id FROM kelas WHERE id = :kelas_id LIMIT 1");
            $stmtKelasTenant->execute(['kelas_id' => $kelasId]);
            $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
        }

        if (!$tenantId) {
            die("Tenant ID tidak terdeteksi.");
        }

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            die("Parameter kelas_id, tahun_ajaran, dan semester wajib diisi.");
        }

        // Get kelas name
        $stmtKelas = $db->prepare("SELECT nama_kelas FROM kelas WHERE id = :id LIMIT 1");
        $stmtKelas->execute(['id' => $kelasId]);
        $kelasName = $stmtKelas->fetchColumn() ?: 'Kelas';

        // 1. Get subjects
        $qMapel = "SELECT DISTINCT p.mapel_id, m.nama_mapel 
                   FROM pemetaan_mapel p
                   JOIN mata_pelajaran m ON p.mapel_id = m.id
                   WHERE p.kelas_id = :kelas_id 
                     AND p.tahun_ajaran = :tahun_ajaran 
                     AND p.semester = :semester
                     AND p.tenant_id = :tenant_id
                     AND p.deleted_at IS NULL
                     AND m.deleted_at IS NULL
                   ORDER BY m.nama_mapel ASC";
        $stmtMapel = $db->prepare($qMapel);
        $stmtMapel->execute([
            'kelas_id' => $kelasId,
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $semester,
            'tenant_id' => $tenantId
        ]);
        $subjects = $stmtMapel->fetchAll(PDO::FETCH_ASSOC);

        // 2. Get students
        $qSiswa = "SELECT s.id, s.nama_lengkap, s.nisn, s.nis, s.agama
                   FROM siswa s
                   WHERE s.id_kelas = :kelas_id
                     AND s.tenant_id = :tenant_id
                     AND s.status = 'Aktif'
                     AND s.deleted_at IS NULL
                   ORDER BY s.nama_lengkap ASC";
        $stmtSiswa = $db->prepare($qSiswa);
        $stmtSiswa->execute([
            'kelas_id' => $kelasId,
            'tenant_id' => $tenantId
        ]);
        $students = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

        // 3. Get existing grades matrix
        $qGrades = "SELECT siswa_id, mapel_id, nilai_akhir 
                    FROM detail_nilai_rapor
                    WHERE kelas_id = :kelas_id
                      AND tahun_ajaran = :tahun_ajaran
                      AND semester = :semester
                      AND tenant_id = :tenant_id
                      AND deleted_at IS NULL";
        $stmtGrades = $db->prepare($qGrades);
        $stmtGrades->execute([
            'kelas_id' => $kelasId,
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $semester,
            'tenant_id' => $tenantId
        ]);
        $gradesList = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

        $gradesMatrix = [];
        foreach ($gradesList as $row) {
            $gradesMatrix[$row['siswa_id']][$row['mapel_id']] = $row['nilai_akhir'];
        }

        // Set response headers
        $cleanKelasName = str_replace(' ', '_', $kelasName);
        $cleanTahun = str_replace('/', '-', $tahunAjaran);
        $filename = "format_nilai_{$cleanKelasName}_{$cleanTahun}_{$semester}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');

        // Build header line: Siswa ID, NISN, Nama Siswa, Subject 1 [ID], Subject 2 [ID]...
        $header = ['Siswa ID', 'NISN', 'Nama Siswa'];
        foreach ($subjects as $sub) {
            $header[] = $sub['nama_mapel'] . " [" . $sub['mapel_id'] . "]";
        }
        fputcsv($output, $header);

        // Build rows
        foreach ($students as $stu) {
            $nisnVal = $stu['nisn'] ?: $stu['nis'] ?: '-';
            if ($nisnVal !== '-' && strpos($nisnVal, "'") !== 0) {
                $nisnVal = "'" . $nisnVal;
            }
            $row = [
                $stu['id'],
                $nisnVal,
                $stu['nama_lengkap']
            ];
            foreach ($subjects as $sub) {
                if ($this->isReligionSubjectMismatch($stu['agama'] ?? null, $sub['nama_mapel'])) {
                    $row[] = 'N/A';
                } else {
                    $val = $gradesMatrix[$stu['id']][$sub['mapel_id']] ?? '';
                    $row[] = $val !== '' ? $val : '';
                }
            }
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * POST /api/v1/nilai-rapor/import
     * Upload and parse CSV to upsert grades.
     */
    public function import(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed.'], 405);
            return;
        }

        $kelasId = $_POST['kelas_id'] ?? '';
        $tahunAjaran = $_POST['tahun_ajaran'] ?? '';
        $semester = $_POST['semester'] ?? '';

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            $this->jsonResponse(['error' => 'Parameter kelas_id, tahun_ajaran, dan semester wajib disertakan.'], 400);
            return;
        }

        // Resolve tenant_id
        $db = \App\Config\Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        if (!$tenantId && !empty($_POST['tenant_id'])) {
            $tenantId = $_POST['tenant_id'];
        }
        if (!$tenantId && !empty($kelasId)) {
            $stmtKelasTenant = $db->prepare("SELECT tenant_id FROM kelas WHERE id = :kelas_id LIMIT 1");
            $stmtKelasTenant->execute(['kelas_id' => $kelasId]);
            $tenantId = $stmtKelasTenant->fetchColumn() ?: null;
        }

        if (!$tenantId) {
            $this->jsonResponse(['error' => 'Tenant ID tidak terdeteksi.'], 400);
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'File tidak terunggah atau terjadi kesalahan upload.'], 400);
            return;
        }

        $fileTmp = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'csv') {
            $this->jsonResponse(['error' => 'Format file tidak didukung. Mohon gunakan file CSV (.csv).'], 400);
            return;
        }

        $handle = fopen($fileTmp, 'r');
        if (!$handle) {
            $this->jsonResponse(['error' => 'Gagal membuka file CSV.'], 500);
            return;
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        $delimiter = ',';
        if (strpos($firstLine, ';') !== false) {
            $delimiter = ';';
        }
        rewind($handle);

        $header = fgetcsv($handle, 1000, $delimiter);
        if (!$header) {
            fclose($handle);
            $this->jsonResponse(['error' => 'File CSV kosong atau tidak valid.'], 400);
            return;
        }

        // Remove UTF-8 BOM if present on first element
        if (isset($header[0])) {
            $header[0] = preg_replace('/^[\x{FEFF}\x{200B}]+/u', '', $header[0]);
        }

        // Map column indices of subjects to their mapel_id
        $mapelCols = []; // index => mapel_id
        foreach ($header as $idx => $colName) {
            if ($idx < 3) {
                continue; // Skip Siswa ID, NISN, Nama Siswa
            }
            
            // Extract mapel_id from header (format: "Math [12]" or "Math [12")
            if (preg_match('/\[([0-9]+)\]/', $colName, $matches)) {
                $mapelCols[$idx] = (int)$matches[1];
            }
        }

        if (empty($mapelCols)) {
            fclose($handle);
            $this->jsonResponse(['error' => 'Tidak ditemukan kolom mata pelajaran yang valid dalam file CSV.'], 400);
            return;
        }

        $db = \App\Config\Database::getConnection();

        // Fetch student religion mapping for validation
        $qSiswaList = "SELECT id, agama FROM siswa WHERE id_kelas = :kelas_id AND tenant_id = :tenant_id AND status = 'Aktif' AND deleted_at IS NULL";
        $stmtSiswaList = $db->prepare($qSiswaList);
        $stmtSiswaList->execute(['kelas_id' => $kelasId, 'tenant_id' => $tenantId]);
        $studentsMap = [];
        foreach ($stmtSiswaList->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $studentsMap[$s['id']] = $s['agama'] ?? null;
        }

        // Fetch subject names mapping for validation
        $qMapelNames = "SELECT DISTINCT p.mapel_id, m.nama_mapel 
                        FROM pemetaan_mapel p
                        JOIN mata_pelajaran m ON p.mapel_id = m.id
                        WHERE p.kelas_id = :kelas_id 
                          AND p.tahun_ajaran = :tahun_ajaran 
                          AND p.semester = :semester
                          AND p.tenant_id = :tenant_id
                          AND p.deleted_at IS NULL
                          AND m.deleted_at IS NULL";
        $stmtMapelNames = $db->prepare($qMapelNames);
        $stmtMapelNames->execute([
            'kelas_id' => $kelasId,
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $semester,
            'tenant_id' => $tenantId
        ]);
        $subjectsMap = [];
        foreach ($stmtMapelNames->fetchAll(PDO::FETCH_ASSOC) as $sub) {
            $subjectsMap[$sub['mapel_id']] = $sub['nama_mapel'];
        }

        $db->beginTransaction();
        $rowCount = 0;
        $successCount = 0;
        try {
            $stmtUpsert = $db->prepare("
                INSERT INTO detail_nilai_rapor 
                    (tenant_id, siswa_id, kelas_id, tahun_ajaran, semester, mapel_id, nilai_akhir)
                VALUES 
                    (:tenant_id, :siswa_id, :kelas_id, :tahun_ajaran, :semester, :mapel_id, :nilai_akhir)
                ON DUPLICATE KEY UPDATE 
                    nilai_akhir = VALUES(nilai_akhir), 
                    updated_at = NOW()
            ");

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $rowCount++;
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }

                $siswaId = trim($row[0] ?? '');
                if (empty($siswaId)) {
                    continue; // Skip row if no student ID is found
                }

                // Loop through subject columns and insert values
                foreach ($mapelCols as $idx => $mapelId) {
                    $rawVal = isset($row[$idx]) ? trim($row[$idx]) : '';
                    if ($rawVal === 'N/A' || $rawVal === 'n/a' || $rawVal === '') {
                        continue; // Skip N/A or empty values
                    }

                    // Check for religion mismatch
                    $studentReligion = $studentsMap[$siswaId] ?? null;
                    $mapelName = $subjectsMap[$mapelId] ?? '';
                    if ($this->isReligionSubjectMismatch($studentReligion, $mapelName)) {
                        continue; // Skip mismatched religion subjects
                    }

                    $val = (float)$rawVal;

                    $stmtUpsert->execute([
                        'tenant_id' => $tenantId,
                        'siswa_id' => $siswaId,
                        'kelas_id' => $kelasId,
                        'tahun_ajaran' => $tahunAjaran,
                        'semester' => $semester,
                        'mapel_id' => $mapelId,
                        'nilai_akhir' => $val
                    ]);
                }
                $successCount++;
            }

            fclose($handle);
            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => "Berhasil mengimpor nilai rapor untuk {$successCount} siswa."]);
        } catch (\Throwable $e) {
            fclose($handle);
            $db->rollBack();
            error_log("Failed importing grades: " . $e->getMessage());
        }
    }

    private function isReligionSubjectMismatch(?string $studentReligion, string $subjectName): bool {
        $subjectNameLower = strtolower($subjectName);
        if (strpos($subjectNameLower, 'agama') === false && strpos($subjectNameLower, 'keagamaan') === false) {
            return false;
        }

        $religions = [
            'islam' => ['islam'],
            'kristen' => ['kristen', 'protestan'],
            'katolik' => ['katolik'],
            'hindu' => ['hindu'],
            'buddha' => ['buddha', 'budha'],
            'konghucu' => ['khonghucu', 'konghucu']
        ];

        $subjectReligionKey = null;
        foreach ($religions as $key => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($subjectNameLower, $keyword) !== false) {
                    $subjectReligionKey = $key;
                    break 2;
                }
            }
        }

        if ($subjectReligionKey === null) {
            return false;
        }

        if (empty($studentReligion)) {
            return false;
        }
        
        $studentReligionLower = strtolower(trim($studentReligion));
        $studentReligionKey = null;
        foreach ($religions as $key => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($studentReligionLower, $keyword) !== false) {
                    $studentReligionKey = $key;
                    break 2;
                }
            }
        }

        return $studentReligionKey !== $subjectReligionKey;
    }
}
