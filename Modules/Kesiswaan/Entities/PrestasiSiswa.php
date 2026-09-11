<?php

namespace Modules\Kesiswaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Siswa\Entities\Siswa;

class PrestasiSiswa extends BaseTenantModel
{
    protected $table = 'kesiswaan.prestasi_siswa';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_prestasi_siswa',
        'kategori',
        'deskripsi',
        'tahun_ajaran_id',
        'semester',
        'bidang_lomba',
        'nama_lomba',
        'nomor_sertifikat',
        'juara',
        'tingkat_kejuaraan',
        'jenis_lomba',
        'tempat_lomba',
        'tanggal_lomba',
        'penyelenggara',
        'guru_pendamping',
        'poin_prestasi',
        'foto_bukti_prestasi',
        'foto_siswa_prestasi',
        'foto_kegiatan_lomba',
        'surat_tugas_pdf',
        'is_active',
    ];

    protected $casts = [
        'tanggal_lomba' => 'date',
        'poin_prestasi' => 'integer',
        'is_active'     => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function anggota(): HasMany
    {
        return $this->hasMany(PrestasiSiswaAnggota::class, 'id_prestasi');
    }
}
