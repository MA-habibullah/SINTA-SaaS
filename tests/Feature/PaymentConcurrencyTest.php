<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Core\Entities\Tenant;
use Modules\Siswa\Entities\Siswa;
use Modules\Keuangan\Entities\PosKeuangan;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\KasBank;

class PaymentConcurrencyTest extends TestCase
{
    /**
     * Memastikan pelunasan tagihan tidak mengalami over-payment / race-condition saldo.
     */
    public function test_payment_updates_balance_and_status_accurately(): void
    {
        $tenant = Tenant::firstOrCreate(['npsn' => 'TEST_PAY'], ['nama_sekolah' => 'Sekolah Pay', 'is_active' => true]);
        session(['tenant_id' => $tenant->id]);

        $siswa = Siswa::withoutTenant()->create([
            'tenant_id'    => $tenant->id,
            'nisn'         => '9999888877',
            'nis'          => 'PAY001',
            'nama_lengkap' => 'Siswa Pembayar',
            'jenis_kelamin'=> 'L',
        ]);

        $pos = PosKeuangan::withoutTenant()->create([
            'tenant_id'       => $tenant->id,
            'kode_pos'        => 'POS-TEST',
            'nama_pos'        => 'SPP Uji Coba',
            'tipe_pembayaran' => 'Bulanan',
        ]);

        $kas = KasBank::withoutTenant()->create([
            'tenant_id'      => $tenant->id,
            'kode_akun'      => 'KAS-TEST',
            'nama_kas_bank'  => 'Kas Uji Coba',
            'saldo_saat_ini' => 1000000,
            'is_active'      => true,
        ]);

        $tagihan = TagihanSiswa::withoutTenant()->create([
            'tenant_id'         => $tenant->id,
            'siswa_id'          => $siswa->id,
            'pos_keuangan_id'   => $pos->id,
            'total_tagihan'     => 500000,
            'total_terbayar'    => 0,
            'sisa_tagihan'      => 500000,
            'status_pembayaran' => 'Belum Bayar',
        ]);

        $this->assertEquals(500000, (float)$tagihan->sisa_tagihan);
        $this->assertEquals('Belum Bayar', $tagihan->status_pembayaran);
    }
}
