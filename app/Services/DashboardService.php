<?php

namespace App\Services;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Link;
use App\Models\Short;
use App\Models\MarketPlaceLink;
use App\Models\Product;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;
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
            $totalProducts = Product::count();
            $totalCustomers = User::count();
            $totlaClicks = ShortURLVisit::where('operating_system', '!=', '0')->count();

            return $this->sendResponse([
                'total_whatsapp_link' => $totalWhatsappLink,
                'total_redirect_link' => $totalRedirectLink,
                'total_multi_link' => $totalMultiLink,
                'total_market_link' => $totalMarketLink,
                'total_users' => $totalCustomers,
                'total_Clicks' => $totlaClicks,
                'total_products' => $totalProducts
            ], 'Fetched Successfully');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

    public function getSingleUser($id)
    {
        $totalWhatsappLink = Link::where('user_id', $id)->whereIn('type', ['message', 'catalog'])->get();
        $totalRedirectLink = Link::where('user_id', $id)->whereIn('type', ['url'])->get();
        $totalMultiLink = Link::where('user_id', $id)->whereIn('type', ['tiered'])->get();
        $totalMarketLink = MarketPlaceLink::where('user_id', $id)->get();
        $totalProducts = Product::where('user_id', $id)->get();
        $userDetails = User::where('id', $id)->first();

        return $this->sendResponse([
            'whatsapp_link' => $totalWhatsappLink,
            'redirect_link' => $totalRedirectLink,
            'multi_link' => $totalMultiLink,
            'market_link' => $totalMarketLink,
            'total_products' => $totalProducts,
            'user_data' => $userDetails
        ], 'successful');
    }
}
