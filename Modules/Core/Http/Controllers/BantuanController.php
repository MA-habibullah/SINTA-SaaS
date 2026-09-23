<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Core\Entities\Ticket;
use Modules\Core\Entities\TicketCategory;
use Modules\Core\Entities\TicketReply;
use Modules\Core\Entities\TicketCannedResponse;
use Modules\Core\Entities\TicketFaq;
use Modules\Core\Entities\FeatureRequest;
use Modules\Core\Entities\FeatureRequestVote;

class BantuanController extends Controller

{
    /**
     * Tampilan Utama Halaman Pusat Bantuan & Layanan Tiket
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $roleName = $user ? ($user->role->nama_role ?? 'user') : 'user';

        $categories = TicketCategory::where('is_active', true)->orderBy('id', 'asc')->get();

        // Hitung unread count
        if ($isSuperAdmin) {
            $unreadCount = Ticket::withoutTenant()->where('admin_unread', true)->count();
        } else {
            $unreadCount = Ticket::where('user_unread', true)->where('user_id', $user?->id)->count();
        }

        // Ambil data FAQ aktif
        $faqs = TicketFaq::with('category')->where('is_active', true)->orderBy('created_at', 'desc')->get();

        // Ambil canned responses jika Super Admin
        $cannedResponses = $isSuperAdmin ? TicketCannedResponse::orderBy('judul', 'asc')->get() : [];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'categories'       => $categories,
                    'unread_count'     => $unreadCount,
                    'is_super_admin'   => $isSuperAdmin,
                    'user_role'        => $roleName,
                    'faqs'             => $faqs,
                    'canned_responses' => $cannedResponses,
                    'allowed_tabs'     => \Modules\Core\Services\MenuService::getAllowedTabsForRoute($user, '/bantuan'),
                ]
            ]);
        }

        return Inertia::render('Core/Bantuan/Index', [
            'categories'      => $categories,
            'unreadCount'     => $unreadCount,
            'isSuperAdmin'    => $isSuperAdmin,
            'userRole'        => $roleName,
            'initialFaqs'     => $faqs,
            'cannedResponses' => $cannedResponses,
            'allowed_tabs'    => \Modules\Core\Services\MenuService::getAllowedTabsForRoute($user, '/bantuan'),
        ]);
    }

    /**
     * Mengambil daftar tiket dengan filter
     */
    public function getTickets(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        $status = $request->query('status');
        $categoryId = $request->query('category_id');
        $search = trim((string)$request->query('search', ''));

        if ($isSuperAdmin) {
            $query = Ticket::withoutTenant()
                ->with([
                    'category:id,nama_kategori',
                    'user:id,nama_lengkap,username,email',
                    'tenant:id,nama_sekolah,npsn'
                ]);
        } else {
            $query = Ticket::with([
                    'category:id,nama_kategori',
                    'user:id,nama_lengkap,username'
                ])
                ->where('user_id', $user?->id);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($categoryId)) {
            $query->where('category_id', (int)$categoryId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'ILIKE', "%{$search}%")
                  ->orWhere('nomor_tiket', 'ILIKE', "%{$search}%")
                  ->orWhere('deskripsi', 'ILIKE', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        // Map is_overdue attribute
        $tickets->getCollection()->transform(function ($ticket) {
            $ticket->is_overdue = $ticket->is_overdue;
            return $ticket;
        });

        return response()->json([
            'success' => true,
            'data'    => $tickets,
        ]);
    }

    /**
     * Mengambil detail percakapan tiket
     */
    public function getTicketDetail(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        if ($isSuperAdmin) {
            $ticket = Ticket::withoutTenant()
                ->with([
                    'category:id,nama_kategori,sla_hours',
                    'user:id,nama_lengkap,username,email,role_id',
                    'tenant:id,nama_sekolah,npsn',
                    'replies' => function ($q) {
                        $q->with('user:id,nama_lengkap,username')->orderBy('created_at', 'asc');
                    }
                ])
                ->find($id);

            if ($ticket && $ticket->admin_unread) {
                $ticket->admin_unread = false;
                $ticket->saveQuietly();
            }
        } else {
            $ticket = Ticket::with([
                    'category:id,nama_kategori,sla_hours',
                    'user:id,nama_lengkap,username',
                    'tenant:id,nama_sekolah',
                    'replies' => function ($q) {
                        $q->with('user:id,nama_lengkap,username')->orderBy('created_at', 'asc');
                    }
                ])
                ->where('user_id', $user?->id)
                ->find($id);

            if ($ticket && $ticket->user_unread) {
                $ticket->user_unread = false;
                $ticket->saveQuietly();
            }
        }

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan atau Anda tidak memiliki akses.',
            ], 404);
        }

