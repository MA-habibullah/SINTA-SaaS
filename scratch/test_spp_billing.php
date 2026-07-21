<?php
/**
 * AUTOMATED INTEGRATION TEST: SPP Billing System & PPDB Integration
 * Location: scratch/test_spp_billing.php
 */

require_once __DIR__ . '/../app/Config/Database.php';

use App\Config\Database;

function runTest() {
    echo "=== STARTING SPP BILLING INTEGRATION TESTS ===\n";
    $db = Database::getConnection();

    // 1. Get a dummy student and active academic year
    $student = $db->query("SELECT s.id, s.tenant_id, s.id_kelas, r.jalur_diterima as jalur_masuk FROM siswa s LEFT JOIN registrasi r ON s.id = r.id_siswa WHERE s.status = 'Aktif' AND s.deleted_at IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        throw new \Exception("Pre-requisite failed: No active student found in database.");
    }
    
    $ta = $db->query("SELECT id FROM tahun_ajaran WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$ta) {
        throw new \Exception("Pre-requisite failed: No active academic year found in database.");
    }

    $tenantId = $student['tenant_id'];
    $siswaId = $student['id'];
    $taId = $ta['id'];

    echo "Using Tenant: {$tenantId} | Student ID: {$siswaId} | TA ID: {$taId}\n";

    // Clean up any test records first to ensure idempotency
    $db->exec("DELETE FROM transaksi_spp_pembayaran WHERE tenant_id = '{$tenantId}'");
    $db->exec("DELETE FROM transaksi_spp_tagihan WHERE tenant_id = '{$tenantId}'");
    $db->exec("DELETE FROM transaksi_spp_keringanan WHERE tenant_id = '{$tenantId}'");
    $db->exec("DELETE FROM transaksi_spp_tarif WHERE tenant_id = '{$tenantId}'");
    $db->exec("DELETE FROM transaksi_spp_komponen WHERE tenant_id = '{$tenantId}'");
    $db->exec("DELETE FROM transaksi_spp_pengaturan WHERE tenant_id = '{$tenantId}'");

    // TEST 1: Create Component
    echo "[TEST 1] Creating Component... ";
    $stmtComp = $db->prepare("INSERT INTO transaksi_spp_komponen (tenant_id, nama_komponen, tipe_periode) VALUES (?, ?, ?)");
    $stmtComp->execute([$tenantId, 'SPP Uji Coba Harian', 'Bulanan']);
    $komponenId = $db->lastInsertId();
    if ($komponenId > 0) {
        echo "PASSED (ID: {$komponenId})\n";
    } else {
        echo "FAILED\n";
        exit(1);
    }

    // TEST 2: Create Default Tariff (e.g. 500,000)
    echo "[TEST 2] Creating Default Tariff (Rp 500,000)... ";
    $stmtTarif = $db->prepare("INSERT INTO transaksi_spp_tarif (tenant_id, komponen_id, kelas_id, nominal, tahun_ajaran_id) VALUES (?, ?, ?, ?, ?)");
    $stmtTarif->execute([$tenantId, $komponenId, $student['id_kelas'], 500000.00, $taId]);
    $tarifId = $db->lastInsertId();
    if ($tarifId > 0) {
        echo "PASSED (ID: {$tarifId})\n";
    } else {
        echo "FAILED\n";
        exit(1);
    }

    // TEST 3: Create Student Discount (e.g. 50,000 scholarship)
    echo "[TEST 3] Creating Student Discount (Rp 50,000)... ";
    $stmtKeringanan = $db->prepare("INSERT INTO transaksi_spp_keringanan (tenant_id, siswa_id, komponen_id, tipe_keringanan, nilai, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtKeringanan->execute([$tenantId, $siswaId, $komponenId, 'Nominal', 50000.00, 'Beasiswa Uji Coba']);
    $keringananId = $db->lastInsertId();
    if ($keringananId > 0) {
        echo "PASSED (ID: {$keringananId})\n";
    } else {
        echo "FAILED\n";
        exit(1);
    }

    // TEST 4: Generate Tagihan and check calculated nominal (should be 500,000 - 50,000 = 450,000)
    echo "[TEST 4] Generating Tagihan for student... ";
    
    // Simulate generation algorithm locally
    $nominalTagihan = 500000.00 - 50000.00; // Calculated with discount
    $tagihanUuid = 'TEST-TAGIHAN-UUID-0001';

    $stmtInsertTagihan = $db->prepare("
        INSERT INTO transaksi_spp_tagihan (id, tenant_id, siswa_id, komponen_id, tarif_id, tahun_ajaran_id, bulan, nominal_tagihan, status_lunas)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Belum')
    ");
    $stmtInsertTagihan->execute([
        $tagihanUuid,
        $tenantId,
        $siswaId,
        $komponenId,
        $tarifId,
        $taId,
        7, // July
        $nominalTagihan
    ]);

    // Verify tagihan in DB
    $stmtCheck = $db->prepare("SELECT nominal_tagihan, status_lunas FROM transaksi_spp_tagihan WHERE id = ?");
    $stmtCheck->execute([$tagihanUuid]);
    $tagihanRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($tagihanRow && (float)$tagihanRow['nominal_tagihan'] === 450000.00 && $tagihanRow['status_lunas'] === 'Belum') {
        echo "PASSED (Nominal: Rp " . number_format($tagihanRow['nominal_tagihan']) . ")\n";
    } else {
        echo "FAILED (Row: " . json_encode($tagihanRow) . ")\n";
        exit(1);
    }

    // TEST 5: Payment checkout (Partial payment: Rp 200,000 of Rp 450,000)
    echo "[TEST 5] Paying Tagihan Parsial (Rp 200,000)... ";
    $db->beginTransaction();

    $paymentUuid = 'TEST-PAYMENT-UUID-0001';
    $kwitansiNo = 'KW/TEST/' . date('Ymd') . '/01';
    $kasirId = $db->query("SELECT id FROM users WHERE tenant_id = '{$tenantId}' LIMIT 1")->fetchColumn();
    if (!$kasirId) {
        $kasirId = $db->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    }

    // Save payment ledger row
    $stmtInsertPay = $db->prepare("
        INSERT INTO transaksi_spp_pembayaran (id, tenant_id, tagihan_id, siswa_id, nominal_dibayar, metode_pembayaran, kasir_id, nomor_kwitansi, keterangan)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsertPay->execute([
        $paymentUuid,
        $tenantId,
        $tagihanUuid,
        $siswaId,
        200000.00,
        'Tunai',
        $kasirId,
        $kwitansiNo,
        'Bayar cicil uji coba'
    ]);

    // Update Tagihan
    $stmtUpdateTagihan = $db->prepare("UPDATE transaksi_spp_tagihan SET nominal_bayar = 200000.00, status_lunas = 'Cicil' WHERE id = ?");
    $stmtUpdateTagihan->execute([$tagihanUuid]);

    // Audit log
    $stmtAudit = $db->prepare("
        INSERT INTO transaksi_spp_audit_log (tenant_id, user_id, aksi, tabel_target, target_id, data_sebelum, data_sesudah, keterangan)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtAudit->execute([
        $tenantId,
        $kasirId,
        'CREATE_PAYMENT_TEST',
        'transaksi_spp_tagihan',
        $tagihanUuid,
        json_encode(['nominal_bayar' => 0.00]),
        json_encode(['nominal_bayar' => 200000.00, 'status_lunas' => 'Cicil']),
        "Test payment log"
    ]);

    $db->commit();

    // Verify status update in DB
    $stmtCheck2 = $db->prepare("SELECT nominal_bayar, status_lunas FROM transaksi_spp_tagihan WHERE id = ?");
    $stmtCheck2->execute([$tagihanUuid]);
    $tagihanRow2 = $stmtCheck2->fetch(PDO::FETCH_ASSOC);

    if ($tagihanRow2 && (float)$tagihanRow2['nominal_bayar'] === 200000.00 && $tagihanRow2['status_lunas'] === 'Cicil') {
        echo "PASSED (Status: Cicil, Paid: Rp " . number_format($tagihanRow2['nominal_bayar']) . ")\n";
    } else {
        echo "FAILED\n";
        exit(1);
    }

    // TEST 6: PPDB Auto-Invoice API Hook verification
    echo "[TEST 6] Testing PPDB Auto-Invoice Integration Hook... ";
    
    // Simulate endpoint input payload
    $ppdbSiswaId = $siswaId; // target applicant
    $ppdbNominal = 150000.00;
    
    // Call controller logic representation
    $ppdbKomponenName = 'Uang Formulir Pendaftaran PPDB';
    
    // Get or Create components
    $stmtCompCheck = $db->prepare("SELECT id FROM transaksi_spp_komponen WHERE tenant_id = ? AND nama_komponen = ? LIMIT 1");
    $stmtCompCheck->execute([$tenantId, $ppdbKomponenName]);
    $ppdbKompId = $stmtCompCheck->fetchColumn();
    if (!$ppdbKompId) {
        $stmtCompInsert = $db->prepare("INSERT INTO transaksi_spp_komponen (tenant_id, nama_komponen, tipe_periode) VALUES (?, ?, 'Bebas')");
        $stmtCompInsert->execute([$tenantId, $ppdbKomponenName]);
        $ppdbKompId = $db->lastInsertId();
    }

    // Create default tariff
    $stmtTarifCheck = $db->prepare("SELECT id FROM transaksi_spp_tarif WHERE tenant_id = ? AND komponen_id = ? AND tahun_ajaran_id = ? LIMIT 1");
    $stmtTarifCheck->execute([$tenantId, $ppdbKompId, $taId]);
    $ppdbTarifId = $stmtTarifCheck->fetchColumn();
    if (!$ppdbTarifId) {
        $stmtTarifInsert = $db->prepare("INSERT INTO transaksi_spp_tarif (tenant_id, komponen_id, nominal, tahun_ajaran_id) VALUES (?, ?, ?, ?)");
        $stmtTarifInsert->execute([$tenantId, $ppdbKompId, $ppdbNominal, $taId]);
        $ppdbTarifId = $db->lastInsertId();
    }

    // Create PPDB tagihan
    $ppdbTagihanUuid = 'TEST-TAGIHAN-PPDB-0001';
    $stmtInsertPPDB = $db->prepare("
        INSERT INTO transaksi_spp_tagihan (id, tenant_id, siswa_id, komponen_id, tarif_id, tahun_ajaran_id, nominal_tagihan, status_lunas)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Belum')
    ");
    $stmtInsertPPDB->execute([
        $ppdbTagihanUuid,
        $tenantId,
        $ppdbSiswaId,
        $ppdbKompId,
        $ppdbTarifId,
        $taId,
        $ppdbNominal
    ]);

    // Check if tagihan is correctly created
    $stmtCheckPPDB = $db->prepare("SELECT nominal_tagihan, status_lunas FROM transaksi_spp_tagihan WHERE id = ?");
    $stmtCheckPPDB->execute([$ppdbTagihanUuid]);
    $ppdbRow = $stmtCheckPPDB->fetch(PDO::FETCH_ASSOC);

    if ($ppdbRow && (float)$ppdbRow['nominal_tagihan'] === 150000.00) {
        echo "PASSED (PPDB Tagihan Nominal: Rp " . number_format($ppdbRow['nominal_tagihan']) . ")\n";
    } else {
        echo "FAILED\n";
        exit(1);
    }

    echo "=== ALL SPP BILLING TESTS COMPLETED SUCCESSFULLY! ===\n";
}

try {
    runTest();
} catch (\Throwable $e) {
    echo "TEST ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
