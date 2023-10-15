<?php

namespace App\Http\Controllers\Api\MarketPlace;

use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\createTieredLinkRequest;
use App\Http\Requests\MarketPlace\CreateProductRequest;
use App\Http\Requests\MarketPlace\MarketLinkRequest;
use App\Models\MarketPlaceLink;
use App\Models\MultiLink;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Create Link
     * @param Request $request
     */
    public function CreateProduct(CreateProductRequest $request)
    {
        try {
            DB::beginTransaction();

              //    dd(Cloudinary::destroy('partnerCourse/k10D3S1dhI3BQ7gOjjg9chizhZeK4dPpTP3mrFEs.png'));
            //   dd(($request->image->storeOnCloudinaryAs('partnerCourse', $request->image->hashName()))->getPublicId());


            $image_1 = $request->product_image_1->storeOnCloudinaryAs('productImages', $request->product_image_1->hashName());
            $image_2 = $request->product_image_2->storeOnCloudinaryAs('productImages', $request->product_image_2->hashName());
            $image_3 = $request->product_image_3->storeOnCloudinaryAs('productImages', $request->product_image_3->hashName());
            $link = Product::create([
                'link_name' => str_replace(' ', '-', $request->link_name),
                'link_id' => $request->link_name,
                'product_name' => $request->product_name,
                'product_description' => $request->product_description,
                'phone_number' => $request->phone_number,
                'no_of_items' => $request->no_of_items,
                'product_price' => $request->product_price,
                'product_image_1' => $image_1->getPath(),
                'product_image_2' => $image_2->getPath(),
                'product_image_3' => $image_3->getPath(),
                'product_image_id_1' => $image_1->getPublicId(),
                'product_image_id_2' => $image_2->getPublicId(),
                'product_image_id_3' => $image_3->getPublicId(),
                'user_id' => auth()->user()->id
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Product Created Successfully',
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
