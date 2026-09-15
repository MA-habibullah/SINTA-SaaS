<?php

namespace Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Cms\Entities\CmsPromotion;
use Modules\Core\Services\SecurityPayloadService;

class CmsPromosiController extends Controller
{
    /**
     * Helper to verify Super Admin
     */
    private function checkIsSuperAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $userRole = is_object($user->role) ? ($user->role->nama_role ?? '') : ($user->role ?? '');
        return ($user->isSuperAdmin() 
            || $userRole === 'super_admin' 
            || $user->tenant_id === '00000000-0000-0000-0000-000000000000' 
            || session('role') === 'super_admin' 
            || session('tenant_id') === '00000000-0000-0000-0000-000000000000');
    }

    /**
     * Tampilkan Halaman Manajemen CMS Promosi & Landing Page Publik
     * GET /super-admin/cms-promosi
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        if (!$this->checkIsSuperAdmin()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki wewenang Super Admin untuk mengelola CMS Promosi.');
        }

        $promotions = CmsPromotion::orderBy('order_num', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = [
            'nav_menu'     => $promotions->where('section_key', 'nav_menu')->values(),
            'hero'         => $promotions->where('section_key', 'hero')->values(),
            'features'     => $promotions->where('section_key', 'features')->values(),
            'benefits'     => $promotions->where('section_key', 'benefits')->values(),
            'pricing'      => $promotions->where('section_key', 'pricing')->values(),
            'faq'          => $promotions->where('section_key', 'faq')->values(),
            'testimonials' => $promotions->where('section_key', 'testimonials')->values(),
            'contact'      => $promotions->where('section_key', 'contact')->values(),
            'cta'          => $promotions->where('section_key', 'cta')->values(),
            'stats'        => $promotions->where('section_key', 'stats')->values(),
        ];

        $stats = [
            'totalItems'    => $promotions->count(),
            'activeItems'   => $promotions->where('is_active', true)->count(),
            'navCount'      => $grouped['nav_menu']->count(),
            'heroCount'     => $grouped['hero']->count(),
            'featureCount'  => $grouped['features']->count(),
            'benefitsCount' => $grouped['benefits']->count(),
            'pricingCount'  => $grouped['pricing']->count(),
            'faqCount'      => $grouped['faq']->count(),
        ];

        // Daftar kategori section untuk dropdown SearchableSelect
        $sectionCategories = [
            ['id' => 'nav_menu',     'nama' => 'Menu Navigasi Header',      'subLabel' => 'Menu & Tautan pada Bar Navigasi Publik'],
            ['id' => 'hero',         'nama' => 'Hero Banner Utama',         'subLabel' => 'Headline, Subtitle, & Tombol CTA Utama'],
            ['id' => 'features',     'nama' => 'Fitur Unggulan (Modul)',    'subLabel' => 'Showcase 16 Modul Aplikasi SINTA'],
            ['id' => 'benefits',     'nama' => 'Keuntungan Aplikasi',       'subLabel' => 'Keunggulan Arsitektur & Manfaat Sekolah'],
            ['id' => 'pricing',      'nama' => 'Paket & Free Trial',        'subLabel' => 'Paket Berlangganan & Penawaran Trial'],
            ['id' => 'faq',          'nama' => 'FAQ (Tanya Jawab)',         'subLabel' => 'Pertanyaan yang Sering Diajukan'],
            ['id' => 'testimonials', 'nama' => 'Testimoni Sekolah',        'subLabel' => 'Ulasan & Pengalaman Kepala Sekolah/Guru'],
            ['id' => 'cta',          'nama' => 'Promotional CTA Banner',    'subLabel' => 'Banner Ajakan Daftar di Bawah Halaman'],
            ['id' => 'contact',      'nama' => 'Kontak & Bantuan',          'subLabel' => 'Informasi Kontak Sales & Helpdesk'],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'data'              => SecurityPayloadService::sanitize($promotions),
                'grouped'           => SecurityPayloadService::sanitize($grouped),
                'stats'             => $stats,
                'sectionCategories' => $sectionCategories,
            ]);
        }

        return Inertia::render('Cms/Promosi/Index', [
            'promotions'        => SecurityPayloadService::sanitize($promotions),
            'grouped'           => SecurityPayloadService::sanitize($grouped),
            'stats'             => $stats,
            'sectionCategories' => $sectionCategories,
        ]);
    }

    /**
     * Simpan Konten Promosi Baru
     * POST /super-admin/cms-promosi
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if (!$this->checkIsSuperAdmin()) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'section_key'  => 'required|string|in:nav_menu,hero,features,benefits,pricing,faq,testimonials,contact,cta,stats',
            'title'        => 'required|string|max:255',
            'subtitle'     => 'nullable|string',
            'content'      => 'nullable|string',
            'content_json' => 'nullable',
            'badge_text'   => 'nullable|string|max:100',
            'icon_class'   => 'nullable|string|max:100',
            'image_url'    => 'nullable|string',
            'cta_text'     => 'nullable|string|max:100',
            'cta_link'     => 'nullable|string|max:255',
            'is_active'    => 'nullable|boolean',
            'order_num'    => 'nullable|integer|min:0',
        ]);

        $contentJson = $validated['content_json'] ?? null;
        if (is_string($contentJson)) {
            $decoded = json_decode($contentJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $contentJson = $decoded;
            }
        }

        $item = CmsPromotion::create([
            'id'           => Str::uuid()->toString(),
            'section_key'  => $validated['section_key'],
            'title'        => trim($validated['title']),
            'subtitle'     => $validated['subtitle'] ?? null,
            'content'      => $validated['content'] ?? null,
            'content_json' => $contentJson,
            'badge_text'   => $validated['badge_text'] ?? null,
            'icon_class'   => $validated['icon_class'] ?? 'bi bi-stars',
            'image_url'    => $validated['image_url'] ?? null,
            'cta_text'     => $validated['cta_text'] ?? null,
            'cta_link'     => $validated['cta_link'] ?? null,
            'is_active'    => $validated['is_active'] ?? true,
            'order_num'    => (int) ($validated['order_num'] ?? 0),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konten promosi landing page berhasil ditambahkan.',
                'data'    => $item,
            ], 201);
        }

        return back()->with('success', 'Konten promosi landing page berhasil ditambahkan.');
    }

    /**
     * Perbarui Konten Promosi
     * PUT /super-admin/cms-promosi/{id}
     */
    public function update(Request $request, string $id): JsonResponse|RedirectResponse
    {
        if (!$this->checkIsSuperAdmin()) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $item = CmsPromotion::findOrFail($id);

        $validated = $request->validate([
            'section_key'  => 'required|string|in:nav_menu,hero,features,benefits,pricing,faq,testimonials,contact,cta,stats',
            'title'        => 'required|string|max:255',
            'subtitle'     => 'nullable|string',
            'content'      => 'nullable|string',
            'content_json' => 'nullable',
            'badge_text'   => 'nullable|string|max:100',
            'icon_class'   => 'nullable|string|max:100',
            'image_url'    => 'nullable|string',
            'cta_text'     => 'nullable|string|max:100',
            'cta_link'     => 'nullable|string|max:255',
            'is_active'    => 'nullable|boolean',
            'order_num'    => 'nullable|integer|min:0',
        ]);

        $contentJson = $validated['content_json'] ?? null;
        if (is_string($contentJson)) {
            $decoded = json_decode($contentJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $contentJson = $decoded;
            }
        }

        $item->update([
            'section_key'  => $validated['section_key'],
            'title'        => trim($validated['title']),
            'subtitle'     => $validated['subtitle'] ?? null,
            'content'      => $validated['content'] ?? null,
            'content_json' => $contentJson ?? $item->content_json,
            'badge_text'   => $validated['badge_text'] ?? null,
            'icon_class'   => $validated['icon_class'] ?? $item->icon_class,
            'image_url'    => $validated['image_url'] ?? null,
            'cta_text'     => $validated['cta_text'] ?? null,
            'cta_link'     => $validated['cta_link'] ?? null,
            'is_active'    => isset($validated['is_active']) ? (bool)$validated['is_active'] : $item->is_active,
            'order_num'    => isset($validated['order_num']) ? (int)$validated['order_num'] : $item->order_num,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konten promosi landing page berhasil diperbarui.',
                'data'    => $item,
            ]);
        }

        return back()->with('success', 'Konten promosi landing page berhasil diperbarui.');
    }

    /**
     * Urutkan Item Konten Promosi (Reorder)
     * POST /super-admin/cms-promosi/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        if (!$this->checkIsSuperAdmin()) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'items'         => 'required|array',
            'items.*.id'    => 'required|uuid',
            'items.*.order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $it) {
                CmsPromotion::where('id', $it['id'])->update(['order_num' => $it['order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Urutan konten landing page berhasil diperbarui.',
        ]);
    }

    /**
     * Toggle Status Aktif Konten Promosi
     * POST /super-admin/cms-promosi/{id}/toggle
     */
    public function toggle(Request $request, string $id): JsonResponse
    {
        if (!$this->checkIsSuperAdmin()) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $item = CmsPromotion::findOrFail($id);
        $item->is_active = !$item->is_active;
        $item->save();

        return response()->json([
            'success'   => true,
            'message'   => "Status konten '{$item->title}' berhasil diubah.",
            'is_active' => $item->is_active,
        ]);
    }

    /**
     * Hapus Konten Promosi
     * DELETE /super-admin/cms-promosi/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        if (!$this->checkIsSuperAdmin()) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $item = CmsPromotion::findOrFail($id);
        $title = $item->title;
        $item->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Konten promosi '{$title}' berhasil dihapus.",
            ]);
        }

        return back()->with('success', "Konten promosi '{$title}' berhasil dihapus.");
    }
}
