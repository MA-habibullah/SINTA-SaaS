<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\TenantModel;

class TenantService {
    public function getTenantProfile(string $tenantId): ?array {
        return TenantModel::findById($tenantId);
    }

    public function getTenantBySubdomain(string $subdomain): ?array {
        return TenantModel::findBySubdomain($subdomain);
    }
}
