<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SnpmbDataSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = __DIR__ . '/data';

        // 1. PTN (Master Kampus)
        $ptnFile = $dataPath . '/snpmb_snbp_ptn.csv';
        $ptnMap = []; // id_ptn => UUID

        if (file_exists($ptnFile)) {
            $handle = fopen($ptnFile, 'r');
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 5 && trim($row[0]) !== '') {
                    $id_ptn    = trim($row[0]);
                    $kode_ptn  = trim($row[1] ?? '');
                    $nama_ptn  = trim($row[2] ?? '');
                    $web       = trim($row[3] ?? '');
                    $jenis     = trim($row[4] ?? 'PTN Akademik');
                    $alamat    = trim($row[5] ?? '');
                    $provinsi  = trim($row[6] ?? '');
                    $kota      = trim($row[8] ?? '');

                    $existing = DB::table('pdss.master_kampus')
                        ->where('nama_kampus', $nama_ptn)
                        ->orWhere('id_ptn', $id_ptn)
                        ->first();

                    if ($existing) {
                        $ptnUuid = $existing->id;
                        DB::table('pdss.master_kampus')->where('id', $ptnUuid)->update([
                            'id_ptn'        => $id_ptn,
                            'kode_ptn'      => $kode_ptn,
                            'web'           => $web,
                            'jenis'         => $jenis,
                            'alamat'        => $alamat,
                            'provinsi'      => $provinsi,
                            'kota'          => $kota,
                            'updated_at'    => now(),
                        ]);
                    } else {
                        $ptnUuid = (string)Str::uuid();
                        DB::table('pdss.master_kampus')->insert([
                            'id'            => $ptnUuid,
                            'tenant_id'     => null,
                            'id_ptn'        => $id_ptn,
                            'kode_ptn'      => $kode_ptn,
                            'nama_kampus'   => $nama_ptn,
                            'web'           => $web,
                            'jenis'         => $jenis,
                            'akreditasi'    => 'A',
                            'alamat'        => $alamat,
                            'provinsi'      => $provinsi,
                            'kota'          => $kota,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }
                    $ptnMap[$id_ptn] = $ptnUuid;
                }
            }
            fclose($handle);
            $this->command?->info('✔ Seeding pdss.master_kampus selesai (' . count($ptnMap) . ' PTN).');
        }

        // 2. Program Studi
        $prodiFile = $dataPath . '/snpmb_snbp_prodi.csv';
        $prodiMap = []; // id_prodi => UUID

        if (file_exists($prodiFile)) {
            $handle = fopen($prodiFile, 'r');
            $header = fgetcsv($handle);
            $inserts = [];

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 5 && trim($row[0]) !== '') {
                    $id_prodi      = trim($row[0]);
                    $kode_prodi    = trim($row[1] ?? '');
                    $id_ptn        = trim($row[2] ?? '');
                    $nama_prodi    = trim($row[3] ?? '');
                    $jenjang       = trim($row[4] ?? 'S1');
                    $daya_tampung  = (int)($row[5] ?? 50);
                    $portofolio    = trim($row[6] ?? 'Tidak Ada');
                    $kampus_uuid   = $ptnMap[$id_ptn] ?? null;

                    if (!$kampus_uuid) {
                        $kampus_uuid = DB::table('pdss.master_kampus')->where('id_ptn', $id_ptn)->value('id');
                    }

                    if ($kampus_uuid) {
                        $existing = DB::table('pdss.master_kampus_prodi')
                            ->where('kampus_id', $kampus_uuid)
                            ->where(function($q) use ($nama_prodi, $kode_prodi) {
                                $q->where('nama_prodi', $nama_prodi)
                                  ->orWhere('kode_prodi', $kode_prodi);
                            })
                            ->first();

                        if ($existing) {
                            $prodiUuid = $existing->id;
                            $prodiMap[$id_prodi] = $prodiUuid;
                        } else {
                            $prodiUuid = (string)Str::uuid();
                            $prodiMap[$id_prodi] = $prodiUuid;
                            $inserts[] = [
                                'id'                    => $prodiUuid,
                                'tenant_id'             => null,
                                'kampus_id'             => $kampus_uuid,
                                'nama_prodi'            => $nama_prodi,
                                'program_studi'         => $nama_prodi,
                                'jenjang'               => $jenjang,
                                'daya_tampung_sekarang' => $daya_tampung,
                                'jenis_portofolio'      => $portofolio,
                                'kode_prodi'            => $kode_prodi,
                                'created_at'            => now(),
                                'updated_at'            => now(),
                            ];

                            if (count($inserts) >= 500) {
                                DB::table('pdss.master_kampus_prodi')->insert($inserts);
                                $inserts = [];
                            }
                        }
                    }
                }
            }
            if (!empty($inserts)) {
                DB::table('pdss.master_kampus_prodi')->insert($inserts);
            }
            fclose($handle);

            $totalProdi = DB::table('pdss.master_kampus_prodi')->count();
            $this->command?->info("✔ Seeding pdss.master_kampus_prodi selesai ({$totalProdi} program studi).");
        }

        // 3. Historis Riwayat Seleksi & Keketatan
        $historisFile = $dataPath . '/snpmb_snbp_historis_peminat.csv';
        if (file_exists($historisFile)) {
            $existingRiwayatCount = DB::table('pdss.kampus_prodi_riwayat')->count();
            if ($existingRiwayatCount < 20000) {
                // Preload all prodi map to memory
                $allProdis = DB::table('pdss.master_kampus_prodi')->pluck('id', 'kode_prodi')->toArray();

                $handle = fopen($historisFile, 'r');
                $header = fgetcsv($handle);
                $batch = [];

                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) >= 5 && trim($row[0]) !== '') {
                        $id_prodi     = trim($row[0]);
                        $kode_prodi   = trim($row[1] ?? '');
                        $tahun        = (int)($row[2] ?? 2025);
                        $daya_tampung = (int)($row[3] ?? 0);
                        $peminat      = (int)($row[4] ?? 0);
                        $diterima     = (int)($row[5] ?? 0);
                        $keketatan    = trim($row[6] ?? '0.00%');

                        $prodiUuid = $prodiMap[$id_prodi] ?? ($allProdis[$kode_prodi] ?? null);
                        if ($prodiUuid) {
                            $batch[] = [
                                'id'               => (string)Str::uuid(),
                                'tenant_id'        => null,
                                'prodi_id'         => $prodiUuid,
                                'tahun'            => $tahun,
                                'daya_tampung'     => $daya_tampung,
                                'jumlah_pendaftar' => $peminat,
                                'diterima'         => $diterima,
                                'keketatan'        => $keketatan,
                                'created_at'       => now(),
                                'updated_at'       => now(),
                            ];

                            if (count($batch) >= 1000) {
                                DB::table('pdss.kampus_prodi_riwayat')->insert($batch);
                                $batch = [];
                            }
                        }
                    }
                }
                if (!empty($batch)) {
                    DB::table('pdss.kampus_prodi_riwayat')->insert($batch);
                }
                fclose($handle);
            }

            $totalRiwayat = DB::table('pdss.kampus_prodi_riwayat')->count();
            $this->command?->info("✔ Seeding pdss.kampus_prodi_riwayat selesai ({$totalRiwayat} rekaman riwayat keketatan).");
        }
    }
}
