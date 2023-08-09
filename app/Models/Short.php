<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use AshAllenDesign\ShortURL\Models\ShortURL;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Short extends ShortURL
{
    protected $hidden = [
      "single_use",
      "forward_query_params",
      "track_visits",
      "redirect_status_code",
      "track_ip_address",
      "track_operating_system",
      "track_operating_system_version",
      "track_browser",
      "track_browser_version",
      "track_referer_url",
      "track_device_type",
      "activated_at",
      "deactivated_at",
      "created_at",
      "updated_at"
    ];

    public function link(): HasOne
    {
        return $this->hasOne(Link::class);
    }
}