        $ticket->is_overdue = $ticket->is_overdue;

        return response()->json([
            'success' => true,
            'data'    => $ticket,
        ]);
    }

    /**
     * Membuat tiket bantuan baru
     */
    public function storeTicket(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'judul'       => 'required|string|max:255',
            'category_id' => 'required|integer|exists:core.ticket_categories,id',
            'urgensi'     => 'required|string|in:Rendah,Sedang,Tinggi,Kritis',
            'deskripsi'   => 'required|string',
            'last_url'    => 'nullable|string|max:255',
            'lampiran'    => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:3072',
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        // SLA Deadline calculation
        $category = TicketCategory::find($validated['category_id']);
        $slaHours = $category?->sla_hours ?: 48;

        if ($validated['urgensi'] === 'Kritis') {
            $slaHours = 2;
        } elseif ($validated['urgensi'] === 'Tinggi') {
            $slaHours = 24;
        }

        $slaDeadline = now()->addHours($slaHours);

        // Upload lampiran jika ada
        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $folder = 'uploads/tickets/' . ($tenantId ?: 'global') . '/' . ($user?->id ?: 'user');
            $lampiranPath = $file->store($folder, 'public');
        }

        // Generate nomor tiket: TKT-YYYYMMDD-XXXX
        $nomorTiket = 'TKT-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        $ticket = Ticket::create([
            'tenant_id'    => $tenantId,
            'user_id'      => $user?->id,
            'category_id'  => $validated['category_id'],
            'nomor_tiket'  => $nomorTiket,
            'judul'        => strip_tags($validated['judul']),
            'deskripsi'    => strip_tags($validated['deskripsi']),
            'urgensi'      => $validated['urgensi'],
            'status'       => 'Menunggu',
            'lampiran'     => $lampiranPath,
            'user_agent'   => $request->userAgent(),
            'last_url'     => $validated['last_url'] ?? null,
            'sla_deadline' => $slaDeadline,
            'user_unread'  => false,
            'admin_unread' => true,
            'is_active'    => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tiket laporan #' . $nomorTiket . ' berhasil dibuat.',
                'data'    => $ticket,
            ], 201);
        }

        return back()->with('success', 'Tiket laporan #' . $nomorTiket . ' berhasil dibuat.');
    }

    /**
     * Menambahkan balasan pada tiket
     */
    public function replyTicket(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'pesan'    => 'required|string',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:3072',
        ]);

        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        if ($isSuperAdmin) {
            $ticket = Ticket::withoutTenant()->find($id);
        } else {
            $ticket = Ticket::where('user_id', $user?->id)->find($id);
        }

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan atau akses ditolak.',
            ], 404);
        }

        if (in_array($ticket->status, ['Selesai', 'Batal'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket sudah ditutup dan tidak dapat dibalas.',
            ], 422);
        }

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $folder = 'uploads/tickets/' . ($ticket->tenant_id ?: 'global') . '/replies';
            $lampiranPath = $file->store($folder, 'public');
        }

        $reply = TicketReply::create([
            'ticket_id'     => $ticket->id,
            'user_id'       => $user?->id,
            'is_superadmin' => $isSuperAdmin,
            'pesan'         => strip_tags($validated['pesan']),
            'lampiran'      => $lampiranPath,
            'created_at'    => now(),
        ]);

        if ($isSuperAdmin) {
            $ticket->status = 'Diproses';
            $ticket->user_unread = true;
            $ticket->admin_unread = false;
        } else {
            $ticket->user_unread = false;
            $ticket->admin_unread = true;
        }
        $ticket->touch();
        $ticket->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Balasan berhasil dikirim.',
                'data'    => $reply->load('user:id,nama_lengkap,username'),
            ]);
        }

        return back()->with('success', 'Balasan berhasil dikirim.');
    }

    /**
     * Memperbarui status tiket
     */
    public function updateStatus(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Menunggu,Diproses,Selesai,Batal',
        ]);

        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;

        if ($isSuperAdmin) {
            $ticket = Ticket::withoutTenant()->find($id);
        } else {
            // User hanya boleh menutup tiket miliknya sendiri
            $ticket = Ticket::where('user_id', $user?->id)->find($id);
            if (!in_array($validated['status'], ['Selesai', 'Batal'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda hanya dapat mengubah status menjadi Selesai atau Batal.',
                ], 403);
            }
        }

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan.',
            ], 404);
        }

        $ticket->status = $validated['status'];
        if ($isSuperAdmin) {
            $ticket->user_unread = true;
        } else {
            $ticket->admin_unread = true;
        }
        $ticket->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status tiket #' . $ticket->nomor_tiket . ' berhasil diubah menjadi ' . $ticket->status,
                'data'    => $ticket,
            ]);
        }

        return back()->with('success', 'Status tiket berhasil diubah.');
    }

    /**
     * Live search FAQ lookup
     */
    public function faqLookup(Request $request): JsonResponse
    {
        $query = trim((string)$request->query('q', ''));
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data'    => [],
            ]);
        }

        $faqs = TicketFaq::with('category:id,nama_kategori')
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('pertanyaan', 'ILIKE', "%{$query}%")
                  ->orWhere('jawaban', 'ILIKE', "%{$query}%");
            })
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $faqs,
        ]);
    }

    /**
     * Mendapatkan canned responses (Super Admin)
     */
    public function getCannedResponses(): JsonResponse
    {
        $responses = TicketCannedResponse::orderBy('judul', 'asc')->get();
        return response()->json([
            'success' => true,
            'data'    => $responses,
        ]);
    }

    /**
     * Menyimpan canned response baru (Super Admin)
     */
    public function storeCannedResponse(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'judul'  => 'required|string|max:200',
            'konten' => 'required|string',
        ]);

        $canned = TicketCannedResponse::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template balasan cepat berhasil ditambahkan.',
            'data'    => $canned,
        ], 201);
    }

    /**
     * Menghapus canned response (Super Admin)
     */
    public function deleteCannedResponse(string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $canned = TicketCannedResponse::findOrFail($id);
        $canned->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template balasan cepat berhasil dihapus.',
        ]);
    }

    /**
     * Menyimpan FAQ baru (Super Admin)
     */
    public function storeFaq(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:core.ticket_categories,id',
            'pertanyaan'  => 'required|string|max:255',
            'jawaban'     => 'required|string',
            'is_active'   => 'boolean',
        ]);

        $faq = TicketFaq::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'FAQ basis pengetahuan berhasil ditambahkan.',
            'data'    => $faq->load('category'),
        ], 201);
    }

    /**
     * Menghapus FAQ (Super Admin)
     */
    public function deleteFaq(string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $faq = TicketFaq::findOrFail($id);
        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil dihapus.',
        ]);
    }

    /**
     * Menghitung unread tickets real-time
     */
    public function getUnreadCount(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => true, 'unread_count' => 0]);
        }

        if ($user->isSuperAdmin()) {
            $count = Ticket::withoutTenant()->where('admin_unread', true)->count();
        } else {
            $count = Ticket::where('user_unread', true)->where('user_id', $user->id)->count();
        }

        return response()->json([
            'success'      => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * Mengambil daftar usulan request fitur
     */
    public function getFeatureRequests(Request $request): JsonResponse
    {
        $user = Auth::user();
        $modul = $request->query('modul');
        $status = $request->query('status');
        $sort = $request->query('sort', 'popular'); // 'popular' | 'newest'
        $search = trim((string)$request->query('search', ''));

        $query = FeatureRequest::withoutTenant()
            ->with([
                'user:id,nama_lengkap,username',
                'tenant:id,nama_sekolah'
            ])
            ->where('is_active', true);

        if (!empty($modul)) {
            $query->where('modul_terkait', $modul);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('judul_fitur', 'ILIKE', "%{$search}%")
                  ->orWhere('deskripsi_kebutuhan', 'ILIKE', "%{$search}%")
                  ->orWhere('ekspektasi_solusi', 'ILIKE', "%{$search}%");
            });
        }

        if ($sort === 'newest') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('votes_count', 'desc')->orderBy('created_at', 'desc');
        }

        $features = $query->paginate(12);

        // Ambil daftar ID request yang sudah di-vote oleh user aktif
        $votedIds = [];
        if ($user) {
            $votedIds = FeatureRequestVote::where('user_id', $user->id)
                ->whereIn('feature_request_id', $features->getCollection()->pluck('id'))
                ->pluck('feature_request_id')
                ->toArray();
        }

        $features->getCollection()->transform(function ($item) use ($votedIds) {
            $item->has_voted = in_array($item->id, $votedIds);
            return $item;
        });

        // Summary Stats
        $stats = [
            'total'               => FeatureRequest::withoutTenant()->where('is_active', true)->count(),
            'sedang_dikembangkan' => FeatureRequest::withoutTenant()->where('status', 'Sedang Dikembangkan')->count(),
            'selesai'             => FeatureRequest::withoutTenant()->where('status', 'Selesai')->count(),
            'disetujui'           => FeatureRequest::withoutTenant()->where('status', 'Disetujui')->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $features,
            'stats'   => $stats,
        ]);
    }

    /**
     * Menyimpan usulan fitur baru
     */
    public function storeFeatureRequest(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'judul_fitur'         => 'required|string|max:255',
            'modul_terkait'       => 'required|string|max:100',
            'urgensi_bisnis'      => 'required|string|in:Rendah,Sedang,Tinggi,Sangat Mendesak',
            'deskripsi_kebutuhan' => 'required|string',
            'ekspektasi_solusi'   => 'nullable|string',
            'lampiran'            => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:3072',
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $folder = 'uploads/feature_requests/' . ($tenantId ?: 'global');
            $lampiranPath = $file->store($folder, 'public');
        }

        $feature = FeatureRequest::create([
            'tenant_id'           => $tenantId,
            'user_id'             => $user?->id,
            'judul_fitur'         => strip_tags($validated['judul_fitur']),
            'modul_terkait'       => $validated['modul_terkait'],
            'urgensi_bisnis'      => $validated['urgensi_bisnis'],
            'deskripsi_kebutuhan' => strip_tags($validated['deskripsi_kebutuhan']),
            'ekspektasi_solusi'   => !empty($validated['ekspektasi_solusi']) ? strip_tags($validated['ekspektasi_solusi']) : null,
            'status'              => 'Review',
            'votes_count'         => 1, // Auto vote creator
            'lampiran'            => $lampiranPath,
            'is_active'           => true,
        ]);

        // Auto vote by creator
        if ($user) {
            FeatureRequestVote::create([
                'feature_request_id' => $feature->id,
                'user_id'            => $user->id,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usulan fitur baru berhasil diajukan ke dewan pengembang SINTA.',
                'data'    => $feature->load('user:id,nama_lengkap,username', 'tenant:id,nama_sekolah'),
            ], 201);
        }

        return back()->with('success', 'Usulan fitur baru berhasil diajukan.');
    }

    /**
     * Upvote / Un-vote usulan fitur
     */
    public function voteFeatureRequest(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);
        }

        $feature = FeatureRequest::withoutTenant()->findOrFail($id);

        $existing = FeatureRequestVote::where('feature_request_id', $feature->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $feature->decrement('votes_count');
            $hasVoted = false;
            $message = 'Dukungan suara dibatalkan.';
        } else {
            FeatureRequestVote::create([
                'feature_request_id' => $feature->id,
                'user_id'            => $user->id,
            ]);
            $feature->increment('votes_count');
            $hasVoted = true;
            $message = 'Terima kasih atas dukungan suara Anda untuk fitur ini!';
        }

        return response()->json([
            'success'     => true,
            'message'     => $message,
            'has_voted'   => $hasVoted,
            'votes_count' => $feature->fresh()->votes_count,
        ]);
    }

    /**
     * Memperbarui status & catatan pengembang usulan fitur (Super Admin)
     */
    public function updateFeatureRequestStatus(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'status'             => 'required|string|in:Review,Disetujui,Dalam Antrian,Sedang Dikembangkan,Selesai,Ditolak',
            'estimasi_rilis'     => 'nullable|string|max:100',
            'catatan_pengembang' => 'nullable|string',
        ]);

        $feature = FeatureRequest::withoutTenant()->findOrFail($id);
        $feature->update([
            'status'             => $validated['status'],
            'estimasi_rilis'     => $validated['estimasi_rilis'] ?? null,
            'catatan_pengembang' => $validated['catatan_pengembang'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status usulan fitur berhasil diperbarui.',
            'data'    => $feature->fresh()->load('user:id,nama_lengkap,username', 'tenant:id,nama_sekolah'),
        ]);
    }
}

