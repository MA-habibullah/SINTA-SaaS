<?php

namespace Modules\Cms\Entities;

use Modules\Core\Entities\BaseTenantModel;

class AgendaSekolah extends BaseTenantModel
{
    protected $table = 'sistem.agenda_sekolah';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'nama_agenda_sekolah',
        'kategori',
        'deskripsi',  // JSON string: {isi, tanggal_mulai, tanggal_selesai, waktu_mulai, waktu_selesai, lokasi, penanggung_jawab, visibilitas, target_roles, lampiran_url, lampiran_nama}
        'lampiran_url',
        'lampiran_nama',
        'lampiran_ukuran',
        'lampiran_tipe',
        'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'lampiran_ukuran' => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    protected $appends = [
        'isi',
        'tanggal_mulai',
        'tanggal_selesai',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi',
        'penanggung_jawab',
        'visibilitas',
        'lampiran_url',
        'lampiran_nama',
        'lampiran_ukuran',
        'lampiran_tipe',
        'detail',
    ];

    /**
     * Get parsed deskripsi JSON as array
     */
    public function getDetailAttribute(): array
    {
        if (empty($this->attributes['deskripsi'] ?? null)) return [];
        $decoded = json_decode($this->attributes['deskripsi'], true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getIsiAttribute(): ?string
    {
        return $this->detail['isi'] ?? null;
    }

    public function getTanggalMulaiAttribute(): ?string
    {
        return $this->detail['tanggal_mulai'] ?? null;
    }

    public function getTanggalSelesaiAttribute(): ?string
    {
        return $this->detail['tanggal_selesai'] ?? $this->detail['tanggal_mulai'] ?? null;
    }

    public function getWaktuMulaiAttribute(): ?string
    {
        return $this->detail['waktu_mulai'] ?? null;
    }

    public function getWaktuSelesaiAttribute(): ?string
    {
        return $this->detail['waktu_selesai'] ?? null;
    }

    public function getLokasiAttribute(): ?string
    {
        return $this->detail['lokasi'] ?? null;
    }

    public function getPenanggungJawabAttribute(): ?string
    {
        return $this->detail['penanggung_jawab'] ?? null;
    }

    public function getVisibilitasAttribute(): ?string
    {
        return $this->detail['visibilitas'] ?? 'public';
    }

    public function getLampiranUrlAttribute(): ?string
    {
        return $this->attributes['lampiran_url'] ?? $this->detail['lampiran_url'] ?? null;
    }

    public function getLampiranNamaAttribute(): ?string
    {
        return $this->attributes['lampiran_nama'] ?? $this->detail['lampiran_nama'] ?? null;
    }

    public function getLampiranUkuranAttribute(): ?int
    {
        $val = $this->attributes['lampiran_ukuran'] ?? $this->detail['lampiran_ukuran'] ?? null;
        return $val !== null ? (int) $val : null;
    }

    public function getLampiranTipeAttribute(): ?string
    {
        return $this->attributes['lampiran_tipe'] ?? $this->detail['lampiran_tipe'] ?? null;
    }
}
