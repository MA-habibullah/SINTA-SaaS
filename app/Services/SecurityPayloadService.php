<?php

namespace App\Services;

use Modules\Core\Services\SecurityPayloadService as BaseSecurityPayloadService;

/**
 * Class SecurityPayloadService (Global Alias Proxy)
 * 
 * Memetakan App\Services\SecurityPayloadService ke Modules\Core\Services\SecurityPayloadService
 * guna mencegah ClassNotFoundException pada pemanggilan legacy / lintas namespace.
 */
class SecurityPayloadService extends BaseSecurityPayloadService
{
}
