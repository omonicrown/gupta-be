<?php

namespace App\Http\Controllers\Api\Links;

use Auth;
use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\createTieredLinkRequest;
use App\Models\MultiLink;

class CreateTieredController extends Controller
{
    /**
     * Create Link
     * @param Request $request
     */
    public function __invoke(createTieredLinkRequest $request)
    {
        try {

            DB::beginTransaction();

            $links = Link::where('user_id', Auth::user()->id)->where('type','tiered')->count();
            if ($links >= Auth::user()->no_of_mlink) {
                // return $links;
                return response()->json([
                    'status' => false,
                    'message' => 'Link exceeded '.Auth::user()->no_of_mlink,
                    'errors' => 'Unauthorized'
                ], 500);
            }

            // $pics_2='';
            // if($request->logo !== '1'){
            //     $uploadedFile2 = $request->logo;
            //     $pin = mt_rand(10, 99);
            //     $pics_2 = $uploadedFile2->hashName();
            //     $uploadedFile2->move(public_path('TieredImages/'), $pics_2);
            // }
            $students = preg_split("/[,]/", $request->attach_links);
           

            $link = Link::create([
                'name' => str_replace(' ', '', $request->name),
                'title' =>  $request->title,
                'type' =>  'tiered',
                'logo' =>  ($request->logo == 'No selected file' ? 'no image' : ($request->logo->storeOnCloudinaryAs('logo/'.Auth::user()->id, $request->logo->hashName()))->getPath()),
                'logo_id' =>  ($request->logo == 'No selected file' ? 'no image path' : ($request->logo->storeOnCloudinaryAs('logo/'.Auth::user()->id, $request->logo->hashName()))->getPublicId()),
                'bio' => $request->bio,
                'business_website' => $request->business_website,
                'business_policy' => $request->business_policy,
                'redirect_link' => $request->redirect_link,
                'user_id' => auth()->user()->id
            ]);

            foreach ($students as $key => $value) {
                MultiLink::create([
                    'link_id' => $link->id,
                    'attach_links' =>  $value,
               ]);
            }

            //    MultiLink::create([
            //         'link_id' => $link->id,
            //         'attach_links' =>  $request->title,
            //    ]);


            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Multi Link Created Successfully',
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
