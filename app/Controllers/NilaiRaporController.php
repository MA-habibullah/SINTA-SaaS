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
                   WHERE s.tenant_id = :tenant_id
                     AND (s.status = 'Aktif' OR s.status = 'Lulus' OR s.status = 'Pindah' OR s.status = 'Drop Out' OR s.status = 'Keluar')
                     AND s.deleted_at IS NULL
                     AND (
                         (
                             s.id_kelas = :kelas_id1
                             AND :tahun_ajaran3 >= COALESCE(
                                 (SELECT MAX(tahun_ajaran) FROM riwayat_kenaikan_kelas WHERE siswa_id = s.id),
                                 (SELECT tahun_ajaran FROM tahun_ajaran WHERE id = s.id_tahun_ajaran LIMIT 1)
                             )
                         )
                         OR s.id IN (
                             SELECT siswa_id FROM detail_nilai_rapor 
                             WHERE kelas_id = :kelas_id2 
                               AND tahun_ajaran = :tahun_ajaran1 
                               AND semester = :semester 
                               AND deleted_at IS NULL
                         )
                         OR s.id IN (
                             SELECT siswa_id FROM riwayat_kenaikan_kelas 
                             WHERE id_kelas_asal = :kelas_id3 
                               AND :tahun_ajaran2 = CONCAT(CAST(LEFT(tahun_ajaran, 4) AS UNSIGNED) - 1, '/', CAST(RIGHT(tahun_ajaran, 4) AS UNSIGNED) - 1)
                         )
                     )
                   ORDER BY s.nama_lengkap ASC";
        $stmtSiswa = $db->prepare($qSiswa);
        $stmtSiswa->execute([
            'kelas_id1' => $kelasId,
            'kelas_id2' => $kelasId,
            'kelas_id3' => $kelasId,
            'tahun_ajaran1' => $tahunAjaran,
            'tahun_ajaran2' => $tahunAjaran,
            'tahun_ajaran3' => $tahunAjaran,
            'semester' => $semester,
            'tenant_id' => $tenantId
        ]);
        $students = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

        // 3. Get active curriculum for the class
        $qActive = "SELECT r.nama_kurikulum, r.tipe_penilaian, kk.is_locked 
                    FROM kelas_kurikulum kk
                    JOIN ref_kurikulum r ON kk.kurikulum_id = r.id
                    WHERE kk.kelas_id = :kelas_id AND kk.tahun_ajaran = :tahun_ajaran AND kk.tenant_id = :tenant_id
                    LIMIT 1";
        $stmtActive = $db->prepare($qActive);
        $stmtActive->execute([
            'kelas_id' => $kelasId,
            'tahun_ajaran' => $tahunAjaran,
            'tenant_id' => $tenantId
        ]);
        $activeKur = $stmtActive->fetch(PDO::FETCH_ASSOC) ?: [
            'nama_kurikulum' => 'Kurikulum 2013 (K-13)',
            'tipe_penilaian' => 'kompleks',
            'is_locked' => 0
        ];

        // 4. Get existing grades
        $qGrades = "SELECT siswa_id, mapel_id, nilai_akhir, kkm, nilai_detail_json 
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

        // Structure grades as [siswa_id][mapel_id] => { nilai_akhir, kkm, detail }
        $gradesMatrix = [];
        foreach ($gradesList as $row) {
            $sId = $row['siswa_id'];
            $mId = $row['mapel_id'];
            $val = $row['nilai_akhir'];
            $kkm = $row['kkm'];
            
            $detail = [];
            if (!empty($row['nilai_detail_json'])) {
                $detail = json_decode($row['nilai_detail_json'], true) ?: [];
            }
            
            if (!isset($gradesMatrix[$sId])) {
                $gradesMatrix[$sId] = [];
            }
            $gradesMatrix[$sId][$mId] = [
                'nilai_akhir' => $val !== null ? (float)$val : null,
                'kkm' => $kkm !== null ? (float)$kkm : null,
                'detail' => $detail
            ];
        }

        // 5. Get existing K-13 attitude grades if applicable
        $sikapList = new \stdClass(); // Return as empty object if none
        if ($activeKur['tipe_penilaian'] === 'kompleks') {
            $qSikap = "SELECT siswa_id, predikat_spiritual, deskripsi_spiritual, predikat_sosial, deskripsi_sosial
                       FROM nilai_sikap_k13
                       WHERE tahun_ajaran = :tahun_ajaran
                         AND semester = :semester
                         AND tenant_id = :tenant_id";
            $stmtSikap = $db->prepare($qSikap);
            $stmtSikap->execute([
                'tahun_ajaran' => $tahunAjaran,
                'semester' => $semester,
                'tenant_id' => $tenantId
            ]);
            $sikapRows = $stmtSikap->fetchAll(PDO::FETCH_ASSOC);
            
            $sikapArr = [];
            foreach ($sikapRows as $row) {
                $sikapArr[$row['siswa_id']] = [
                    'predikat_spiritual' => $row['predikat_spiritual'] ?: '',
                    'deskripsi_spiritual' => $row['deskripsi_spiritual'] ?: '',
                    'predikat_sosial' => $row['predikat_sosial'] ?: '',
                    'deskripsi_sosial' => $row['deskripsi_sosial'] ?: ''
                ];
            }
            if (!empty($sikapArr)) {
                $sikapList = $sikapArr;
            }
        }

        $this->jsonResponse([
            'subjects' => $subjects,
            'students' => $students,
            'grades' => $gradesMatrix,
            'kurikulum' => $activeKur,
            'sikap_k13' => $sikapList,
            'is_rombel_locked' => ($activeKur['is_locked'] ?? 0) == 1
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

        // Periksa apakah input nilai terkunci
        $stmtLock = $db->prepare("SELECT is_locked_nilai FROM kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ?");
        $stmtLock->execute([$tenantId, $tahunAjaran, $semester]);
        if ($stmtLock->fetchColumn()) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Gagal menyimpan. Input Nilai Rapor pada Tahun Ajaran & Semester ini telah dikunci oleh administrator.'], 403);
            return;
        }

        $stmtLockKelas = $db->prepare("SELECT is_locked FROM kelas_kurikulum WHERE tenant_id = ? AND kelas_id = ? AND tahun_ajaran = ? LIMIT 1");
        $stmtLockKelas->execute([$tenantId, $kelasId, $tahunAjaran]);
        if ($stmtLockKelas->fetchColumn() == 1) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Gagal menyimpan. Input Nilai Rapor untuk kelas ini pada tahun ajaran terkait telah dikunci.'], 403);
            return;
        }

        // Ambil nilai lama untuk audit log
        $qOld = "SELECT siswa_id, mapel_id, nilai_akhir, kkm, nilai_detail_json FROM detail_nilai_rapor 
                 WHERE tenant_id = :tenant_id AND kelas_id = :kelas_id AND tahun_ajaran = :tahun_ajaran AND semester = :semester AND deleted_at IS NULL";
        $stmtOld = $db->prepare($qOld);
        $stmtOld->execute([
            'tenant_id' => $tenantId,
            'kelas_id' => $kelasId,
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $semester
        ]);
        $oldGrades = [];
        foreach ($stmtOld->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oldGrades[$row['siswa_id'] . '_' . $row['mapel_id']] = $row;
        }

        $db->beginTransaction();
        try {
            $stmtUpsert = $db->prepare("
                INSERT INTO detail_nilai_rapor 
                    (tenant_id, siswa_id, kelas_id, tahun_ajaran, semester, mapel_id, nilai_akhir, kkm, nilai_detail_json)
                VALUES 
                    (:tenant_id, :siswa_id, :kelas_id, :tahun_ajaran, :semester, :mapel_id, :nilai_akhir, :kkm, :nilai_detail_json)
                ON DUPLICATE KEY UPDATE 
                    kelas_id = VALUES(kelas_id),
                    nilai_akhir = VALUES(nilai_akhir), 
                    kkm = VALUES(kkm),
                    nilai_detail_json = VALUES(nilai_detail_json),
                    updated_at = NOW(),
                    deleted_at = NULL
            ");

            foreach ($grades as $entry) {
                $sId = $entry['siswa_id'] ?? '';
                $mId = $entry['mapel_id'] ?? '';
                $val = isset($entry['nilai_akhir']) && $entry['nilai_akhir'] !== '' ? $entry['nilai_akhir'] : null;
                $kkm = isset($entry['kkm']) && $entry['kkm'] !== '' ? $entry['kkm'] : null;
                
                $detailVal = null;
                if (!empty($entry['detail'])) {
                    $detailVal = json_encode($entry['detail']);
                }

                if (empty($sId) || empty($mId)) {
                    continue;
                }

                // Check for student-subject religion mismatch
                $studentReligion = $studentsMap[$sId] ?? null;
                $mapelName = $subjectsMap[$mId] ?? '';
                if ($this->isReligionSubjectMismatch($studentReligion, $mapelName)) {
                    continue; // Skip saving grade if there is a religion mismatch
                }

                // Audit logging
                $oldData = $oldGrades[$sId . '_' . $mId] ?? null;
                $oldVal = $oldData ? $oldData['nilai_akhir'] : null;
                $oldKkm = $oldData ? $oldData['kkm'] : null;
                $oldJson = $oldData ? $oldData['nilai_detail_json'] : null;

                $action = $oldData ? 'UPDATE' : 'INSERT';
                $isChanged = ($action === 'INSERT') || ($oldVal != $val || $oldKkm != $kkm || $oldJson != $detailVal);

                if ($isChanged) {
                    $stmtLog = $db->prepare("
                        INSERT INTO log_nilai_rapor 
                            (tenant_id, user_id, siswa_id, mapel_id, semester, tahun_ajaran, nilai_lama_json, nilai_baru_json, action)
                        VALUES 
                            (:tenant_id, :user_id, :siswa_id, :mapel_id, :semester, :tahun_ajaran, :nilai_lama_json, :nilai_baru_json, :action)
                    ");
                    $stmtLog->execute([
                        'tenant_id' => $tenantId,
                        'user_id' => $_SESSION['user_id'] ?? 'SYSTEM',
                        'siswa_id' => $sId,
                        'mapel_id' => $mId,
                        'semester' => $semester,
                        'tahun_ajaran' => $tahunAjaran,
                        'nilai_lama_json' => $oldData ? json_encode($oldData) : null,
                        'nilai_baru_json' => json_encode([
                            'nilai_akhir' => $val,
                            'kkm' => $kkm,
                            'nilai_detail_json' => $detailVal
                        ]),
                        'action' => $action
                    ]);
                }

                $stmtUpsert->execute([
                    'tenant_id' => $tenantId,
                    'siswa_id' => $sId,
                    'kelas_id' => $kelasId,
                    'tahun_ajaran' => $tahunAjaran,
                    'semester' => $semester,
                    'mapel_id' => $mId,
                    'nilai_akhir' => $val,
                    'kkm' => $kkm,
                    'nilai_detail_json' => $detailVal
                ]);
            }

            // Save K-13 attitude grades if provided
            $sikapPayload = $input['sikap_k13'] ?? [];
            if (!empty($sikapPayload) && (is_array($sikapPayload) || is_object($sikapPayload))) {
                $stmtUpsertSikap = $db->prepare("
                    INSERT INTO nilai_sikap_k13
                        (tenant_id, siswa_id, tahun_ajaran, semester, predikat_spiritual, deskripsi_spiritual, predikat_sosial, deskripsi_sosial)
                    VALUES
                        (:tenant_id, :siswa_id, :tahun_ajaran, :semester, :pred_spiritual, :desk_spiritual, :pred_sosial, :desk_sosial)
                    ON DUPLICATE KEY UPDATE
                        predikat_spiritual = VALUES(predikat_spiritual),
                        deskripsi_spiritual = VALUES(deskripsi_spiritual),
                        predikat_sosial = VALUES(predikat_sosial),
                        deskripsi_sosial = VALUES(deskripsi_sosial)
                ");
                foreach ($sikapPayload as $sId => $sData) {
                    $stmtUpsertSikap->execute([
                        'tenant_id' => $tenantId,
                        'siswa_id' => $sId,
                        'tahun_ajaran' => $tahunAjaran,
                        'semester' => $semester,
                        'pred_spiritual' => $sData['predikat_spiritual'] ?? null,
                        'desk_spiritual' => $sData['deskripsi_spiritual'] ?? null,
                        'pred_sosial' => $sData['predikat_sosial'] ?? null,
                        'desk_sosial' => $sData['deskripsi_sosial'] ?? null
                    ]);
                }
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
     * Download XLSX template for grades input.
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
        // 3. Get existing grades matrix
        $qGrades = "SELECT siswa_id, mapel_id, nilai_akhir, kkm, nilai_detail_json 
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
            $detail = json_decode($row['nilai_detail_json'] ?? '', true) ?: [];
            $tertinggi = $detail['capaian_tertinggi'] ?? $detail['kognitif_deskripsi'] ?? $detail['pengetahuan_deskripsi'] ?? '';
            $terendah = $detail['capaian_terendah'] ?? $detail['psikomotorik_deskripsi'] ?? $detail['keterampilan_deskripsi'] ?? '';

            $gradesMatrix[$row['siswa_id']][$row['mapel_id']] = [
                'nilai_akhir' => $row['nilai_akhir'],
                'kkm' => $row['kkm'] ?? ($detail['kkm'] ?? ''),
                'tertinggi' => $tertinggi,
                'terendah' => $terendah
            ];
        }

        // Build excel data matrix
        $excelData = [];

        // Build header line: Siswa ID, NISN, Nama Siswa, [Mapel] - KKTP, [Mapel] - Nilai Akhir...
        $header = ['Siswa ID', 'NISN', 'Nama Siswa'];
        foreach ($subjects as $sub) {
            $mid = $sub['mapel_id'];
            $mname = $sub['nama_mapel'];
            $header[] = "{$mname} - KKTP [{$mid}]";
            $header[] = "{$mname} - Nilai Akhir [{$mid}]";
            $header[] = "{$mname} - Capaian Tertinggi [{$mid}]";
            $header[] = "{$mname} - Capaian Terendah [{$mid}]";
        }
        $excelData[] = $header;

        // Build rows
        foreach ($students as $stu) {
            $nisnVal = $stu['nisn'] ?: $stu['nis'] ?: '-';
            $row = [
                (string)$stu['id'],
                (string)$nisnVal,
                (string)$stu['nama_lengkap']
            ];
            foreach ($subjects as $sub) {
                $mid = $sub['mapel_id'];
                if ($this->isReligionSubjectMismatch($stu['agama'] ?? null, $sub['nama_mapel'])) {
                    $row[] = 'N/A';
                    $row[] = 'N/A';
                    $row[] = 'N/A';
                    $row[] = 'N/A';
                } else {
                    $g = $gradesMatrix[$stu['id']][$mid] ?? null;
                    $kkm = $g['kkm'] ?? '';
                    $val = $g['nilai_akhir'] ?? '';
                    $tertinggi = $g['tertinggi'] ?? '';
                    $terendah = $g['terendah'] ?? '';

                    $row[] = $kkm !== '' ? (float)$kkm : '';
                    $row[] = $val !== '' ? (float)$val : '';
                    $row[] = (string)$tertinggi;
                    $row[] = (string)$terendah;
                }
            }
            $excelData[] = $row;
        }

        $cleanKelasName = str_replace(' ', '_', $kelasName);
        $cleanTahun = str_replace('/', '-', $tahunAjaran);
        $filename = "format_nilai_{$cleanKelasName}_{$cleanTahun}_{$semester}.xlsx";

        \Shuchkin\SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
        exit;
    }

    /**
     * POST /api/v1/nilai-rapor/import
     * Upload and parse XLSX to upsert grades.
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

        if ($fileExt !== 'xlsx') {
            $this->jsonResponse(['error' => 'Format file tidak didukung. Mohon gunakan file Excel (.xlsx).'], 400);
            return;
        }

        $xlsx = \Shuchkin\SimpleXLSX::parse($fileTmp);
        if (!$xlsx) {
            $this->jsonResponse(['error' => 'Gagal membaca berkas Excel (.xlsx): ' . \Shuchkin\SimpleXLSX::parseError()], 400);
            return;
        }

        $rows = $xlsx->rows();
        if (empty($rows)) {
            $this->jsonResponse(['error' => 'Berkas Excel kosong atau tidak valid.'], 400);
            return;
        }

        $header = array_shift($rows);
        if (isset($header[0])) {
            $header[0] = preg_replace('/^[\x{FEFF}\x{200B}]+/u', '', $header[0]);
        }

        // Map column indices of subjects to their mapel_id & column type
        $mapelCols = []; // index => ['mapel_id' => ..., 'type' => 'nilai_akhir'|'kkm'|'tertinggi'|'terendah']
        foreach ($header as $idx => $colName) {
            if ($idx < 3) {
                continue; // Skip Siswa ID, NISN, Nama Siswa
            }
            
            // Extract mapel_id from header (format: "Math - KKTP [12]" or "Math [12]")
            if (preg_match('/\[([0-9]+)\]/', $colName, $matches)) {
                $mapelId = (int)$matches[1];
                $type = 'nilai_akhir'; // default
                if (strpos($colName, '- KKTP') !== false || strpos($colName, '- KKM') !== false) {
                    $type = 'kkm';
                } elseif (strpos($colName, '- Capaian Tertinggi') !== false) {
                    $type = 'tertinggi';
                } elseif (strpos($colName, '- Capaian Terendah') !== false) {
                    $type = 'terendah';
                }
                $mapelCols[$idx] = [
                    'mapel_id' => $mapelId,
                    'type' => $type
                ];
            }
        }

        if (empty($mapelCols)) {
            $this->jsonResponse(['error' => 'Tidak ditemukan kolom mata pelajaran yang valid dalam file Excel.'], 400);
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
        $rowCount = 1; // Since header was row 1
        $successCount = 0;
        try {
            $stmtUpsert = $db->prepare("
                INSERT INTO detail_nilai_rapor 
                    (tenant_id, siswa_id, kelas_id, tahun_ajaran, semester, mapel_id, nilai_akhir, kkm, nilai_detail_json)
                VALUES 
                    (:tenant_id, :siswa_id, :kelas_id, :tahun_ajaran, :semester, :mapel_id, :nilai_akhir, :kkm, :nilai_detail_json)
                ON DUPLICATE KEY UPDATE 
                    nilai_akhir = COALESCE(VALUES(nilai_akhir), detail_nilai_rapor.nilai_akhir),
                    kkm = COALESCE(VALUES(kkm), detail_nilai_rapor.kkm),
                    nilai_detail_json = COALESCE(VALUES(nilai_detail_json), detail_nilai_rapor.nilai_detail_json),
                    updated_at = NOW()
            ");

            foreach ($rows as $row) {
                $rowCount++;
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }

                $siswaId = trim((string)($row[0] ?? ''));
                if (empty($siswaId)) {
                    continue; // Skip row if no student ID is found
                }

                $studentGrades = [];
                foreach ($mapelCols as $idx => $colInfo) {
                    $mapelId = $colInfo['mapel_id'];
                    $type = $colInfo['type'];
                    $rawVal = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                    if ($rawVal === 'N/A' || $rawVal === 'n/a' || $rawVal === '') {
                        continue;
                    }

                    $studentGrades[$mapelId][$type] = $rawVal;
                }

                foreach ($studentGrades as $mapelId => $data) {
                    // Check for religion mismatch
                    $studentReligion = $studentsMap[$siswaId] ?? null;
                    $mapelName = $subjectsMap[$mapelId] ?? '';
                    if ($this->isReligionSubjectMismatch($studentReligion, $mapelName)) {
                        continue;
                    }

                    $val = isset($data['nilai_akhir']) && $data['nilai_akhir'] !== '' ? (float)$data['nilai_akhir'] : null;
                    $kkm = isset($data['kkm']) && $data['kkm'] !== '' ? (float)$data['kkm'] : null;

                    // Fetch existing JSON detail to merge
                    $stmtExist = $db->prepare("SELECT nilai_detail_json, kkm, nilai_akhir FROM detail_nilai_rapor WHERE siswa_id = ? AND mapel_id = ? AND kelas_id = ? AND tahun_ajaran = ? AND semester = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1");
                    $stmtExist->execute([$siswaId, $mapelId, $kelasId, $tahunAjaran, $semester, $tenantId]);
                    $existRow = $stmtExist->fetch(PDO::FETCH_ASSOC);
                    $existDetail = $existRow ? (json_decode($existRow['nilai_detail_json'] ?? '', true) ?: []) : [];

                    if ($val === null && $existRow) {
                        $val = $existRow['nilai_akhir'];
                    }
                    if ($kkm === null && $existRow) {
                        $kkm = $existRow['kkm'];
                    }

                    if (isset($data['tertinggi'])) {
                        $existDetail['capaian_tertinggi'] = $data['tertinggi'];
                        $existDetail['kognitif_deskripsi'] = $data['tertinggi'];
                        $existDetail['pengetahuan_deskripsi'] = $data['tertinggi'];
                    }
                    if (isset($data['terendah'])) {
                        $existDetail['capaian_terendah'] = $data['terendah'];
                        $existDetail['psikomotorik_deskripsi'] = $data['terendah'];
                        $existDetail['keterampilan_deskripsi'] = $data['terendah'];
                    }
                    if ($kkm !== null) {
                        $existDetail['kkm'] = $kkm;
                    }

                    $detailJson = !empty($existDetail) ? json_encode($existDetail) : null;

                    $stmtUpsert->execute([
                        'tenant_id' => $tenantId,
                        'siswa_id' => $siswaId,
                        'kelas_id' => $kelasId,
                        'tahun_ajaran' => $tahunAjaran,
                        'semester' => $semester,
                        'mapel_id' => $mapelId,
                        'nilai_akhir' => $val,
                        'kkm' => $kkm,
                        'nilai_detail_json' => $detailJson
                    ]);
                }
                $successCount++;
            }

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => "Berhasil mengimpor nilai rapor untuk {$successCount} siswa."]);
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log("Failed importing grades: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat menyimpan data nilai: ' . $e->getMessage()], 500);
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

    public function deleteSiswaGradesApi(): void {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Method not allowed.");
            }

            $siswaId = $_POST['siswa_id'] ?? '';
            $kelasId = $_POST['kelas_id'] ?? '';
            $tahunAjaran = $_POST['tahun_ajaran'] ?? '';
            $semester = $_POST['semester'] ?? '';

            if (!$siswaId || !$kelasId || !$tahunAjaran || !$semester) {
                throw new \Exception("Parameter tidak lengkap.");
            }

            $role = $_SESSION['role_name'] ?? '';
            if ($role !== 'super_admin' && $role !== 'admin') {
                throw new \Exception("Anda tidak memiliki akses.");
            }

            $tenantId = \App\Core\SessionManager::getTenantId();
            if ($role === 'super_admin' && empty($tenantId)) {
                $tenantId = $_POST['tenant_id'] ?? '';
            }
            if (!$tenantId) {
                throw new \Exception("Sekolah belum dipilih.");
            }

            $db = \App\Config\Database::getConnection();

            // Periksa apakah input nilai terkunci
            $stmtLock = $db->prepare("SELECT is_locked_nilai FROM kunci_akademik WHERE tenant_id = ? AND tahun_ajaran = ? AND semester = ?");
            $stmtLock->execute([$tenantId, $tahunAjaran, $semester]);
            if ($stmtLock->fetchColumn()) {
                throw new \Exception("Gagal menghapus. Input Nilai Rapor pada Tahun Ajaran & Semester ini telah dikunci oleh administrator.");
            }

            $stmtLockKelas = $db->prepare("SELECT is_locked FROM kelas_kurikulum WHERE tenant_id = ? AND kelas_id = ? AND tahun_ajaran = ? LIMIT 1");
            $stmtLockKelas->execute([$tenantId, $kelasId, $tahunAjaran]);
            if ($stmtLockKelas->fetchColumn() == 1) {
                throw new \Exception("Gagal menghapus. Input Nilai Rapor untuk kelas ini pada tahun ajaran terkait telah dikunci.");
            }

            // Ambil data nilai lama sebelum di-soft-delete untuk log audit
            $stmtOld = $db->prepare("SELECT mapel_id, nilai_akhir, kkm, nilai_detail_json FROM detail_nilai_rapor WHERE siswa_id = ? AND kelas_id = ? AND tahun_ajaran = ? AND semester = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmtOld->execute([$siswaId, $kelasId, $tahunAjaran, $semester, $tenantId]);
            $oldDataList = $stmtOld->fetchAll(PDO::FETCH_ASSOC);

            $db->beginTransaction();
            try {
                foreach ($oldDataList as $old) {
                    $stmtLog = $db->prepare("
                        INSERT INTO log_nilai_rapor 
                            (tenant_id, user_id, siswa_id, mapel_id, semester, tahun_ajaran, nilai_lama_json, nilai_baru_json, action)
                        VALUES 
                            (:tenant_id, :user_id, :siswa_id, :mapel_id, :semester, :tahun_ajaran, :nilai_lama_json, NULL, 'DELETE')
                    ");
                    $stmtLog->execute([
                        'tenant_id' => $tenantId,
                        'user_id' => $_SESSION['user_id'] ?? 'SYSTEM',
                        'siswa_id' => $siswaId,
                        'mapel_id' => $old['mapel_id'],
                        'semester' => $semester,
                        'tahun_ajaran' => $tahunAjaran,
                        'nilai_lama_json' => json_encode($old)
                    ]);
                }

                $stmt = $db->prepare("UPDATE detail_nilai_rapor SET deleted_at = NOW() WHERE siswa_id = ? AND kelas_id = ? AND tahun_ajaran = ? AND semester = ? AND tenant_id = ? AND deleted_at IS NULL");
                $stmt->execute([$siswaId, $kelasId, $tahunAjaran, $semester, $tenantId]);

                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }

            // Jika nilai berhasil dihapus, student otomatis akan hilang dari grid jika mereka tidak terdaftar resmi di kelas tersebut (berdasarkan filter BukuIndukController / getGrid)
            echo json_encode([
                'success' => true,
                'message' => 'Nilai rapor untuk siswa tersebut pada filter ini berhasil dihapus.'
            ]);
        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function validateExcelImportApi(): void {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Method not allowed.");
            }

            $kelasId = $_POST['kelas_id'] ?? '';
            $tahunAjaran = $_POST['tahun_ajaran'] ?? '';
            $semester = $_POST['semester'] ?? '';

            if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
                throw new \Exception("Parameter tidak lengkap.");
            }

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
                throw new \Exception("Tenant ID tidak terdeteksi.");
            }

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception("File tidak terunggah atau terjadi kesalahan upload.");
            }

            $fileTmp = $_FILES['file']['tmp_name'];
            $xlsx = \Shuchkin\SimpleXLSX::parse($fileTmp);
            if (!$xlsx) {
                throw new \Exception("Gagal membaca berkas Excel: " . \Shuchkin\SimpleXLSX::parseError());
            }

            $rows = $xlsx->rows();
            if (empty($rows)) {
                throw new \Exception("Berkas Excel kosong.");
            }

            $header = array_shift($rows);
            if (isset($header[0])) {
                $header[0] = preg_replace('/^[\x{FEFF}\x{200B}]+/u', '', $header[0]);
            }

            $mapelCols = []; // index => ['mapel_id' => ..., 'type' => 'nilai_akhir'|'kkm'|'tertinggi'|'terendah']
            foreach ($header as $idx => $colName) {
                if ($idx < 3) continue;
                if (preg_match('/\[([0-9]+)\]/', $colName, $matches)) {
                    $mapelId = (int)$matches[1];
                    $type = 'nilai_akhir'; // default
                    if (strpos($colName, '- KKTP') !== false || strpos($colName, '- KKM') !== false) {
                        $type = 'kkm';
                    } elseif (strpos($colName, '- Capaian Tertinggi') !== false) {
                        $type = 'tertinggi';
                    } elseif (strpos($colName, '- Capaian Terendah') !== false) {
                        $type = 'terendah';
                    }
                    $mapelCols[$idx] = [
                        'mapel_id' => $mapelId,
                        'type' => $type
                    ];
                }
            }

            if (empty($mapelCols)) {
                throw new \Exception("Kolom mata pelajaran tidak ditemukan dalam file Excel.");
            }

            // Fetch student database info
            $qSiswaList = "SELECT id, nama_lengkap, nisn, nis, agama FROM siswa WHERE id_kelas = :kelas_id AND tenant_id = :tenant_id AND status = 'Aktif' AND deleted_at IS NULL";
            $stmtSiswaList = $db->prepare($qSiswaList);
            $stmtSiswaList->execute(['kelas_id' => $kelasId, 'tenant_id' => $tenantId]);
            $studentsMap = [];
            foreach ($stmtSiswaList->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $studentsMap[$s['id']] = $s;
            }

            // Fetch subject names
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

            $reportRows = [];
            $totalErrors = 0;
            $totalWarnings = 0;
            $totalValid = 0;

            foreach ($rows as $rowIdx => $row) {
                if (empty(array_filter($row))) {
                    continue; // skip empty rows
                }

                $siswaId = trim((string)($row[0] ?? ''));
                $nisnVal = trim((string)($row[1] ?? ''));
                $namaVal = trim((string)($row[2] ?? ''));

                if (empty($siswaId)) {
                    continue;
                }

                $dbSiswa = $studentsMap[$siswaId] ?? null;
                $rowErrors = [];
                $rowWarnings = [];

                if (!$dbSiswa) {
                    $rowErrors[] = "Siswa dengan ID '{$siswaId}' tidak terdaftar aktif di kelas ini.";
                } else {
                    // Check mismatch names / NISN if available
                    if ($dbSiswa['nisn'] && $nisnVal && $dbSiswa['nisn'] !== $nisnVal) {
                        $rowWarnings[] = "NISN di Excel ({$nisnVal}) tidak cocok dengan database ({$dbSiswa['nisn']}).";
                    }
                }

                $gradesChecked = [];
                foreach ($mapelCols as $idx => $colInfo) {
                    $mapelId = $colInfo['mapel_id'];
                    $type = $colInfo['type'];
                    $rawVal = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
                    $mapelName = $subjectsMap[$mapelId] ?? "Mapel ID: {$mapelId}";
                    
                    if ($rawVal === 'N/A' || $rawVal === 'n/a' || $rawVal === '') {
                        $gradesChecked[] = [
                            'mapel_name' => "{$mapelName} (" . strtoupper($type) . ")",
                            'value' => 'N/A',
                            'status' => 'info',
                            'msg' => 'Dilewati (Kosong/NA)'
                        ];
                        continue;
                    }

                    if ($type === 'nilai_akhir' || $type === 'kkm') {
                        if (!is_numeric($rawVal)) {
                            $rowErrors[] = "Nilai '{$rawVal}' pada {$type} mapel '{$mapelName}' bukan angka.";
                            $gradesChecked[] = [
                                'mapel_name' => "{$mapelName} (" . strtoupper($type) . ")",
                                'value' => $rawVal,
                                'status' => 'error',
                                'msg' => 'Bukan angka'
                            ];
                            continue;
                        }

                        $val = (float)$rawVal;
                        if ($val < 0 || $val > 100) {
                            $rowErrors[] = "Nilai {$val} pada {$type} mapel '{$mapelName}' di luar batas 0-100.";
                            $gradesChecked[] = [
                                'mapel_name' => "{$mapelName} (" . strtoupper($type) . ")",
                                'value' => $val,
                                'status' => 'error',
                                'msg' => 'Di luar batas 0-100'
                            ];
                            continue;
                        }
                    } else {
                        // Text description (tertinggi / terendah)
                        $gradesChecked[] = [
                            'mapel_name' => "{$mapelName} (" . ($type === 'tertinggi' ? 'Capaian Tertinggi' : 'Capaian Terendah') . ")",
                            'value' => mb_strimwidth($rawVal, 0, 30, '...'),
                            'status' => 'success',
                            'msg' => 'Deskripsi Valid'
                        ];
                    }

                    // Religion check
                    if ($dbSiswa) {
                        $studentReligion = $dbSiswa['agama'] ?? null;
                        if ($this->isReligionSubjectMismatch($studentReligion, $mapelName)) {
                            $rowWarnings[] = "Nilai '{$val}' pada '{$mapelName}' diabaikan karena agama siswa '{$studentReligion}' tidak cocok.";
                            $gradesChecked[] = [
                                'mapel_name' => $mapelName,
                                'value' => $val,
                                'status' => 'warning',
                                'msg' => 'Beda Agama (Diabaikan)'
                            ];
                            continue;
                        }
                    }

                    $gradesChecked[] = [
                        'mapel_name' => $mapelName,
                        'value' => $val,
                        'status' => 'valid',
                        'msg' => 'Valid'
                    ];
                }

                $hasError = count($rowErrors) > 0;
                $hasWarning = count($rowWarnings) > 0;

                if ($hasError) {
                    $totalErrors++;
                    $status = 'error';
                } elseif ($hasWarning) {
                    $totalWarnings++;
                    $status = 'warning';
                } else {
                    $totalValid++;
                    $status = 'valid';
                }

                $reportRows[] = [
                    'siswa_id' => $siswaId,
                    'nisn' => $nisnVal,
                    'nama_lengkap' => $namaVal ?: ($dbSiswa['nama_lengkap'] ?? 'Tidak Diketahui'),
                    'status' => $status,
                    'errors' => $rowErrors,
                    'warnings' => $rowWarnings,
                    'grades' => $gradesChecked
                ];
            }

            echo json_encode([
                'success' => true,
                'summary' => [
                    'total_rows' => count($reportRows),
                    'valid' => $totalValid,
                    'warning' => $totalWarnings,
                    'error' => $totalErrors
                ],
                'data' => $reportRows
            ]);
        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}
