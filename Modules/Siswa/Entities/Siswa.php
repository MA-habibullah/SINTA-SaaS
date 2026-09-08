<?php

namespace Modules\Siswa\Entities;

use Modules\Core\Entities\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Siswa extends BaseTenantModel
{
    protected $table = 'siswa.siswa';

    protected $fillable = [
        'id',
        'tenant_id',
        // Identitas Kependudukan
        'nisn',
        'nis',
        'nik',
        'no_kk',
        'nama_lengkap',
        'nama_panggilan',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'kewarganegaraan',
        'bahasa_sehari_hari',
        // Fisik & Seragam
        'ukuran_seragam_sekolah',
        'ukuran_seragam_olahraga',
        // Alamat & Kontak
        'alamat',
        'alamat_domisili',
        'rt',
        'rw',
        'kode_pos',
        'id_provinsi',
        'id_kota',
        'id_kecamatan',
        'id_kelurahan',
        'status_tinggal',
        'tinggal_dengan',
        'no_telepon_rumah',
        'no_hp',
        'email',
        'no_telepon_orang_tua',
        // Data Keluarga & Bantuan
        'anak_ke',
        'jumlah_saudara',
        'saudara_tiri',
        'saudara_angkat',
        'status_anak',
        'jarak_rumah',
        'transportasi',
        'penerima_kps',
        'punya_kip',
        'layak_kip',
        'no_kip',
        'alasan_layak',
        // Akademik & Status
        'kelas_saat_ini',
        'jurusan',
        'angkatan',
        'tahun_masuk',
        'tahun_lulus',
        'status_siswa',
        'password',
        'is_first_login',
        'foto_url',
        'is_active',
    ];

    protected $casts = [
        'tanggal_lahir'    => 'date',
        'anak_ke'          => 'integer',
        'jumlah_saudara'   => 'integer',
        'saudara_tiri'     => 'integer',
        'saudara_angkat'   => 'integer',
        'jarak_rumah'      => 'integer',
        'id_provinsi'      => 'integer',
        'id_kota'          => 'integer',
        'id_kecamatan'     => 'integer',
        'id_kelurahan'     => 'integer',
        'penerima_kps'     => 'boolean',
        'punya_kip'        => 'boolean',
        'layak_kip'        => 'boolean',
        'is_first_login'   => 'boolean',
        'is_active'        => 'boolean',
    ];

    protected $appends = [
        'persentase_kelengkapan',
        'kelengkapan_step',
    ];

    /**
     * Hitung Persentase Kelengkapan Biodata Siswa berdasarkan 5 Step Wizard (0 - 100%)
     */
    public function getPersentaseKelengkapanAttribute(): int
    {
        $stepScores = $this->getKelengkapanStepAttribute();
        return (int) round(array_sum($stepScores));
    }

    /**
     * Breakdown Persentase Kelengkapan per Step 1 sampai Step 5 (Masing-masing bernilai maksimal 20%)
     */
    public function getKelengkapanStepAttribute(): array
    {
        // 1. Step 1: Identitas & Akademik (Maksimal 20%)
        $step1Fields = [
            $this->nama_lengkap,
            $this->nisn,
            $this->nis,
            $this->nik,
            $this->jenis_kelamin,
            $this->tempat_lahir,
            $this->tanggal_lahir,
            $this->agama,
            $this->kewarganegaraan,
            $this->kelas_saat_ini,
        ];
        $s1Filled = 0;
        foreach ($step1Fields as $val) {
            if (!empty($val) && trim((string)$val) !== '') $s1Filled++;
        }
        $step1Score = count($step1Fields) > 0 ? ($s1Filled / count($step1Fields)) * 20 : 0;

        // 2. Step 2: Alamat & Kontak (Maksimal 20%)
        $step2Fields = [
            $this->alamat ?? $this->alamat_kk,
            $this->alamat_domisili ?? $this->alamat,
            $this->rt,
            $this->rw,
            $this->kode_pos,
            $this->id_provinsi,
            $this->status_tinggal,
            $this->email,
            $this->no_hp ?? $this->no_telepon_siswa,
        ];
        $s2Filled = 0;
        foreach ($step2Fields as $val) {
            if (!empty($val) && trim((string)$val) !== '') $s2Filled++;
        }
        $step2Score = count($step2Fields) > 0 ? ($s2Filled / count($step2Fields)) * 20 : 0;

        // 3. Step 3: Fisik & Kesejahteraan (Maksimal 20%)
        $fisik = $this->relationLoaded('fisikKesehatan') ? $this->fisikKesehatan : null;
        $step3Fields = [
            $fisik?->tinggi_badan ?? $this->tinggi_badan,
            $fisik?->berat_badan ?? $this->berat_badan,
            $fisik?->lingkar_kepala ?? $this->lingkar_kepala,
            $fisik?->golongan_darah ?? $this->golongan_darah,
            $this->anak_ke,
            $this->jumlah_saudara !== null ? (string)$this->jumlah_saudara : null,
            $fisik?->disabilitas ?? $this->kelainan_jasmani,
            $this->jarak_rumah,
            $this->transportasi,
        ];
        $s3Filled = 0;
        foreach ($step3Fields as $val) {
            if (!empty($val) && trim((string)$val) !== '') $s3Filled++;
        }
        $step3Score = count($step3Fields) > 0 ? ($s3Filled / count($step3Fields)) * 20 : 0;

        // 4. Step 4: Orang Tua / Wali (Maksimal 20%)
        $ortuList = $this->relationLoaded('orangTua') ? $this->orangTua : null;
        $ibu = $ortuList ? $ortuList->firstWhere('hubungan', 'Ibu') : null;
        $ayah = $ortuList ? $ortuList->firstWhere('hubungan', 'Ayah') : null;

        $step4Fields = [
            $ibu?->nama_lengkap ?? $ayah?->nama_lengkap ?? $this->nama_ibu ?? $this->nama_ayah,
            $ibu?->nik ?? $ayah?->nik ?? $this->nik_ibu ?? $this->nik_ayah,
            $ibu?->tempat_lahir ?? $ayah?->tempat_lahir ?? $this->tempat_lahir_ibu ?? $this->tempat_lahir_ayah,
            $ibu?->tanggal_lahir ?? $ayah?->tanggal_lahir ?? $this->tanggal_lahir_ibu ?? $this->tanggal_lahir_ayah,
            $ibu?->pendidikan ?? $ayah?->pendidikan ?? $this->pendidikan_ibu ?? $this->pendidikan_ayah,
            $ibu?->pekerjaan ?? $ayah?->pekerjaan ?? $this->pekerjaan_ibu ?? $this->pekerjaan_ayah,
            $ibu?->penghasilan ?? $ayah?->penghasilan ?? $this->penghasilan_ibu ?? $this->penghasilan_ayah,
            $ibu?->agama ?? $ayah?->agama ?? $this->agama_ibu ?? $this->agama_ayah,
        ];
        $s4Filled = 0;
        foreach ($step4Fields as $val) {
            if (!empty($val) && trim((string)$val) !== '') $s4Filled++;
        }
        $step4Score = count($step4Fields) > 0 ? ($s4Filled / count($step4Fields)) * 20 : 0;

        // 5. Step 5: Registrasi & Riwayat Masuk (Maksimal 20%)
        $reg = $this->relationLoaded('registrasi') ? $this->registrasi : null;
        $step5Fields = [
            $reg?->jenis_pendaftaran ?? $this->status_siswa ?? $this->jenis_pendaftaran,
            $reg?->tanggal_masuk ?? $this->tahun_masuk ?? $this->tanggal_masuk,
            $reg?->asal_sekolah ?? $this->sekolah_asal ?? $this->asal_sekolah,
            $this->hobi ?? $reg?->hobi,
        ];
        $s5Filled = 0;
        foreach ($step5Fields as $val) {
            if (!empty($val) && trim((string)$val) !== '') $s5Filled++;
        }
        $step5Score = count($step5Fields) > 0 ? ($s5Filled / count($step5Fields)) * 20 : 0;

        return [
            'step1' => round($step1Score, 1),
            'step2' => round($step2Score, 1),
            'step3' => round($step3Score, 1),
            'step4' => round($step4Score, 1),
            'step5' => round($step5Score, 1),
        ];
    }

    public function orangTua(): HasMany
    {
        return $this->hasMany(SiswaOrangTua::class, 'siswa_id', 'id');
    }

    public function registrasi(): HasOne
    {
        return $this->hasOne(SiswaRegistrasi::class, 'siswa_id', 'id');
    }

    public function fisikKesehatan(): HasOne
    {
        return $this->hasOne(SiswaFisikKesehatan::class, 'siswa_id', 'id');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(SiswaDokumen::class, 'siswa_id', 'id');
    }

    public function mutasi(): HasMany
    {
        return $this->hasMany(SiswaMutasi::class, 'siswa_id', 'id');
    }

    public function prestasi(): HasMany
    {
        return $this->hasMany(SiswaPrestasi::class, 'siswa_id', 'id');
    }

    public function riwayatKenaikanKelas(): HasMany
    {
        return $this->hasMany(RiwayatKenaikanKelas::class, 'siswa_id', 'id');
    }
}

