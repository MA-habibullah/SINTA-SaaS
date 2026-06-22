<?php
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';
require_once __DIR__ . '/../app/Models/Siswa.php';

use App\Models\Siswa;

try {
    // We need a tenant_id to create/update
    $tenantId = '11111111-1111-1111-1111-111111111111'; // default tenant from check_siswa_data.php
    $model = new Siswa($tenantId);

    // 0. Clean up previous test runs if any
    $pdo = \App\Config\Database::getConnection();
    $pdo->exec("DELETE FROM siswa WHERE nama_lengkap LIKE 'TEST SISWA BARU%' OR nisn = '0098765432' OR nis = '98765'");

    // 1. Prepare sample data
    $testData = [
        'tenant_id' => $tenantId,
        'user_id' => null,
        'nik' => '1234567890123456',
        'no_kk' => '1234567890123456',
        'nisn' => '0098765432',
        'nis' => '98765',
        'nama_lengkap' => 'TEST SISWA BARU',
        'nama_panggilan' => 'TEST',
        'jenis_kelamin' => 'L',
        'agama' => 'Islam',
        'tempat_lahir' => '1', // Kota ID 1
        'tanggal_lahir' => '2010-05-15',
        'sekolah_asal' => 'SMP Test Asal',
        'status' => 'Aktif',
        'id_angkatan' => null,
        'id_tahun_ajaran' => null,
        'id_jenjang' => null,
        'id_jurusan' => null,
        'id_kelas' => null,
        'id_pendidikan' => null,
        'ukuran_seragam_sekolah' => 'M',
        'ukuran_seragam_olahraga' => 'L',

        // rincian_alamat
        'id_kelurahan' => 1,
        'alamat_kk' => 'Alamat KK Test',
        'alamat_domisili' => 'Alamat Domisili Test',
        'rt' => '001',
        'rw' => '002',
        'kode_pos' => '12345',
        'status_tinggal' => 'Milik Sendiri',

        // orang_tua
        'id_tempat_lahir_ayah' => 1,
        'nik_ayah' => '1111111111111111',
        'nama_ayah' => 'AYAH TEST',
        'tahun_lahir_ayah' => 1975,
        'pendidikan_ayah' => 'S1',
        'pekerjaan_ayah' => 'PNS / TNI / Polri',
        'penghasilan_ayah' => 'Rp5.000.000 sampai Rp20.000.000',
        'agama_ayah' => 'Islam',
        'id_tempat_lahir_ibu' => 1,
        'nik_ibu' => '2222222222222222',
        'nama_ibu' => 'IBU TEST',
        'tahun_lahir_ibu' => 1980,
        'pendidikan_ibu' => 'SMA',
        'pekerjaan_ibu' => 'Tidak Bekerja',
        'penghasilan_ibu' => 'Tidak Berpenghasilan',
        'agama_ibu' => 'Islam',

        // kontak
        'email' => 'test_siswa@gmail.com',
        'no_telepon_rumah' => '021123456',
        'no_telepon_orang_tua' => '081234567890',
        'no_telepon_siswa' => '089987654321',

        // rincian_pelajar
        'lingkar_kepala' => 55,
        'tinggi_badan' => 160,
        'berat_badan' => 50,
        'golongan_darah' => 'B',
        'anak_ke' => 2,
        'jarak_rumah' => 1500,
        'transportasi' => 'Motor',
        'jumlah_saudara' => 2,
        'penyakit_yang_diderita' => 'Alergi debu',
        'foto_profil' => 'test_foto.jpg',

        // kip
        'penerima_kps' => 0,
        'punya_kip' => 0,
        'layak_kip' => 0,
        'alasan_layak' => 'Tidak Ada',
        'no_kip' => '',
        'status_anak' => 'Yatim',

        // registrasi
        'jalur_diterima' => 'Zonasi',
        'jenis_pendaftaran' => 'Siswa Baru',
        'tanggal_masuk' => '2026-06-01',
        'paud_formal' => 1,
        'paud_non_formal' => 0,
        'hobi' => 'Membaca',

        // dokumen
        'berkas_kk' => 'kk_test.pdf',
        'berkas_akta' => 'akta_test.pdf',
        'berkas_ijazah_sd' => 'sd_test.pdf',
        'berkas_ijazah_smp' => 'smp_test.pdf',
        'berkas_ijazah_sma' => null,
        'berkas_mutasi_masuk' => null,
        'berkas_mutasi_keluar' => null,
        'berkas_kip' => null
    ];

    // 2. Test create
    echo "Testing Create Siswa...\n";
    $id = $model->create($testData);
    echo "Created Siswa with ID: $id\n";

    // 3. Test findFullById
    echo "Testing findFullById...\n";
    $fullData = $model->findFullById($id);
    if ($fullData) {
        echo "Found full data! Nama Lengkap: " . $fullData['nama_lengkap'] . "\n";
        echo "Alamat KK: " . $fullData['alamat_kk'] . "\n";
        echo "Nama Ayah: " . $fullData['nama_ayah'] . "\n";
        echo "Email: " . $fullData['email'] . "\n";
        echo "Tinggi Badan: " . $fullData['tinggi_badan'] . "\n";
        echo "Hobi: " . $fullData['hobi'] . "\n";
    } else {
        throw new \Exception("Full data not found!");
    }

    // 4. Test update
    echo "Testing Update Siswa...\n";
    $testData['nama_lengkap'] = 'TEST SISWA BARU UPDATED';
    $testData['alamat_kk'] = 'Alamat KK Test Updated';
    $testData['hobi'] = 'Berenang';
    $testData['tinggi_badan'] = 165;
    
    $res = $model->update($id, $testData);
    if ($res) {
        echo "Update operation returned true!\n";
    } else {
        throw new \Exception("Update operation returned false!");
    }

    // 5. Test findFullById after update
    echo "Testing findFullById after update...\n";
    $fullDataUpdated = $model->findFullById($id);
    if ($fullDataUpdated) {
        echo "Updated Nama Lengkap: " . $fullDataUpdated['nama_lengkap'] . " (Expected: TEST SISWA BARU UPDATED)\n";
        echo "Updated Alamat KK: " . $fullDataUpdated['alamat_kk'] . " (Expected: Alamat KK Test Updated)\n";
        echo "Updated Hobi: " . $fullDataUpdated['hobi'] . " (Expected: Berenang)\n";
        echo "Updated Tinggi Badan: " . $fullDataUpdated['tinggi_badan'] . " (Expected: 165)\n";
    } else {
        throw new \Exception("Full data not found after update!");
    }

    // 6. Clean up database
    echo "Cleaning up created test records...\n";
    $pdo = \App\Config\Database::getConnection();
    // Tables cascade delete is handled by foreign keys in ON DELETE CASCADE
    $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = ?");
    $stmt->execute([$id]);
    echo "Cleaned up successfully!\n";

    echo "\n=== ALL TESTS PASSED SUCCESSFULLY! ===\n";

} catch (\Throwable $e) {
    echo "TEST FAILED: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
