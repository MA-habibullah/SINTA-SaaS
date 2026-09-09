<?php

namespace Modules\Bk\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Konseling extends BaseTenantModel
{
    use SoftDeletes;

    protected $table = 'bk.catatan_bk';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'id_siswa',
        'tanggal_konseling',
        'jenis_konseling', // 'Pribadi', 'Sosial', 'Belajar', 'Karier', 'Kedisiplinan'
        'jenis_kasus',
        'topik_masalah',
        'nama_catatan_bk',
        'ringkasan_konseling',
        'catatan',
        'solusi_tindak_lanjut',
        'tindak_lanjut',
        'status_kasus', // 'Terbuka', 'Dalam Pendampingan', 'Selesai'
        'is_rahasia',   // 0: Terbuka, 1: Rahasia
        'guru_bk_nama',
        'id_guru_bk',
        'snapshot_nama_siswa',
        'snapshot_nisn',
        'snapshot_nis',
        'snapshot_nama_kelas',
        'id_kelas_snapshot',
        'surat_panggilan_pdf',
        'foto_panggilan',
        'is_active',
    ];

    protected $casts = [
        'tanggal_konseling' => 'date',
        'is_rahasia'        => 'boolean',
        'is_active'         => 'boolean',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
