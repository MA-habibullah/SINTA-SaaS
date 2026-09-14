<?php

namespace Modules\Keuangan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Core\Entities\User;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiPembayaran extends BaseTenantModel
{
    protected $table = 'keuangan.transaksi_spp_pembayaran';

    protected $fillable = [
        'id',
        'tenant_id',
        'nomor_transaksi',
        'tagihan_id',
        'siswa_id',
        'nominal_bayar',
        'metode_pembayaran', // 'Tunai', 'Transfer Bank', 'QRIS', 'Midtrans VA'
        'kas_id',
        'user_id_kasir',
        'tanggal_bayar',
        'status_transaksi', // 'SUCCESS', 'VOID'
        'alasan_void',
        'void_by',
        'void_at',
        'bukti_transfer_url',
        'catatan',
        'is_active',
    ];

    protected $casts = [
        'nominal_bayar' => 'decimal:2',
        'tanggal_bayar' => 'datetime',
        'void_at'       => 'datetime',
        'is_active'     => 'boolean',
    ];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(TagihanSiswa::class, 'tagihan_id', 'id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function kas(): BelongsTo
    {
        return $this->belongsTo(KasBank::class, 'kas_id', 'id');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id_kasir', 'id');
    }

    public function voidUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'void_by', 'id');
    }
}
