<?php

namespace Modules\Persuratan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disposisi extends BaseTenantModel
{
    protected $table = 'persuratan.disposisi';

    protected $fillable = [
        'id',
        'tenant_id',
        'surat_masuk_id',
        'dari_jabatan', // 'Kepala Sekolah'
        'diteruskan_kepada', // 'Waka Kurikulum', 'Waka Kesiswaan', 'Kepala TU'
        'instruksi_disposisi', // 'Tindak lanjuti', 'Hadirkan guru', 'Arsipkan', dll.
        'catatan_tambahan',
        'batas_waktu_tindak_lanjut',
        'status_tindak_lanjut', // 'Menunggu', 'Sedang Diproses', 'Selesai'
    ];

    protected $casts = [
        'batas_waktu_tindak_lanjut' => 'date',
    ];

    public function suratMasuk(): BelongsTo
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id', 'id');
    }
}
