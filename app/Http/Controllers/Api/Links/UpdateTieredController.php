<?php

namespace App\Http\Controllers\Api\Links;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\createTieredLinkRequest;
use App\Models\MultiLink;
use Illuminate\Support\Facades\Validator;

class UpdateTieredController extends Controller
{
    /**
     * Create Link
     * @param Request $request
     */
    public function updateTieredLink(Request $request)
    {
        try {
            // if ($data['article_image2'] !== 'No selected file') {
            DB::beginTransaction();
            $students = preg_split("/[,]/", $request->attach_links);

            $Link = Link::where('id', $request->id)->first();
            $Link->name = str_replace(' ', '', $request->name);
            $Link->title = $request->title;
            $Link->bio = $request->bio;
            $Link->business_website = $request->business_website;
            $Link->business_policy = $request->business_policy;
            $Link->redirect_link = $request->redirect_link;
            $Link->title = $request->title;
            $Link->save();

            MultiLink::where('link_id', $request->id)->forceDelete();
            foreach ($students as $key => $value) {
                MultiLink::create([
                    'link_id' => $request->id,
                    'attach_links' =>  $value,
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Tiered Link Updated Successfully',
                'link' => $Link,
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getLinkDetails($linkName)
    {
        try {
            $link = Link::where('id',$linkName)->first();
            $MultiLink = MultiLink::where('link_id',$link->id)->get(['attach_links']);
            return $this->success( 'Fetched Successfully',['multiLinks'=>$link,'attachLinks'=>$MultiLink]);
        } catch (Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        } 

    }

    public function getLinkDetailByName($linkName)
    {
        try {
            $link = Link::where('name',$linkName)->where('type','tiered')->first();
            $MultiLink = MultiLink::where('link_id',$link->id)->get(['attach_links']);
            return $this->success( 'Fetched Successfully',['multiLinks'=>$link,'attachLinks'=>$MultiLink]);
        } catch (Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        } 

    }

    public function updateLogo(Request $data, $id)
    {
        $validateUser = Validator::make(
            $data->all(),
            [
                'id' => 'required',
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors()
            ], 401);
        }

        DB::beginTransaction();
        try {
            $link = Link::where('id', $id)->first();
            $uploadedFile1 = $data->logo;
            $pics_1 = $uploadedFile1->hashName();
            $userImage = 'TieredImages/' . $link->logo;
            if ($link->logo !== null) {
                if (file_exists(public_path($userImage))) {
                    unlink(public_path($userImage));
                }
            }
            $uploadedFile1->move(public_path('TieredImages/'), $pics_1);
            $link = Link::where('id', $data->id);
            $link->logo = $pics_1;
            $link->save();
            DB::commit();
            return $this->sendResponse($link, 'Logo updated Successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th);
        }
    }


    public function DeleteLink($id)
    {
        // dd($id);
        DB::beginTransaction();
        try {
            $link = Link::where('id', $id)->first();
            // dd($link->logo);

            if($link->logo_id !=='no image path' || !$link->logo_id){
                (Cloudinary::destroy($link->logo_id));
            }


            // $userImage = 'TieredImages/' . $link->logo;
            // if ($link->logo !== null) {
            //     if (file_exists(public_path($userImage))) {
            //         unlink(public_path($userImage));
            //     }
            // }

            MultiLink::where('link_id', $id)->forceDelete();
            Link::where('id', $id)->forceDelete();
            DB::commit();
            return $this->success($link, 'Logo deleted Successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error($th);
        }
    }
}
