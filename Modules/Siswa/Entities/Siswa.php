<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends BaseTenantModel
{
    protected $table = 'siswa.siswa';

    protected $fillable = [
        'id',
        'tenant_id',
        'nisn',
        'nis',
        'nik',
        'nama_lengkap',
        'nama_panggilan',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'kewarganegaraan',
        'anak_ke',
        'jumlah_saudara_kandung',
        'jumlah_saudara_tiri',
        'jumlah_saudara_angkat',
        'status_dalam_keluarga',
        'bahasa_sehari_hari',
        'golongan_darah',
        'penyakit_pernah_diderita',
        'kelainan_jasmani',
        'tinggi_badan_cm',
        'berat_badan_kg',
        'alamat_tinggal',
        'rt_rw',
        'kelurahan_desa',
        'kecamatan',
        'kabupaten_kota',
        'provinsi',
        'kode_pos',
        'tinggal_bersama',
        'jarak_ke_sekolah_km',
        'transportasi_ke_sekolah',
        'no_telepon_rumah',
        'no_hp',
        'email',
        'penerima_kps_kpip',
        'no_kps_kpip',
        'nama_ayah',
        'nik_ayah',
        'tahun_lahir_ayah',
        'pendidikan_ayah',
        'pekerjaan_ayah',
        'penghasilan_bulanan_ayah',
        'kebutuhan_khusus_ayah',
        'no_hp_ayah',
        'nama_ibu',
        'nik_ibu',
        'tahun_lahir_ibu',
        'pendidikan_ibu',
        'pekerjaan_ibu',
        'penghasilan_bulanan_ibu',
        'kebutuhan_khusus_ibu',
        'no_hp_ibu',
        'nama_wali',
        'nik_wali',
        'tahun_lahir_wali',
        'pendidikan_wali',
        'pekerjaan_wali',
        'penghasilan_bulanan_wali',
        'no_hp_wali',
        'hubungan_wali',
        'asal_sekolah',
        'no_peserta_un_smp',
        'no_ijazah_smp',
        'no_skhun_smp',
        'tanggal_diterima',
        'kelas_diterima',
        'jurusan_diterima',
        'kelas_saat_ini',
        'jurusan',
        'angkatan',
        'status_siswa',
        'status_buku_induk',
        'foto_url',
        'is_active',
    ];

    protected $casts = [
        'tanggal_lahir'            => 'date',
        'tanggal_diterima'         => 'date',
        'anak_ke'                  => 'integer',
        'jumlah_saudara_kandung'   => 'integer',
        'tinggi_badan_cm'          => 'integer',
        'berat_badan_kg'           => 'integer',
        'jarak_ke_sekolah_km'      => 'decimal:2',
        'is_active'                => 'boolean',
    ];

    public function mutasi(): HasMany
    {
        return $this->hasMany(SiswaMutasi::class, 'siswa_id', 'id');
    }

    public function prestasi(): HasMany
    {
        return $this->hasMany(SiswaPrestasi::class, 'siswa_id', 'id');
    }
}
