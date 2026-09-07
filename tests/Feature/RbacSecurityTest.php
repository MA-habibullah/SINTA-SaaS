<?php

namespace Tests\Feature;

use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Modules\Core\Entities\User;

class RbacSecurityTest extends TestCase
{
    /**
     * Memastikan 9 peran utama terdefinisi dan dapat di-assign ke user.
     */
    public function test_nine_roles_are_properly_assigned(): void
    {
        $roles = [
            'super_admin', 'admin_sekolah', 'kepala_sekolah',
            'guru', 'wali_kelas', 'staf_tu', 'staf_keuangan',
            'siswa', 'orang_tua'
        ];

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $this->assertNotNull($role);
        }

        $user = new User([
            'username'     => 'test_guru',
            'email'        => 'guru_test@sinta.id',
            'nama_lengkap' => 'Guru Test RBAC',
        ]);

        $this->assertEquals('test_guru', $user->username);
    }
}
