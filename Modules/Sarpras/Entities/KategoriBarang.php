<?php

namespace Modules\Sarpras\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriBarang extends BaseTenantModel
{
    protected $table = 'sarpras.kategori_barang';

    protected $fillable = [
        'id',
        'tenant_id',
        'kode_kategori',
        'nama_kategori',
        'tipe',          // 'Barang Modal', 'Barang Habis Pakai'
        'keterangan',
    ];
}
