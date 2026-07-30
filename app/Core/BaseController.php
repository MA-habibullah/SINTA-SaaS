<?php

namespace App\Core;

class BaseController {
    protected ?string $tenantId = null;

    public function __construct() {
        $this->detectTenant();
    }

    protected function detectTenant(): void {
        $subdomain = null;

        if (isset($_SERVER['HTTP_X_TENANT_ID'])) {
            $subdomain = $_SERVER['HTTP_X_TENANT_ID'];
        } else {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $parts = explode('.', $host);
            if (count($parts) >= 3 && $parts[0] !== 'www') {
                $subdomain = $parts[0];
            }
        }

        if ($subdomain !== null && $subdomain !== '') {
            $this->tenantId = $this->getTenantIdBySubdomain($subdomain);
        }
    }

    private function getTenantIdBySubdomain(string $subdomain): ?string {
        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("SELECT id FROM core.tenants WHERE subdomain = :subdomain AND status = 'active' LIMIT 1");
            $stmt->execute(['subdomain' => $subdomain]);
            $row = $stmt->fetch();
            return $row ? $row['id'] : null;
        } catch (\PDOException $e) {
            error_log("Failed to detect tenant by subdomain: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Dapatkan Base URL dinamis dari lingkungan server
     */
    protected function getBaseUrl(): string {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        return rtrim(dirname($scriptName), '/\\');
    }

    /**
     * Redirect HTTP dinamis bebas hardcode folder
     */
    protected function redirect(string $path): void {
        $cleanPath = '/' . ltrim($path, '/');
        $baseUrl = $this->getBaseUrl();
        header('Location: ' . $baseUrl . $cleanPath);
        exit;
    }

    protected function jsonResponse(bool $success, mixed $data = null, ?string $error = null, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        if (is_array($data)) {
            $data = $this->sanitizeDataArray($data);
        }

        echo json_encode([
            'success' => $success,
            'data'    => $data,
            'error'   => $error
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }

    private function sanitizeDataArray(array $arr): array {
        foreach ($arr as $key => &$val) {
            if (in_array(strtolower((string)$key), ['password', 'password_hash', 'token', 'remember_token', 'api_key'], true)) {
                unset($arr[$key]);
                continue;
            }
            if (is_array($val)) {
                $val = $this->sanitizeDataArray($val);
            }
        }
        return $arr;
    }

    protected function getJsonInput(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return $_POST;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }
}
