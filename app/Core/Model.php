<?php

namespace App\Core;

use App\Config\Database;
use PDO;

abstract class Model {
    protected PDO $db;
    protected ?string $tenantId = null;

    public function __construct(?string $tenantId = null) {
        $this->db = Database::getConnection();
        $this->tenantId = $tenantId;
    }

    /**
     * Set the current tenant ID dynamically
     */
    public function setTenantId(string $tenantId): void {
        $this->tenantId = $tenantId;
    }

    /**
     * Helper to enforce tenant filtering on query parameters
     */
    protected function applyTenantFilter(array $params): array {
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }
        return $params;
    }
}
