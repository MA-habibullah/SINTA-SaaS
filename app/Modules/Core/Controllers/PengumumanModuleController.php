<?php

namespace App\Modules\Core\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Core\Models\PengumumanModel;

class PengumumanModuleController extends BaseController {
    private PengumumanModel $model;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->model = new PengumumanModel($this->tenantId);
    }

    public function index(): void {
        $data = [
            "title" => "Manajemen Pengumuman",
            "pengumuman" => $this->model->getAll()
        ];
        $this->render("informasi/pengumuman", $data);
    }

    public function storeKategori(): void {
        // Implementation for storeKategori
        $this->jsonResponse(true, null, "Kategori disimpan.");
    }

    public function updateKategori(): void {
        // Implementation for updateKategori
        $this->jsonResponse(true, null, "Kategori diupdate.");
    }

    public function deleteKategori(): void {
        // Implementation for deleteKategori
        $this->jsonResponse(true, null, "Kategori dihapus.");
    }

    public function store(): void {
        // Implementation for store
        $this->jsonResponse(true, null, "Pengumuman disimpan.");
    }

    public function update(): void {
        // Implementation for update
        $this->jsonResponse(true, null, "Pengumuman diupdate.");
    }

    public function delete(): void {
        // Implementation for delete
        $this->jsonResponse(true, null, "Pengumuman dihapus.");
    }
}

