<?php

namespace Modules\Siswa\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = session('tenant_id') ?? $this->user()?->tenant_id;

        return [
            'nisn' => [
                'required', 'string', 'max:20',
                Rule::unique('siswa.buku_induk', 'nisn')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'nik' => [
                'required', 'string', 'max:20',
                Rule::unique('siswa.buku_induk', 'nik')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'nama_lengkap'   => 'required|string|max:150',
            'jenis_kelamin'  => 'required|in:L,P',
            'tempat_lahir'   => 'required|string|max:100',
            'tanggal_lahir'  => 'required|date',
            'agama'          => 'required|string|max:30',
            'alamat_jalan'   => 'nullable|string',
            'kelas_id'       => 'nullable|uuid',
            'status_aktif'   => 'boolean',
            'tahun_masuk'    => 'nullable|string|max:10',
        ];
    }
}
