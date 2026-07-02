<?php

namespace App\Controllers;

use App\Models\KategoriAgendaModel;
use App\Models\AgendaModel;

class KategoriAgendaController extends BaseController
{
    private $kategoriModel;
    private $agendaModel;

    public function __construct()
    {
        parent::__construct();
        // Hanya Super Admin atau Operator Sekolah yang boleh mengakses menu ini
        if (!isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], ['super_admin', 'operator_sekolah'])) {
            header('Location: /SINTA-SaaS/dashboard');
            exit;
        }

        $tenant_id = $_SESSION['tenant_id'] ?? null;
        $this->kategoriModel = new KategoriAgendaModel($tenant_id);
        $this->agendaModel = new AgendaModel($tenant_id);
    }

    public function index()
    {
        $kategoriList = $this->kategoriModel->getAll();
        
        $active_menu = 'kategori_agenda';
        $title = 'Manajemen Kategori Agenda';

        require_once __DIR__ . '/../../views/sekolah/kategori_agenda.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nama_kategori' => $_POST['nama_kategori'] ?? '',
                'kode_warna' => $_POST['kode_warna'] ?? '#0b5ed7'
            ];
            
            if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'super_admin') {
                if (isset($_POST['tenant_id'])) {
                    $data['tenant_id'] = ($_POST['tenant_id'] === 'global') ? null : $_POST['tenant_id'];
                }
            }
            
            if ($this->kategoriModel->create($data)) {
                $_SESSION['success_message'] = "Kategori agenda berhasil ditambahkan!";
            } else {
                $_SESSION['error_message'] = "Gagal menambahkan kategori agenda.";
            }
        }
        header('Location: /SINTA-SaaS/informasi/agenda');
        exit;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $data = [
                'nama_kategori' => $_POST['nama_kategori'] ?? '',
                'kode_warna' => $_POST['kode_warna'] ?? '#0b5ed7'
            ];
            
            if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'super_admin') {
                if (isset($_POST['tenant_id'])) {
                    $data['tenant_id'] = ($_POST['tenant_id'] === 'global') ? null : $_POST['tenant_id'];
                }
            }
            
            if ($id && $this->kategoriModel->update($id, $data)) {
                $_SESSION['success_message'] = "Kategori agenda berhasil diperbarui!";
            } else {
                $_SESSION['error_message'] = "Gagal memperbarui kategori agenda.";
            }
        }
        header('Location: /SINTA-SaaS/informasi/agenda');
        exit;
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            
            // Check if used by any agenda
            $agendas = $this->agendaModel->getAll(['kategori_id' => $id]);
            if (count($agendas) > 0) {
                $_SESSION['error_message'] = "Kategori tidak dapat dihapus karena masih digunakan oleh " . count($agendas) . " kegiatan.";
                header('Location: /SINTA-SaaS/informasi/agenda');
                exit;
            }

            if ($id && $this->kategoriModel->delete($id)) {
                $_SESSION['success_message'] = "Kategori agenda berhasil dihapus!";
            } else {
                $_SESSION['error_message'] = "Gagal menghapus kategori agenda.";
            }
        }
        header('Location: /SINTA-SaaS/informasi/kategori-agenda');
        exit;
    }
}
