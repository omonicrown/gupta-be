<?php

namespace App\Http\Controllers\Api\Links;

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
            $link = Link::create([
                'name' => str_replace(' ', '', $request->name),
                'title' =>  $request->title,
                'type' =>  'tiered',
                'logo' =>  $request->logo,
                'bio' => $request->bio,
                'business_website' => $request->business_website,
                'business_policy' => $request->business_policy,
                'redirect_link' => $request->redirect_link,
                'user_id' => auth()->user()->id
            ]);

        //    MultiLink::create([
        //         'link_id' => $link->id,
        //         'attach_links' =>  $request->title,
        //    ]);


            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Tiered Link Created Successfully',
                'link' => $link,
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'link name already in use'
            ], 500);
        }
    }
}
