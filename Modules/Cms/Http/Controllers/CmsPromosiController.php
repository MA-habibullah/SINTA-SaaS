<?php

namespace Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Cms\Entities\CmsPromotion;
use Modules\Core\Entities\Tenant;

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
            'hero'         => $promotions->where('section_key', 'hero')->values(),
            'pricing'      => $promotions->where('section_key', 'pricing')->values(),
            'features'     => $promotions->where('section_key', 'features')->values(),
            'faq'          => $promotions->where('section_key', 'faq')->values(),
            'testimonials' => $promotions->where('section_key', 'testimonials')->values(),
            'contact'      => $promotions->where('section_key', 'contact')->values(),
            'cta'          => $promotions->where('section_key', 'cta')->values(),
        ];

        $stats = [
            'totalItems'  => $promotions->count(),
            'activeItems' => $promotions->where('is_active', true)->count(),
            'heroCount'   => $grouped['hero']->count(),
            'pricingCount'=> $grouped['pricing']->count(),
            'featureCount'=> $grouped['features']->count(),
            'faqCount'    => $grouped['faq']->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'data'       => $promotions,
                'grouped'    => $grouped,
                'stats'      => $stats,
            ]);
        }

        return Inertia::render('Cms/Promosi/Index', [
            'promotions' => $promotions,
            'grouped'    => $grouped,
            'stats'      => $stats,
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
            'section_key'  => 'required|string|in:hero,pricing,features,faq,testimonials,contact,cta',
            'title'        => 'required|string|max:255',
            'subtitle'     => 'nullable|string',
            'content'      => 'nullable|string',
            'content_json' => 'nullable|array',
            'badge_text'   => 'nullable|string|max:100',
            'icon_class'   => 'nullable|string|max:100',
            'image_url'    => 'nullable|string',
            'cta_text'     => 'nullable|string|max:100',
            'cta_link'     => 'nullable|string|max:255',
            'is_active'    => 'nullable|boolean',
            'order_num'    => 'nullable|integer|min:0',
        ]);

        $item = CmsPromotion::create([
            'id'           => Str::uuid()->toString(),
            'section_key'  => $validated['section_key'],
            'title'        => trim($validated['title']),
            'subtitle'     => $validated['subtitle'] ?? null,
            'content'      => $validated['content'] ?? null,
            'content_json' => $validated['content_json'] ?? null,
            'badge_text'   => $validated['badge_text'] ?? null,
            'icon_class'   => $validated['icon_class'] ?? 'bi bi-star',
            'image_url'    => $validated['image_url'] ?? null,
            'cta_text'     => $validated['cta_text'] ?? null,
            'cta_link'     => $validated['cta_link'] ?? null,
            'is_active'    => $validated['is_active'] ?? true,
            'order_num'    => (int) ($validated['order_num'] ?? 0),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konten promosi berhasil ditambahkan.',
                'data'    => $item,
            ], 201);
        }

        return back()->with('success', 'Konten promosi berhasil ditambahkan.');
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
            'section_key'  => 'required|string|in:hero,pricing,features,faq,testimonials,contact,cta',
            'title'        => 'required|string|max:255',
            'subtitle'     => 'nullable|string',
            'content'      => 'nullable|string',
            'content_json' => 'nullable|array',
            'badge_text'   => 'nullable|string|max:100',
            'icon_class'   => 'nullable|string|max:100',
            'image_url'    => 'nullable|string',
            'cta_text'     => 'nullable|string|max:100',
            'cta_link'     => 'nullable|string|max:255',
            'is_active'    => 'nullable|boolean',
            'order_num'    => 'nullable|integer|min:0',
        ]);

        $item->update([
            'section_key'  => $validated['section_key'],
            'title'        => trim($validated['title']),
            'subtitle'     => $validated['subtitle'] ?? null,
            'content'      => $validated['content'] ?? null,
            'content_json' => $validated['content_json'] ?? null,
            'badge_text'   => $validated['badge_text'] ?? null,
            'icon_class'   => $validated['icon_class'] ?? $item->icon_class,
            'image_url'    => $validated['image_url'] ?? null,
            'cta_text'     => $validated['cta_text'] ?? null,
            'cta_link'     => $validated['cta_link'] ?? null,
            'is_active'    => $validated['is_active'] ?? $item->is_active,
            'order_num'    => (int) ($validated['order_num'] ?? $item->order_num),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konten promosi berhasil diperbarui.',
                'data'    => $item,
            ]);
        }

        return back()->with('success', 'Konten promosi berhasil diperbarui.');
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
