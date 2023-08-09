<?php

namespace App\Http\Controllers\Api\Links;

use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;


class UpdateLinksController extends Controller
{
    /**
     *
     * @param Request $request
     */
    public function __invoke(Request $request, $id)
    {
        try {

            DB::beginTransaction();

            $link = tap(Link::whereId($id))->update([
                'name' => str_replace(' ', '', $request->name),
            ])->first();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Link updated Successfully',
                'link' => $link
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
