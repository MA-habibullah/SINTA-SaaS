<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SiswaMutasi extends BaseTenantModel
{
    protected $table = 'siswa.registrasi';

    protected $fillable = [
        'id',
        'tenant_id',
        'siswa_id',
        'jenis_pendaftaran',
        'tanggal_masuk',
        'sekolah_asal_mutasi',
        'pindah_dari_tingkat',
        'pindah_no_surat',
        'keluar_karena',
        'tanggal_keluar',
        'alasan_keluar',
        'sekolah_tujuan',
        'nomor_skp',
        'tingkat_ditinggalkan',
        'catatan',
    ];

    protected $casts = [
        'tanggal_masuk'  => 'date',
        'tanggal_keluar' => 'date',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    protected $appends = [
        'jenis_mutasi',
        'tanggal_mutasi',
        'sekolah_asal_tujuan',
        'nomor_surat_mutasi',
        'alasan_mutasi',
    ];

    public function getJenisMutasiAttribute(): string
    {
        return !empty($this->tanggal_keluar) || !empty($this->keluar_karena) ? 'keluar' : 'masuk';
    }

    public function getTanggalMutasiAttribute(): ?string
    {
        return $this->tanggal_keluar ? $this->tanggal_keluar->format('Y-m-d') : ($this->tanggal_masuk ? $this->tanggal_masuk->format('Y-m-d') : null);
    }

    public function getSekolahAsalTujuanAttribute(): ?string
    {
        return $this->sekolah_tujuan ?: $this->sekolah_asal_mutasi;
    }

    public function getNomorSuratMutasiAttribute(): ?string
    {
        return $this->nomor_skp ?: $this->pindah_no_surat;
    }

    public function getAlasanMutasiAttribute(): ?string
    {
        return $this->alasan_keluar ?: $this->catatan;
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}
