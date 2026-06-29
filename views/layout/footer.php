<?php
/**
 * Layout Component: Footer
 * Menampilkan informasi hak cipta dan versi aplikasi dengan desain premium & responsive.
 */
?>
<footer class="footer bg-white border-top py-3 mt-auto" style="box-shadow: 0 -2px 10px rgba(0,0,0,0.015);">
    <div class="container-fluid px-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <!-- Left Side: Copyright and Status -->
            <div class="d-flex align-items-center flex-wrap gap-2 text-center text-md-start justify-content-center justify-content-md-start">
                <span class="text-muted d-inline-flex align-items-center" style="font-size: 0.75rem; letter-spacing: 0.2px;">
                    <i class="bi bi-patch-check-fill text-primary me-2 fs-6"></i>
                    <span>&copy; 2026 <strong>SINTA-SaaS Multi&#8209;Tenant SaaS</strong>. Hak Cipta Dilindungi.</span>
                </span>
            </div>
            
            <!-- Right Side: Status Badge & Version -->
            <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-end flex-wrap">
                <!-- Status System -->
                <div class="d-inline-flex align-items-center px-2.5 py-1 rounded-pill bg-success-subtle border border-success-subtle" style="font-size: 0.7rem; font-weight: 600; color: #15803d;">
                    <span class="position-relative d-inline-flex me-1.5" style="width: 6px; height: 6px;">
                        <span class="animate-ping position-absolute inline-flex h-100 w-100 rounded-full bg-success opacity-75" style="animation: footerPing 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-success" style="width: 6px; height: 6px;"></span>
                    </span>
                    Sistem Normal
                </div>
                
                <!-- Version Badge -->
                <span class="badge bg-light text-muted border px-2.5 py-1.5 rounded-pill d-inline-flex align-items-center font-monospace fw-semibold" style="font-size: 0.72rem; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02); white-space: nowrap;">
                    v1.2.0-stable
                </span>
            </div>
        </div>
    </div>
</footer>

<style>
@keyframes footerPing {
    0% { transform: scale(1); opacity: 1; }
    70%, 100% { transform: scale(2.5); opacity: 0; }
}
</style>
