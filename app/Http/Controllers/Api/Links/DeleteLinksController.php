<?php

namespace App\Http\Controllers\Api\Links;

use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;


class DeleteLinksController extends Controller
{
    /**
     *
     * @param Request $request
     */
    public function __invoke(Request $request, $id)
    {
        try {

            DB::beginTransaction();

            $link = Link::whereId($id)->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Link deleted Successfully',
                'link' => $link
            ], 200);

        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
