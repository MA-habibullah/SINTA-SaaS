<?php

namespace Modules\Kesiswaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Siswa\Entities\Siswa;

class PrestasiSiswa extends BaseTenantModel
{
    protected $table = 'kesiswaan.prestasi_siswa';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'nama_lomba',
        'bidang', // Sains, Olahraga, Seni, Agama, Riset
        'tingkat', // Sekolah, Kecamatan, Kabupaten/Kota, Provinsi, Nasional, Internasional
        'peringkat_juara',
        'tahun',
        'penyelenggara',
        'sertifikat_file',
        'keterangan',
    ];

    protected $casts = [
        'tahun' => 'integer',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
