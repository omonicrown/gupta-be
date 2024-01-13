<?php

namespace App\Http\Controllers\Api\Links;

use AshAllenDesign\ShortURL\Facades\ShortURL;
use Exception;
use App\Models\Link;
use App\Models\LinkInfo;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;


class CreateRandomLinkController extends Controller
{
    /**
     *
     * @param Request $request
     */
    public function __invoke(Request $request)
    {
        try {

            $validateUser = Validator::make(
                $request->all(),
                [
                    'phone_number' => 'required',
                    'message' => 'required',
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
                'name' => Str::random(10),
                'type' => 'message',
            ]);

            $shortURL = ShortURL::destinationUrl(
                'https://api.whatsapp.com/send?phone='.$request->phone_number.'&text='.$request->message
            )->urlKey(
                $link->name
            )->trackVisits()->make();

            $info = LinkInfo::updateOrCreate(
                [
                    'link_id' => $link->id
                ],
                [
                    'phone_number' => $request->phone_number,
                    'message' => $request->message,
                ]
            );

            $link->update([
               'short_url_id' => $shortURL->id
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Link Created Successfully',
                'link' => $link,
                'info' => $info,
                'url' => $shortURL->default_short_url
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
