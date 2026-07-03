<?php
require_once __DIR__ . '/app/Config/Database.php';

header('Content-Type: application/json');

try {
    $db = \App\Config\Database::getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tenant_id = $_GET['tenant'] ?? '11111111-1111-1111-1111-111111111111';

    // Check count of pengumuman
    $stmt1 = $db->prepare("SELECT COUNT(*) FROM pengumuman WHERE tenant_id = ?");
    $stmt1->execute([$tenant_id]);
    $count = $stmt1->fetchColumn();

    // Fetch the raw rows
    $stmt2 = $db->prepare("SELECT id, judul, created_by, tenant_id FROM pengumuman WHERE tenant_id = ?");
    $stmt2->execute([$tenant_id]);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Test the complex query
    $sql = "SELECT p.id, p.judul, u.nama_lengkap as nama_pembuat, r.nama_role as pembuat_role, t.nama_sekolah, k.nama_kategori 
            FROM pengumuman p 
            JOIN users u ON p.created_by = u.id 
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN tenants t ON p.tenant_id = t.id
            LEFT JOIN kategori_pengumuman k ON p.kategori_id = k.id
            WHERE p.tenant_id = :filter_tenant_id";
            
    $stmt3 = $db->prepare($sql);
    $stmt3->bindValue(':filter_tenant_id', $tenant_id);
    $stmt3->execute();
    $complex_rows = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'tenant_checked' => $tenant_id,
        'total_pengumuman' => $count,
        'raw_rows' => $rows,
        'complex_rows' => $complex_rows,
        'php_version' => PHP_VERSION,
        'mysql_version' => $db->getAttribute(PDO::ATTR_SERVER_VERSION)
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
