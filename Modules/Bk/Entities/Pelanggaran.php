<?php

namespace Modules\Bk\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pelanggaran extends BaseTenantModel
{
    use SoftDeletes;

    protected $table = 'bk.pelanggaran_siswa';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'pelanggaran_id',
        'nama_pelanggaran_siswa',
        'nama_pelanggaran',
        'kategori', // 'Ringan', 'Sedang', 'Berat', 'Khusus'
        'poin_pelanggaran',
        'tanggal_kejadian',
        'tindakan_hukuman',
        'petugas_pencatat',
        'keterangan',
        'deskripsi',
        'foto_bukti',
        'snapshot_nama_siswa',
        'snapshot_nisn',
        'snapshot_nis',
        'snapshot_nama_kelas',
        'id_kelas_snapshot',
        'status_pembinaan', // 'Belum Dibina', 'Dalam Pembinaan', 'Selesai'
        'surat_panggilan_pdf',
        'is_active',
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
        'poin_pelanggaran' => 'integer',
        'is_active'        => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function masterPelanggaran(): BelongsTo
    {
        return $this->belongsTo(MasterPelanggaran::class, 'pelanggaran_id', 'id');
    }
}
