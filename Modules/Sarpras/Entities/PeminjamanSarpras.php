<?php

namespace Modules\Sarpras\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Entities\User;

class PeminjamanSarpras extends BaseTenantModel
{
    protected $table = 'sarpras.peminjaman_sarpras';

    protected $fillable = [
        'id',
        'tenant_id',
        'nomor_peminjaman',
        'peminjam_id',       // FK ke users
        'nama_peminjam',     // Nama bebas jika bukan user sistem
        'tujuan_peminjaman',
        'tanggal_pinjam',
        'tanggal_rencana_kembali',
        'tanggal_kembali_aktual',
        'status',            // 'Menunggu Persetujuan', 'Disetujui', 'Dipinjam', 'Dikembalikan', 'Ditolak'
        'disetujui_oleh',
        'catatan_persetujuan',
        'catatan_pengembalian',
    ];

    protected $casts = [
        'tanggal_pinjam'            => 'date',
        'tanggal_rencana_kembali'   => 'date',
        'tanggal_kembali_aktual'    => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PeminjamanSarprasItem::class, 'peminjaman_id', 'id');
    }

    public function peminjam()
    {
        return $this->belongsTo(User::class, 'peminjam_id', 'id');
    }
}
