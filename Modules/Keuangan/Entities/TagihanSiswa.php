<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagihanSiswa extends BaseTenantModel
{
    protected $table = 'keuangan.transaksi_spp_tagihan';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'pos_keuangan_id',
        'tahun_ajaran_id',
        'bulan', // 1 - 12 (untuk SPP bulanan)
        'tahun',
        'nomor_tagihan',
        'total_tagihan',
        'total_terbayar',
        'sisa_tagihan',
        'status_pembayaran', // 'Belum Bayar', 'Sebagian', 'Lunas'
        'tanggal_jatuh_tempo',
    ];

    protected $casts = [
        'bulan'          => 'integer',
        'tahun'          => 'integer',
        'total_tagihan'  => 'decimal:2',
        'total_terbayar' => 'decimal:2',
        'sisa_tagihan'   => 'decimal:2',
        'tanggal_jatuh_tempo' => 'date',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function pos(): BelongsTo
    {
        return $this->belongsTo(PosKeuangan::class, 'pos_keuangan_id', 'id');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(TransaksiPembayaran::class, 'tagihan_id', 'id');
    }
}
