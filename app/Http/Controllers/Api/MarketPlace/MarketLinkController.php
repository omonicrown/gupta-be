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
use Illuminate\Support\Facades\Auth;
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

            $links = MarketPlaceLink::where('user_id', Auth::user()->id)->count();
            if ($links >= Auth::user()->no_of_mlink) {
                // return $links;
                return response()->json([
                    'status' => false,
                    'message' => 'Link exceeded '.Auth::user()->no_of_mlink,
                    'errors' => 'Unauthorized'
                ], 500);
            }


            //    dd(Cloudinary::destroy('partnerCourse/k10D3S1dhI3BQ7gOjjg9chizhZeK4dPpTP3mrFEs.png'));
            // dd(($request->image->storeOnCloudinaryAs('partnerCourse', $request->image->hashName()))->getPublicId());
            
            $link = MarketPlaceLink::create([
                'link_name' => str_replace(' ', '-', $request->link_name),
                'brand_primary_color' => $request->brand_primary_color,
                'brand_description' => $request->brand_description,
                'facebook_url' => $request->facebook_url,
                'instagram_url' => $request->instagram_url,
                'tiktok_url' => $request->tiktok_url,
                'brand_logo' => ($request->brand_logo =='No selected file' ? 'no image' : ($request->brand_logo->storeOnCloudinaryAs('brandLogos', $request->brand_logo->hashName()))->getPath()),
                'brand_logo_id' =>  ( $request->brand_logo =='No selected file' ? 'no image' : ($request->brand_logo->storeOnCloudinaryAs('productImages', $request->brand_logo->hashName()))->getPublicId()),
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


    public function checkMarketLink(Request $request)
    {
        try {
            DB::beginTransaction();
            $link = MarketPlaceLink::where('link_name', '=', $request->link_name)->count();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => $link <= 0 ? 'Available' : 'Taken',
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

    public function getLinks()
    {
        try {
            DB::beginTransaction();
            $link = MarketPlaceLink::where('user_id', Auth::user()->id)->get();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' =>'success',
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
