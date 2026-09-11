<?php

namespace Modules\Cms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CmsPromotion extends Model
{
    use HasUuids;

    protected $table = 'cms.cms_promotions';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'section_key',
        'title',
        'subtitle',
        'content',
        'content_json',
        'badge_text',
        'image_url',
        'icon_class',
        'cta_text',
        'cta_link',
        'is_active',
        'order_num',
    ];

    protected $casts = [
        'content_json' => 'array',
        'is_active'    => 'boolean',
        'order_num'    => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];
}
