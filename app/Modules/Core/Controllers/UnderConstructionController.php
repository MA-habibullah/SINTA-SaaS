<?php
namespace App\Modules\Core\Controllers;

use App\Core\BaseController;

class UnderConstructionController extends BaseController
{
    public function index()
    {
        $namaSekolah = 'SINTA SaaS Platform';
        $title = 'Sedang Dalam Pengembangan - ' . $namaSekolah;
        
        $data = [
            'title' => $title,
            'user_role' => $_SESSION['role_name'] ?? 'Guest',
            'user_nama' => $_SESSION['nama_lengkap'] ?? 'User'
        ];
        
        // Because there is no specific view for UnderConstruction yet, we'll render a simple HTML response or create a view.
        // It's better to render a standard view. Since we don't have a view file for this yet, we can render the dashboard view and inject a message, or just create a simple view file inline.
        // Wait, standard MVC requires a view file. I'll create `views/core/under_construction.php` later, but for now I'll just echo a beautiful HTML here directly, or render an existing view.
        // The user rule says "buatkan tampilan premium khusus Under Construction". I should create a view file for it!
        $this->render('core/under_construction', $data);
    }
}
