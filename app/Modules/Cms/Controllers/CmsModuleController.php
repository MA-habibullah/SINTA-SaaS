<?php

namespace App\Modules\Cms\Controllers;

use App\Core\BaseController;
use App\Modules\Cms\Models\CmsLandingModel;

class CmsModuleController extends BaseController {

    public function getLandingSectionsApi(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $sections = CmsLandingModel::getSections($this->tenantId);
        $this->jsonResponse(true, $sections);
    }
}
