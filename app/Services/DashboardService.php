<?php

namespace App\Services;

use App\Http\Controllers\Api\BaseController;  
use App\Models\User;  
use App\Models\Link;
use App\Models\Short;
use App\Models\MarketPlaceLink;
use Faker\Core\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class DashboardService extends BaseController
{
    public function getLinksCount()
    {
        try {
           $totalWhatsappLink = Link::whereIn('type', ['message', 'catalog'])->count();
           $totalRedirectLink = Link::whereIn('type', ['url'])->count();
           $totalMultiLink = Link::whereIn('type', ['tiered'])->count();
           $totalMarketLink = MarketPlaceLink::count();
           $totalCustomers = User::count();
           $totlaClicks = Short::count();

           return $this->sendResponse([
            'total_whatsapp_link'=>$totalWhatsappLink,
            'total_redirect_link'=>$totalRedirectLink,
            'total_multi_link'=>$totalMultiLink,
            'total_market_link'=>$totalMarketLink,
            'total_users'=>$totalCustomers,
            'total_Clicks'=>$totlaClicks,
        ],'Fetched Successfully');
        } catch (\Throwable $th) {
            return $this->sendError('Something went wrong');
        }
    }
}
