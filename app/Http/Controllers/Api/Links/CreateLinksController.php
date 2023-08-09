<?php

namespace App\Http\Controllers\Api\Links;

use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CreateLinksController extends Controller
{
    /**
     * Create Link
     * @param Request $request
     */
    public function __invoke(Request $request)
    {
        try {

            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
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

            $link = Link::create([
                'name' => $request->name,
                'type' => 'message',
                'user_id' => auth()->user()->id
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Link Created Successfully',
                'link' => $link
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

