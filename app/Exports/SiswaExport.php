<?php

namespace App\Exports;

use App\Config\Database;
use PDO;

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
            
            // Set headers for excel download
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=export_siswa_" . date('Ymd_His') . ".xls");
            header("Pragma: no-cache");
            header("Expires: 0");
            
            // Render spreadsheet HTML with number formatting CSS
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            echo '<style>';
            echo '  .text-format { mso-number-format: "\@"; }';
            echo '  table { border-collapse: collapse; }';
            echo '  th { background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #dee2e6; padding: 8px; text-align: center; }';
            echo '  td { border: 1px solid #dee2e6; padding: 8px; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>No</th>';
            echo '<th>Instansi Sekolah</th>';
            echo '<th>Nama Lengkap</th>';
            echo '<th>Nama Panggilan</th>';
            echo '<th>NISN</th>';
            echo '<th>NIS</th>';
            echo '<th>NIK</th>';
            echo '<th>No. KK</th>';
            echo '<th>Jenis Kelamin</th>';
            echo '<th>Tempat Lahir</th>';
            echo '<th>Tanggal Lahir</th>';
            echo '<th>Agama</th>';
            echo '<th>Ukuran Seragam Sekolah</th>';
            echo '<th>Ukuran Seragam Olahraga</th>';
            echo '<th>Sekolah Asal</th>';
            echo '<th>Status</th>';
            echo '<th>Kelas</th>';
            echo '<th>Jurusan</th>';
            echo '<th>Jenjang</th>';
            echo '<th>Tahun Ajaran</th>';
            echo '<th>Tahun Angkatan</th>';
            echo '<th>Pendidikan</th>';
            echo '<th>Alamat KK</th>';
            echo '<th>Alamat Domisili</th>';
            echo '<th>RT</th>';
            echo '<th>RW</th>';
            echo '<th>Kode Pos</th>';
            echo '<th>Provinsi</th>';
            echo '<th>Kota/Kabupaten</th>';
            echo '<th>Kecamatan</th>';
            echo '<th>Kelurahan</th>';
            echo '<th>Status Tinggal</th>';
            echo '<th>Email</th>';
            echo '<th>No. Telp Rumah</th>';
            echo '<th>No. Telp Siswa</th>';
            echo '<th>No. Telp Orang Tua</th>';
            echo '<th>Nama Ayah</th>';
            echo '<th>NIK Ayah</th>';
            echo '<th>Nama Ibu</th>';
            echo '<th>NIK Ibu</th>';
            echo '<th>Nama Wali</th>';
            echo '<th>NIK Wali</th>';
            echo '<th>Penerima KPS</th>';
            echo '<th>Punya KIP</th>';
            echo '<th>No. KIP</th>';
            echo '<th>Jenis Pendaftaran</th>';
            echo '<th>Jalur Diterima</th>';
            echo '<th>Tanggal Masuk</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            $no = 1;
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>' . $no++ . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_sekolah_tenant'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_lengkap'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_panggilan'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['nisn'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['nis'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['nik'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['no_kk'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['jenis_kelamin'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['tempat_lahir'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars(self::formatTanggalIndo($row['tanggal_lahir'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['agama'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['ukuran_seragam_sekolah'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['ukuran_seragam_olahraga'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['sekolah_asal'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['status'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['nama_kelas'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_jurusan'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_jenjang'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['tahun_ajaran'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['tahun_angkatan'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_pendidikan'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['alamat_kk'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['alamat_domisili'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['rt'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['rw'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['kode_pos'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_provinsi'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_kota_alamat'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_kecamatan'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_kelurahan'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['status_tinggal'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['email'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['no_telepon_rumah'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['no_telepon_siswa'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['no_telepon_orang_tua'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_ayah'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['nik_ayah'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_ibu'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['nik_ibu'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_wali'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['nik_wali'] ?? '') . '</td>';
                echo '<td>' . ($row['penerima_kps'] ? 'Ya' : 'Tidak') . '</td>';
                echo '<td>' . ($row['punya_kip'] ? 'Ya' : 'Tidak') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars($row['no_kip'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['jenis_pendaftaran'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['jalur_diterima'] ?? '') . '</td>';
                echo '<td class="text-format">' . htmlspecialchars(self::formatTanggalIndo($row['tanggal_masuk'])) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</body>';
            echo '</html>';
            exit;
            
        } catch (\Throwable $e) {
            die("Gagal mengekspor data: " . $e->getMessage());
        }
    }
}
