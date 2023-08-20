<?php

namespace App\Http\Controllers\Api\Links;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\MultiLink;

class AddLinksToTieredLink extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        try {
            DB::beginTransaction(); 
            $link = MultiLink::create([
                'link_id' => $request->id,
                'attach_links' =>  $request->title,
            ]);
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
