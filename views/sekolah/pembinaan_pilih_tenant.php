<div class="container-fluid px-4 py-4">
    <div class="mb-6">
        <h1 class="h3 mb-0 text-slate-800 font-bold">Pilih Sekolah (Tenant)</h1>
        <p class="text-slate-500 text-sm mt-1">Pilih sekolah yang ingin Anda pantau data Pembinaan & Supervisinya (Mode Super Admin)</p>
    </div>

    <div class="row g-4">
        <?php foreach ($tenants as $tenant): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="/SINTA-SaaS/pembinaan?tenant_id=<?= $tenant['id'] ?>" class="block h-100 text-decoration-none">
                    <div class="border border-slate-100 rounded-3xl p-6 bg-white shadow-sm hover:shadow-md transition-all h-100 flex flex-col items-center justify-center text-center group">
                        <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="bi bi-building text-3xl"></i>
                        </div>
                        <h5 class="font-bold text-slate-800 mb-1"><?= htmlspecialchars($tenant['nama_sekolah']) ?></h5>
                        <span class="badge bg-slate-100 text-slate-600 border border-slate-200"><?= htmlspecialchars($tenant['npsn']) ?></span>
                        <div class="mt-4 text-sm font-semibold text-blue-600 group-hover:text-blue-700">
                            Pantau Sekolah <i class="bi bi-arrow-right ml-1"></i>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
