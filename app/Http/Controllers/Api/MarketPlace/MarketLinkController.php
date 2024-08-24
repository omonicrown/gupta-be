<?php

namespace App\Http\Controllers\Api\MarketPlace;

use App\Models\Product;
use App\Models\User;
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
            if ($links >= Auth::user()->no_of_malink) {
                // return $links;
                return response()->json([
                    'status' => false,
                    'message' => 'Link exceeded ' . Auth::user()->no_of_malink,
                    'errors' => 'Unauthorized'
                ], 500);
            }

            $linksExist = MarketPlaceLink::where('link_name', $request->link_name)->count();

            if ($linksExist > 0) {
                // return $links;
                return response()->json([
                    'status' => false,
                    'message' => 'link name already in use.Try another',
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
                'brand_logo' => ($request->brand_logo == 'No selected file' ? 'no image' : ($request->brand_logo->storeOnCloudinaryAs('brandLogos', $request->brand_logo->hashName()))->getPath()),
                'brand_logo_id' => ($request->brand_logo == 'No selected file' ? 'no image' : ($request->brand_logo->storeOnCloudinaryAs('brandLogos', $request->brand_logo->hashName()))->getPublicId()),
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

    public function UpdateMarketLink(Request $request)
    {
        try {
            DB::beginTransaction();
            $product = MarketPlaceLink::where('id', $request->id)->first();
            if ($request->id) {
                if ($request->brand_logo !== $product->brand_logo && $request->brand_logo !== 'No selected file') {
                    if ($request->brand_logo_id) {
                        (Cloudinary::destroy($product->brand_logo_id));
                    }
                }
            }

            $link = MarketPlaceLink::updateOrCreate(
                ['link_name' => $product->link_name],
                [
                    'link_name' => $product->link_name,
                    'brand_primary_color' => $request->brand_primary_color,
                    'brand_description' => $request->brand_description,
                    'facebook_url' => $request->facebook_url,
                    'instagram_url' => $request->instagram_url,
                    'tiktok_url' => $request->tiktok_url,
                    'brand_logo' => (($request->brand_logo == 'No selected file') ? $product->brand_logo : ($request->brand_logo->storeOnCloudinaryAs('brandLogos', $request->brand_logo->hashName()))->getPath()),
                    'brand_logo_id' => (($request->brand_logo == 'No selected file') ? $product->brand_logo_id : ($request->brand_logo->storeOnCloudinaryAs('brandLogos', $request->brand_logo->hashName()))->getPublicId()),
                    'user_id' => auth()->user()->id
                ]
            );

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Product Updated Successfully',
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
            $userData = User::where('id', Auth::user()->id)->first();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'success',
                'link' => $link,
                'user_data' => $userData
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => ($e->getMessage())
            ], 500);
        }
    }

    public function deleteMarketLink($id)
    {
        try {
            $marketLink = MarketPlaceLink::where('id', $id)->first();

            $images = Product::where('link_name', $marketLink->link_name)->get();
            
            foreach ($images as $image) {
                (Cloudinary::destroy($image->product_image_id_1));
                (Cloudinary::destroy($image->product_image_id_2));
                (Cloudinary::destroy($image->product_image_id_3));

            }

            $product = Product::where('link_name', $marketLink->link_name)->forceDelete();
            $delLink =  MarketPlaceLink::where('id', $id)->forceDelete();

            return $this->success('Deleted Successfully', $product);
        } catch (Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


}
