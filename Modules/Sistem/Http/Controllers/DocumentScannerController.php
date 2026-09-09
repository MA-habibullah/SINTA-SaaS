<?php

namespace Modules\Sistem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentScannerController extends Controller
{
    /**
     * Menampilkan antarmuka Pemindai & Kompresor Dokumen (AeroScan).
     *
     * @param Request $request
     * @return InertiaResponse|JsonResponse
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $userRole = 'user';
        if ($user) {
            if (is_object($user->role) && isset($user->role->name)) {
                $userRole = $user->role->name;
            } elseif (is_string($user->role)) {
                $userRole = $user->role;
            }
        }

        $presets = [
            'standard' => [
                'name' => 'Standar (1200 x 1842)',
                'width' => 1200,
                'height' => 1842,
                'quality' => 0.85,
            ],
            'hd' => [
                'name' => 'High-Definition HD (1600 x 2456)',
                'width' => 1600,
                'height' => 2456,
                'quality' => 0.90,
            ],
            'super-hd' => [
                'name' => 'Super-HD Ultra (2400 x 3684)',
                'width' => 2400,
                'height' => 3684,
                'quality' => 0.95,
            ],
        ];

        $data = [
            'user_role' => $userRole,
            'presets'   => $presets,
            'ocr_languages' => [
                ['code' => 'ind', 'name' => 'Bahasa Indonesia (ind)'],
                ['code' => 'eng', 'name' => 'Bahasa Inggris (eng)'],
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        return Inertia::render('Sistem/DocumentScanner/Index', $data);
    }

    /**
     * Endpoint opsional untuk menyimpan hasil pindaian PDF ke sistem penyimpanan tenant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveScannedPdf(Request $request): JsonResponse
    {
        $request->validate([
            'file'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:51200', // Max 50MB
            'filename' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
        ]);

        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id ?? 'global';
            $file = $request->file('file');
            
            $rawFilename = $request->input('filename', 'scanned_doc_' . time());
            $sanitized = Str::slug(pathinfo($rawFilename, PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension() ?: 'pdf';
            $finalFilename = $sanitized . '_' . time() . '.' . $extension;

            $path = "tenants/{$tenantId}/documents/scanned/{$finalFilename}";
            Storage::disk('public')->put($path, file_get_contents($file));

            $url = Storage::disk('public')->url($path);

            return response()->json([
                'success'  => true,
                'message'  => 'Dokumen hasil scan berhasil disimpan ke penyimpanan sekolah.',
                'filename' => $finalFilename,
                'path'     => $path,
                'url'      => $url,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal menyimpan dokumen: ' . $e->getMessage(),
            ], 500);
        }
    }
}
