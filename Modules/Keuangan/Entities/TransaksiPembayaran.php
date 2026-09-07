<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Modules\Core\Entities\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiPembayaran extends BaseTenantModel
{
    protected $table = 'keuangan.transaksi_pembayaran';

    protected $fillable = [
        'id',
        'tenant_id',
        'nomor_transaksi',
        'tagihan_id',
        'siswa_id',
        'nominal_bayar',
        'metode_pembayaran', // 'Tunai', 'Transfer Bank', 'QRIS', 'Payment Gateway'
        'tanggal_bayar',
        'kas_id',
        'user_id_kasir',
        'catatan',
        'bukti_bayar_url',
    ];

    protected $casts = [
        'nominal_bayar' => 'decimal:2',
        'tanggal_bayar' => 'datetime',
    ];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(TagihanSiswa::class, 'tagihan_id', 'id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_kasir', 'id');
    }
}
