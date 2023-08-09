<?php

namespace App\Http\Controllers\Api\Links;

use App\Http\Controllers\Controller;
use App\Models\Link;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckLinkController extends Controller
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

            $link = Link::where('name','=',$request->name)->count();
            DB::commit();
          
            return response()->json([
                'status' => true,
                'data' => $link,
            ], 200);

        } catch(Exception $e) {

            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'link name already in use'
            ], 500);
        }
    }
}
