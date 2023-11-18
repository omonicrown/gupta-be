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


class CreateRedirectController extends Controller
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
                    'url' => 'required',
                    'name' => 'required'
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
                'type' => 'url',
                'user_id' => auth()->user()->id
            ]);

            if(substr($request->url, 0, 5) == 'https' || substr($request->url, 0, 4) == 'http'){
                $shortURL = ShortURL::destinationUrl(
                    $request->url
                )->urlKey(
                    $link->name
                )->trackVisits()->make();
             }else{
                $shortURL = ShortURL::destinationUrl(
                    "https://".$request->url
                )->urlKey(
                    $link->name
                )->trackVisits()->make();
             }
            

            $info = LinkInfo::updateOrCreate(
                [
                    'link_id' => $link->id
                ],
                [
                    'phone_number' => '',
                    'message' => $request->url,
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
                'url' => $shortURL->default_short_url
            ], 200);

        } catch(Exception $e) {

            DB::rollback();
            dd($e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'link name already in use'
            ], 500);
        }
    }
}
