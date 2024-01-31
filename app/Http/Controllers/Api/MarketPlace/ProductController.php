<?php

namespace App\Http\Controllers\Api\MarketPlace;

use Auth;
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
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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

            $links = Product::where('user_id', Auth::user()->id)->count();
            if ($links >= ((Auth::user()->no_of_mstore)+1)) {
                // return $links;
                return response()->json([
                    'status' => false,
                    'message' => 'Link exceeded ' . Auth::user()->no_of_mstore,
                    'errors' => 'Unauthorized'
                ], 500);
            }

            $link = Product::create(
                [
                    'link_name' => str_replace(' ', '-', $request->link_name),
                    'link_id' => $request->link_name,
                    'product_name' => $request->product_name,
                    'product_description' => $request->product_description,
                    'phone_number' => $request->phone_number,
                    'no_of_items' => $request->no_of_items,
                    'product_price' => $request->product_price,
                    'product_image_1' => (($request->product_image_1->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_1->hashName()))->getPath()),
                    'product_image_2' => ($request->product_image_2 == 'No selected file' ? 'no image' : ($request->product_image_2->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_2->hashName()))->getPath()),
                    'product_image_3' => ($request->product_image_3 == 'No selected file' ? 'no image' : ($request->product_image_3->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_3->hashName()))->getPath()),
                    'product_image_id_1' => (($request->product_image_1->storeOnCloudinaryAs('productImages', $request->product_image_1->hashName()))->getPublicId()),
                    'product_image_id_2' => ($request->product_image_2 == 'No selected file' ? 'no image path' : ($request->product_image_2->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_2->hashName()))->getPublicId()),
                    'product_image_id_3' => ($request->product_image_3 == 'No selected file' ? 'no image path' : ($request->product_image_3->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_3->hashName()))->getPublicId()),
                    'user_id' => auth()->user()->id
                ]
            );

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

    public function UpdateProduct(Request $request)
    {
        try {
            DB::beginTransaction();
            $product = Product::where('id', $request->id)->first();
            if ($request->id) {
                if ($request->product_image_1 !== $product->product_image_1 && $request->product_image_1 !== 'No selected file') {
                    if ($request->product_image_id_1) {
                        (Cloudinary::destroy($product->product_image_id_1));
                    }
                }

                if ($request->product_image_2 !== $product->product_image_2 && $request->product_image_2 !== 'No selected file') {
                    if ($request->product_image_id_2) {
                        (Cloudinary::destroy($product->product_image_id_2));
                    }
                }

                if ($request->product_image_3 !== $product->product_image_3 && $request->product_image_3 !== 'No selected file') {
                    if ($request->product_image_id_3) {
                        (Cloudinary::destroy($product->product_image_id_3));
                    }
                }
            }

            $link = Product::updateOrCreate(
                ['id' => $request->id],
                [
                    'link_name' => str_replace(' ', '-', $request->link_name),
                    'link_id' => $request->link_name,
                    'product_name' => $request->product_name,
                    'product_description' => $request->product_description,
                    'phone_number' => $request->phone_number,
                    'no_of_items' => $request->no_of_items,
                    'product_price' => $request->product_price,
                    'product_image_1' => (($request->product_image_1 == $product->product_image_1) ? $request->product_image_1 : ($request->product_image_1->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_1->hashName()))->getPath()),
                    'product_image_2' => (($request->product_image_2 == $product->product_image_2) ? $request->product_image_2 : ($request->product_image_2->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_2->hashName()))->getPath()),
                    'product_image_3' => (($request->product_image_3 == $product->product_image_3) ? $request->product_image_3 : ($request->product_image_3->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_3->hashName()))->getPath()),
                    'product_image_id_1' => (($request->product_image_1 == $product->product_image_1) ? $request->product_image_id_1 : ($request->product_image_1->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_1->hashName()))->getPublicId()),
                    'product_image_id_2' => (($request->product_image_2 == $product->product_image_2) ? $request->product_image_id_2 : ($request->product_image_2->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_2->hashName()))->getPublicId()),
                    'product_image_id_3' => (($request->product_image_3 == $product->product_image_3) ? $request->product_image_id_3 : ($request->product_image_3->storeOnCloudinaryAs('productImages/'.Auth::user()->id, $request->product_image_3->hashName()))->getPublicId()),
                    'user_id' => auth()->user()->id
                ]
            );

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

    public function getAllProducts()
    {
        try {
            // dd(auth()->user()->id);
            $products = Product::where('user_id', auth()->user()->id)->paginate('10');
            return $this->success('Fetched Successfully', $products);
        } catch (Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getProductsByLinkName($name)
    {
        try {
            // dd(auth()->user()->id);
            $products = Product::where('link_name', $name)->paginate(10);
            $market = MarketPlaceLink::where('link_name', $name)->first();
            return $this->success('Fetched Successfully', [
                'market_info' => $market,
                'products' => $products
            ]);
        } catch (Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getSingleProduct($id)
    {
        try {
            $product = Product::where('id', $id)->first();
            $market = MarketPlaceLink::where('link_name',  $product->link_name)->first();
            return $this->success('Fetched Successfully',[
               'product'=> $product,
               'market_info' => $market
               ] );
        } catch (Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function deleteProduct($id)
    {
        try {

            $images = Product::where('id', $id)->first();
            (Cloudinary::destroy($images->product_image_id_1));
            (Cloudinary::destroy($images->product_image_id_2));
            (Cloudinary::destroy($images->product_image_id_3));

            $product = Product::where('id', $id)->forceDelete();
            return $this->success('deleted Successfully', $product);
        } catch (Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


  


    public function updateProductData(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = Product::where('id', $request->id)->first();
            $data->link_name = str_replace(' ', '-', $request->link_name);
            $data->link_id = $request->link_id;
            $data->product_name = $request->product_name;
            $data->product_description = $request->product_description;
            $data->phone_number = $request->phone_number;
            $data->no_of_items = $request->no_of_items;
            $data->product_price = $request->product_price;
            $data->user_id = auth()->user()->id;
            $data->save();
            DB::commit();
            return $this->success('Product updated Successfully', $data);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th);
        }
    }

    public function updateProductImage1(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = Product::where('id', $request->id)->first();
            Cloudinary::destroy($data->product_image_id_1);
            $data->product_image_1 = ($request->product_image_1->storeOnCloudinaryAs('productImages', $request->product_image_1->hashName()))->getPath();
            $data->product_image_id_1 = ($request->product_image_1->storeOnCloudinaryAs('productImages', $request->product_image_1->hashName()))->getPublicId();
            $data->save();
            DB::commit();
            return $this->success('Image updated Successfully', $data);
            // return response()->json([
            //     'status' => true,
            //     'message' => 'Product Image Updated Successfully',
            //     'link' => $data,
            // ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th);
        }
    }

    public function updateProductImage2(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = Product::where('id', $request->id)->first();
            Cloudinary::destroy($request->product_image_id_2);
            $data->product_image_2 = ($request->product_image_2->storeOnCloudinaryAs('productImages', $request->product_image_2->hashName()))->getPath();
            $data->product_image_id_2 = ($request->product_image_2->storeOnCloudinaryAs('productImages', $request->product_image_2->hashName()))->getPublicId();
            $data->save();
            DB::commit();

            return $this->success($data, 'Image updated Successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th);
        }
    }

    public function updateProductImage3(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = Product::where('id', $request->id)->first();
            Cloudinary::destroy($request->product_image_id_3);
            $data->product_image_3 = ($request->product_image_3->storeOnCloudinaryAs('productImages', $request->product_image_3->hashName()))->getPath();
            $data->product_image_id_3 = ($request->product_image_3->storeOnCloudinaryAs('productImages', $request->product_image_3->hashName()))->getPublicId();
            $data->save();
            DB::commit();
            return $this->success($data, 'Image updated Successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th);
        }
    }
}
