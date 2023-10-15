<?php

namespace App\Http\Controllers\Api\MarketPlace;

use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\createTieredLinkRequest;
use App\Http\Requests\MarketPlace\MarketLinkRequest;
use App\Models\MarketPlaceLink;
use App\Models\MultiLink;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use JD\Cloudder\Facades\Cloudder;

class MarketLinkController extends Controller
{
    /**
     * Create Link
     * @param Request $request
     */
    public function CreateMarketLink(MarketLinkRequest $request)
    {
        try {
            DB::beginTransaction();
        //    dd(Cloudinary::destroy('partnerCourse/k10D3S1dhI3BQ7gOjjg9chizhZeK4dPpTP3mrFEs.png'));
            dd(($request->image->storeOnCloudinaryAs('partnerCourse', $request->image->hashName()))->getPublicId());
            $link = MarketPlaceLink::create([
                'link_name' => str_replace(' ', '-', $request->link_name),
                'user_id' => auth()->user()->id
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Market Link Created Successfully',
                'link' => $link,
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => ($e->getMessage())
            ], 500);
        }
    }

    public function CreateProductLink(MarketLinkRequest $request)
    {
        try {
            DB::beginTransaction();
            $link = MarketPlaceLink::create([
                'link_name' => str_replace(' ', '-', $request->link_name),
                'user_id' => auth()->user()->id
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Market Link Created Successfully',
                'link' => $link,
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => ($e->getMessage())
            ], 500);
        }
    }
}
