<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\Tenant;

class Sirkulasi extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_sirkulasi';

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_perpus_sirkulasi',
        'nomor_transaksi',
        'buku_id',
        'eksemplar_id',
        'peminjam_type',
        'peminjam_id',
        'nama_peminjam',
        'nomor_identitas',
        'kelas_unit',
        'tanggal_pinjam',
        'tanggal_harus_kembali',
        'tanggal_kembali_aktual',
        'jumlah_perpanjangan',
        'status_sirkulasi',
        'tarif_denda_harian',
        'hari_keterlambatan',
        'denda_keterlambatan',
        'denda_dibayar',
        'status_denda',
        'catatan',
        'petugas_peminjaman',
        'petugas_pengembalian',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tanggal_pinjam'         => 'date',
        'tanggal_harus_kembali'  => 'date',
        'tanggal_kembali_aktual' => 'date',
        'jumlah_perpanjangan'    => 'integer',
        'hari_keterlambatan'     => 'integer',
        'tarif_denda_harian'     => 'decimal:2',
        'denda_keterlambatan'    => 'decimal:2',
        'denda_dibayar'          => 'decimal:2',
        'is_active'              => 'boolean',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'buku_id');
    }

    public function eksemplar(): BelongsTo
    {
        return $this->belongsTo(Eksemplar::class, 'eksemplar_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
