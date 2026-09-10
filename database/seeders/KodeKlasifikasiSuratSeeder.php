<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KodeKlasifikasiSuratSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan kolom-kolom DDL tersedia
        DB::unprepared("
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS kode_klasifikasi VARCHAR(50) NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS nama_klasifikasi TEXT NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS nama_kode_klasifikasi_surat TEXT NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ALTER COLUMN nama_klasifikasi TYPE TEXT;
            ALTER TABLE persuratan.kode_klasifikasi_surat ALTER COLUMN nama_kode_klasifikasi_surat TYPE TEXT;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS parent_kode VARCHAR(50) NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS level_klasifikasi INT DEFAULT 1;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS kategori_utama VARCHAR(100) NULL;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS retensi_aktif_tahun INT DEFAULT 5;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS retensi_inaktif_tahun INT DEFAULT 5;
            ALTER TABLE persuratan.kode_klasifikasi_surat ADD COLUMN IF NOT EXISTS retensi_tahun INT DEFAULT 5;
        ");

        $jsonPath = __DIR__ . '/data/kode_klasifikasi_surat.json';
        if (!file_exists($jsonPath)) {
            $this->command?->warn("Berkas {$jsonPath} tidak ditemukan.");
            return;
        }

        $items = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($items)) {
            $this->command?->warn("Format JSON kode klasifikasi surat tidak valid.");
            return;
        }

        $existingCodes = DB::table('persuratan.kode_klasifikasi_surat')
            ->whereNull('tenant_id')
            ->pluck('id', 'kode_klasifikasi')
            ->toArray();

        $inserts = [];
        foreach ($items as $item) {
            $kode = trim($item['kode_klasifikasi'] ?? '');
            $nama = trim($item['nama_klasifikasi'] ?? '');
            if (!$kode || !$nama) continue;

            $parent = !empty($item['parent_kode']) ? trim($item['parent_kode']) : null;
            $level = isset($item['level_klasifikasi']) ? (int)$item['level_klasifikasi'] : 1;
            $kategoriUtama = trim($item['kategori_utama'] ?? 'Umum/Organisasi');
            $retensiAktif = isset($item['retensi_aktif_tahun']) ? (int)$item['retensi_aktif_tahun'] : 5;
            $retensiInaktif = isset($item['retensi_inaktif_tahun']) ? (int)$item['retensi_inaktif_tahun'] : 5;

            if (isset($existingCodes[$kode])) {
                DB::table('persuratan.kode_klasifikasi_surat')
                    ->where('id', $existingCodes[$kode])
                    ->update([
                        'nama_klasifikasi' => $nama,
                        'nama_kode_klasifikasi_surat' => $nama,
                        'parent_kode' => $parent,
                        'level_klasifikasi' => $level,
                        'kategori_utama' => $kategoriUtama,
                        'kategori' => $kategoriUtama,
                        'retensi_aktif_tahun' => $retensiAktif,
                        'retensi_inaktif_tahun' => $retensiInaktif,
                        'retensi_tahun' => $retensiAktif,
                        'updated_at' => now(),
                    ]);
            } else {
                $inserts[] = [
                    'id' => (string)Str::uuid(),
                    'tenant_id' => null,
                    'kode_klasifikasi' => $kode,
                    'nama_klasifikasi' => $nama,
                    'nama_kode_klasifikasi_surat' => $nama,
                    'parent_kode' => $parent,
                    'level_klasifikasi' => $level,
                    'kategori_utama' => $kategoriUtama,
                    'kategori' => $kategoriUtama,
                    'retensi_aktif_tahun' => $retensiAktif,
                    'retensi_inaktif_tahun' => $retensiInaktif,
                    'retensi_tahun' => $retensiAktif,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($inserts) >= 200) {
                    DB::table('persuratan.kode_klasifikasi_surat')->insert($inserts);
                    $inserts = [];
                }
            }
        }

        if (!empty($inserts)) {
            DB::table('persuratan.kode_klasifikasi_surat')->insert($inserts);
        }

        $totalCount = DB::table('persuratan.kode_klasifikasi_surat')->count();
        $this->command?->info("✔ Seeding persuratan.kode_klasifikasi_surat selesai ({$totalCount} kode klasifikasi aktif).");
    }
}
