<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TenantStorageGuard
{
    /**
     * Memeriksa sisa kuota storage sekolah sebelum mengizinkan proses upload berkas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            if ($request->hasFile('file') || $request->hasFile('foto') || $request->hasFile('lampiran') || $request->hasFile('dokumen') || $request->hasFile('files')) {
                $tenantId = session('tenant_id') ?? (Auth::user()?->tenant_id);

                if (!empty($tenantId)) {
                    $tenant = DB::table('core.tenants')
                        ->where('id', $tenantId)
                        ->first(['storage_limit_mb', 'storage_used_bytes', 'paket_aktif']);

                    if ($tenant) {
                        $limitBytes = ($tenant->storage_limit_mb > 0)
                            ? ((int)$tenant->storage_limit_mb * 1024 * 1024)
                            : (1024 * 1024 * 1024); // 1 GB default

                        $usedBytes = (int)($tenant->storage_used_bytes ?? 0);

                        // Hitung ukuran total file yang sedang diupload
                        $incomingBytes = 0;
                        foreach ($request->allFiles() as $fileItem) {
                            if (is_array($fileItem)) {
                                foreach ($fileItem as $f) {
                                    $incomingBytes += $f->getSize();
                                }
                            } elseif ($fileItem) {
                                $incomingBytes += $fileItem->getSize();
                            }
                        }

                        if (($usedBytes + $incomingBytes) > $limitBytes) {
                            if ($request->expectsJson()) {
                                return response()->json([
                                    'success' => false,
                                    'error'   => 'Kapasitas penyimpanan sekolah penuh. Silakan upgrade paket penyimpanan Anda.',
                                ], 422);
                            }

                            return back()->withErrors([
                                'storage' => 'Kapasitas penyimpanan sekolah Anda telah mencapai batas maksimal.'
                            ]);
                        }
                    }
                }
            }
        }

        return $next($request);
    }
}
