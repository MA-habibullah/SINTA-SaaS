<?php

namespace Modules\Perpustakaan\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sirkulasi extends BaseTenantModel
{
    protected $table = 'perpustakaan.perpus_sirkulasi';

    protected $fillable = [
        'id',
        'tenant_id',
        'nomor_transaksi',
        'buku_id',
        'peminjam_type', // 'siswa', 'gtk'
        'peminjam_id',
        'tanggal_pinjam',
        'tanggal_harus_kembali',
        'tanggal_kembali_aktual',
        'status_sirkulasi', // 'Dipinjam', 'Kembali', 'Terlambat', 'Hilang'
        'denda_keterlambatan',
        'status_denda', // 'Lunas', 'Belum Lunas', 'Nihil'
        'petugas_peminjaman',
        'petugas_pengembalian',
        'catatan',
    ];

    protected $casts = [
        'tanggal_pinjam'         => 'date',
        'tanggal_harus_kembali'  => 'date',
        'tanggal_kembali_aktual' => 'date',
        'denda_keterlambatan'    => 'decimal:2',
    ];

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'buku_id', 'id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'peminjam_id', 'id');
    }
}
