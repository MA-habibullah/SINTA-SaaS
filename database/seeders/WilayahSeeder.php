<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = __DIR__ . '/data';

        // 1. Provinsi
        $provFile = $dataPath . '/provinsi.csv';
        if (file_exists($provFile)) {
            $handle = fopen($provFile, 'r');
            $header = fgetcsv($handle, 0, ';');
            $rows = [];
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                if (count($data) >= 2 && trim($data[0]) !== '') {
                    $rows[] = [
                        'id_provinsi'   => trim($data[0]),
                        'nama_provinsi' => trim($data[1]),
                    ];
                }
            }
            fclose($handle);

            foreach (array_chunk($rows, 100) as $chunk) {
                foreach ($chunk as $row) {
                    DB::table('core.provinsi')->updateOrInsert(
                        ['id_provinsi' => $row['id_provinsi']],
                        ['nama_provinsi' => $row['nama_provinsi']]
                    );
                }
            }
            $this->command?->info('✔ Seeding core.provinsi selesai (' . count($rows) . ' provinsi).');
        }

        // 2. Kota / Kabupaten
        $kotaFile = $dataPath . '/kota.csv';
        if (file_exists($kotaFile)) {
            $handle = fopen($kotaFile, 'r');
            $header = fgetcsv($handle, 0, ';');
            $rows = [];
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                if (count($data) >= 3 && trim($data[0]) !== '') {
                    $rows[] = [
                        'id_kota'     => trim($data[0]),
                        'id_provinsi' => trim($data[1]),
                        'nama_kota'   => trim($data[2]),
                    ];
                }
            }
            fclose($handle);

            foreach (array_chunk($rows, 200) as $chunk) {
                foreach ($chunk as $row) {
                    DB::table('core.kota')->updateOrInsert(
                        ['id_kota' => $row['id_kota']],
                        ['id_provinsi' => $row['id_provinsi'], 'nama_kota' => $row['nama_kota']]
                    );
                }
            }
            $this->command?->info('✔ Seeding core.kota selesai (' . count($rows) . ' kab/kota).');
        }

        // 3. Kecamatan
        $kecFile = $dataPath . '/kecamatan.csv';
        if (file_exists($kecFile)) {
            $handle = fopen($kecFile, 'r');
            $header = fgetcsv($handle, 0, ';');
            $rows = [];
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                if (count($data) >= 3 && trim($data[0]) !== '') {
                    $rows[] = [
                        'id_kecamatan'   => trim($data[0]),
                        'id_kota'        => trim($data[1]),
                        'nama_kecamatan' => trim($data[2]),
                    ];
                }
            }
            fclose($handle);

            // Cek jika sudah terisi untuk menghemat waktu seeding
            $existingCount = DB::table('core.kecamatan')->count();
            if ($existingCount < count($rows)) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    foreach ($chunk as $row) {
                        DB::table('core.kecamatan')->updateOrInsert(
                            ['id_kecamatan' => $row['id_kecamatan']],
                            ['id_kota' => $row['id_kota'], 'nama_kecamatan' => $row['nama_kecamatan']]
                        );
                    }
                }
            }
            $this->command?->info('✔ Seeding core.kecamatan selesai (' . count($rows) . ' kecamatan).');
        }

        // 4. Kelurahan / Desa
        $kelFile = $dataPath . '/kelurahan.csv';
        if (file_exists($kelFile)) {
            $existingCount = DB::table('core.kelurahan')->count();
            if ($existingCount < 80000) {
                $handle = fopen($kelFile, 'r');
                $header = fgetcsv($handle, 0, ';');
                $batch = [];
                while (($data = fgetcsv($handle, 0, ';')) !== false) {
                    if (count($data) >= 3 && trim($data[0]) !== '') {
                        $batch[] = [
                            'id_kelurahan'   => trim($data[0]),
                            'id_kecamatan'   => trim($data[1]),
                            'nama_kelurahan' => trim($data[2]),
                        ];
                        if (count($batch) >= 1000) {
                            DB::table('core.kelurahan')->insertOrIgnore($batch);
                            $batch = [];
                        }
                    }
                }
                if (!empty($batch)) {
                    DB::table('core.kelurahan')->insertOrIgnore($batch);
                }
                fclose($handle);
            }
            $this->command?->info('✔ Seeding core.kelurahan selesai (83.761 kelurahan/desa).');
        }
    }
}
