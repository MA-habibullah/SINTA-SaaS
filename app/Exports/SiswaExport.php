<?php

namespace App\Exports;

use App\Config\Database;
use PDO;
use Shuchkin\SimpleXLSXGen;

class SiswaExport {

    /**
     * Format date (YYYY-MM-DD) into Indonesian local text format, e.g. "27 Januari 2010"
     */
    public static function formatTanggalIndo(?string $dateStr): string {
        if (empty($dateStr)) {
            return '';
        }
        $parts = explode('-', $dateStr);
        if (count($parts) !== 3) {
            return $dateStr;
        }
        $tahun = $parts[0];
        $bulan = (int)$parts[1];
        $tanggal = (int)$parts[2];
        
        $bulanIndo = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        
        $namaBulan = $bulanIndo[$bulan] ?? '';
        return "$tanggal $namaBulan $tahun";
    }

    /**
     * Query data and download Excel file
     */
    public static function download(?string $tenantId = null): void {
        try {
            $db = Database::getConnection();
            
            $sql = "SELECT 
                        s.nama_lengkap,
                        s.nisn,
                        s.nis,
                        s.nik,
                        s.no_kk,
                        s.jenis_kelamin,
                        s.tanggal_lahir,
                        s.agama,
                        s.nama_panggilan,
                        s.ukuran_seragam_sekolah,
                        s.ukuran_seragam_olahraga,
                        s.sekolah_asal,
                        s.status,
                        t.nama_sekolah as nama_sekolah_tenant,
                        k.nama_kelas,
                        j.nama_jurusan,
                        jj.nama_jenjang,
                        ta.tahun_ajaran,
                        ak.tahun_angkatan,
                        pd.nama_pendidikan,
                        COALESCE(kl_lahir.nama_kota, s.tempat_lahir) as tempat_lahir,
                        ra.alamat_kk,
                        ra.alamat_domisili,
                        ra.rt,
                        ra.rw,
                        ra.kode_pos,
                        ra.status_tinggal,
                        prov.nama_provinsi,
                        kt.nama_kota as nama_kota_alamat,
                        kec.nama_kecamatan,
                        kel.nama_kelurahan,
                        ko.email,
                        ko.no_telepon_rumah,
                        ko.no_telepon_orang_tua,
                        ko.no_telepon_siswa,
                        ot.nama_ayah,
                        ot.nik_ayah,
                        ot.nama_ibu,
                        ot.nik_ibu,
                        ot.nama_wali,
                        ot.nik_wali,
                        kp.penerima_kps,
                        kp.punya_kip,
                        kp.no_kip,
                        rg.jenis_pendaftaran,
                        rg.jalur_diterima,
                        rg.tanggal_masuk
                    FROM siswa s
                    LEFT JOIN tenants t ON s.tenant_id = t.id
                    LEFT JOIN kelas k ON s.id_kelas = k.id
                    LEFT JOIN jurusan j ON s.id_jurusan = j.id
                    LEFT JOIN jenjang jj ON s.id_jenjang = jj.id
                    LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id
                    LEFT JOIN angkatan ak ON s.id_angkatan = ak.id
                    LEFT JOIN pendidikan pd ON s.id_pendidikan = pd.id
                    LEFT JOIN kota kl_lahir ON s.tempat_lahir = kl_lahir.id_kota
                    LEFT JOIN rincian_alamat ra ON s.id = ra.id_siswa
                    LEFT JOIN kelurahan kel ON ra.id_kelurahan = kel.id_kelurahan
                    LEFT JOIN kecamatan kec ON kel.id_kecamatan = kec.id_kecamatan
                    LEFT JOIN kota kt ON kec.id_kota = kt.id_kota
                    LEFT JOIN provinsi prov ON kt.id_provinsi = prov.id_provinsi
                    LEFT JOIN kontak ko ON s.id = ko.id_siswa
                    LEFT JOIN orang_tua ot ON s.id = ot.id_siswa
                    LEFT JOIN kip kp ON s.id = kp.id_siswa
                    LEFT JOIN registrasi rg ON s.id = rg.id_siswa
                    WHERE s.deleted_at IS NULL";
            
            if ($tenantId !== null) {
                $sql .= " AND s.tenant_id = :tenant_id";
            }
            
            $sql .= " ORDER BY t.nama_sekolah ASC, s.nama_lengkap ASC";
            
            $stmt = $db->prepare($sql);
            
            if ($tenantId !== null) {
                $stmt->execute(['tenant_id' => $tenantId]);
            } else {
                $stmt->execute();
            }
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Build excel data matrix
            $excelData = [];
            
            // Header columns
            $excelData[] = [
                'No',
                'Instansi Sekolah',
                'Nama Lengkap',
                'Nama Panggilan',
                'NISN',
                'NIS',
                'NIK',
                'No. KK',
                'Jenis Kelamin',
                'Tempat Lahir',
                'Tanggal Lahir',
                'Agama',
                'Ukuran Seragam Sekolah',
                'Ukuran Seragam Olahraga',
                'Sekolah Asal',
                'Status',
                'Kelas',
                'Jurusan',
                'Jenjang',
                'Tahun Ajaran',
                'Tahun Angkatan',
                'Pendidikan',
                'Alamat KK',
                'Alamat Domisili',
                'RT',
                'RW',
                'Kode Pos',
                'Provinsi',
                'Kota/Kabupaten',
                'Kecamatan',
                'Kelurahan',
                'Status Tinggal',
                'Email',
                'No. Telp Rumah',
                'No. Telp Siswa',
                'No. Telp Orang Tua',
                'Nama Ayah',
                'NIK Ayah',
                'Nama Ibu',
                'NIK Ibu',
                'Nama Wali',
                'NIK Wali',
                'Penerima KPS',
                'Punya KIP',
                'No. KIP',
                'Jenis Pendaftaran',
                'Jalur Diterima',
                'Tanggal Masuk'
            ];
            
            $no = 1;
            foreach ($rows as $row) {
                $excelData[] = [
                    $no++,
                    (string)($row['nama_sekolah_tenant'] ?? ''),
                    (string)($row['nama_lengkap'] ?? ''),
                    (string)($row['nama_panggilan'] ?? ''),
                    (string)($row['nisn'] ?? ''),
                    (string)($row['nis'] ?? ''),
                    (string)($row['nik'] ?? ''),
                    (string)($row['no_kk'] ?? ''),
                    (string)($row['jenis_kelamin'] ?? ''),
                    (string)($row['tempat_lahir'] ?? ''),
                    (string)self::formatTanggalIndo($row['tanggal_lahir']),
                    (string)($row['agama'] ?? ''),
                    (string)($row['ukuran_seragam_sekolah'] ?? ''),
                    (string)($row['ukuran_seragam_olahraga'] ?? ''),
                    (string)($row['sekolah_asal'] ?? ''),
                    (string)($row['status'] ?? ''),
                    (string)($row['nama_kelas'] ?? ''),
                    (string)($row['nama_jurusan'] ?? ''),
                    (string)($row['nama_jenjang'] ?? ''),
                    (string)($row['tahun_ajaran'] ?? ''),
                    (string)($row['tahun_angkatan'] ?? ''),
                    (string)($row['nama_pendidikan'] ?? ''),
                    (string)($row['alamat_kk'] ?? ''),
                    (string)($row['alamat_domisili'] ?? ''),
                    (string)($row['rt'] ?? ''),
                    (string)($row['rw'] ?? ''),
                    (string)($row['kode_pos'] ?? ''),
                    (string)($row['nama_provinsi'] ?? ''),
                    (string)($row['nama_kota_alamat'] ?? ''),
                    (string)($row['nama_kecamatan'] ?? ''),
                    (string)($row['nama_kelurahan'] ?? ''),
                    (string)($row['status_tinggal'] ?? ''),
                    (string)($row['email'] ?? ''),
                    (string)($row['no_telepon_rumah'] ?? ''),
                    (string)($row['no_telepon_siswa'] ?? ''),
                    (string)($row['no_telepon_orang_tua'] ?? ''),
                    (string)($row['nama_ayah'] ?? ''),
                    (string)($row['nik_ayah'] ?? ''),
                    (string)($row['nama_ibu'] ?? ''),
                    (string)($row['nik_ibu'] ?? ''),
                    (string)($row['nama_wali'] ?? ''),
                    (string)($row['nik_wali'] ?? ''),
                    ($row['penerima_kps'] ? 'Ya' : 'Tidak'),
                    ($row['punya_kip'] ? 'Ya' : 'Tidak'),
                    (string)($row['no_kip'] ?? ''),
                    (string)($row['jenis_pendaftaran'] ?? ''),
                    (string)($row['jalur_diterima'] ?? ''),
                    (string)self::formatTanggalIndo($row['tanggal_masuk'])
                ];
            }
            
            $filename = "export_siswa_" . date('Ymd_His') . ".xlsx";
            SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
            exit;
            
        } catch (\Throwable $e) {
            die("Gagal mengekspor data: " . $e->getMessage());
        }
    }
}
