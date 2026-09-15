<?php

namespace Modules\Absensi\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Modules\Siswa\Entities\Siswa;
use Modules\Kepegawaian\Entities\Gtk;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiFraudLog extends BaseTenantModel
{
    protected $table = 'absensi.presensi_fraud_logs';

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'siswa_id',
        'ptk_id',
        'tipe_pengguna',
        'nama_pelaku',
        'identifier',
        'latitude',
        'longitude',
        'jarak_meter',
        'akurasi_meter',
        'fraud_type',
        'fraud_reason',
        'fraud_details',
        'device_info',
        'ip_address',
        'is_blocked',
    ];

    protected $casts = [
        'latitude'      => 'float',
        'longitude'     => 'float',
        'jarak_meter'   => 'integer',
        'akurasi_meter' => 'float',
        'fraud_details' => 'array',
        'is_blocked'    => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }

    public function gtk(): BelongsTo
    {
        return $this->belongsTo(Gtk::class, 'ptk_id', 'id');
    }
}
