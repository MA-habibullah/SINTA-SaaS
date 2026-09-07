<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Core\Entities\Tenant;
use Modules\Siswa\Entities\Siswa;

class TenantIsolationTest extends TestCase
{
    /**
     * Memastikan Global Scope 'tenant_isolation' menyaring data antar-tenant secara ketat.
     */
    public function test_tenant_data_is_strictly_isolated(): void
    {
        // 1. Simulasikan Tenant A
        $tenantA = Tenant::firstOrCreate(
            ['npsn' => 'TEST_TENANT_A'],
            ['nama_sekolah' => 'SMA Test A', 'is_active' => true]
        );

        // 2. Simulasikan Tenant B
        $tenantB = Tenant::firstOrCreate(
            ['npsn' => 'TEST_TENANT_B'],
            ['nama_sekolah' => 'SMA Test B', 'is_active' => true]
        );

        // 3. Masukkan Siswa ke Tenant A
        $siswaA = Siswa::withoutTenant()->create([
            'tenant_id'    => $tenantA->id,
            'nisn'         => '1111222233',
            'nis'          => 'A001',
            'nama_lengkap' => 'Siswa Sekolah A',
            'jenis_kelamin'=> 'L',
        ]);

        // 4. Set session aktif sebagai Tenant B
        session(['tenant_id' => $tenantB->id]);

        // 5. Query siswa harus kosong (tidak boleh bocor ke Tenant B)
        $result = Siswa::where('nisn', '1111222233')->first();
        $this->assertNull($result, 'Data siswa tenant A bocor ke sesi tenant B!');
    }
}
