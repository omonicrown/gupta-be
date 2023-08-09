<?php

namespace App\Http\Controllers\Api\Links;

use AshAllenDesign\ShortURL\Facades\ShortURL;
use Exception;
use App\Models\Short;
use App\Models\Link;
use App\Models\LinkInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;


class UpdateLinkInfoController extends Controller
{
    /**
     * Create Link
     * @param Request $request
     * @return Link
     */
    public function __invoke(Request $request, $id)
    {

        try {

            DB::beginTransaction();
            $s_url_id = Link::whereId($id)->first()->short_url_id;

            // //create a new one as update.
            $shortURL = ShortURL::destinationUrl(
                'https://api.whatsapp.com/send?phone='.$request->phone_number.'&text='.$request->message
            )->urlKey(
                str_replace(' ', '', $request->name)
            )->trackVisits()->make();

            $link = Link::updateOrCreate(
                [
                    'id' => $id
                ],
                [
                    'name' => $shortURL->url_key,
                    'type' => 'message',
                    'short_url_id' => $shortURL->id
                ]
            );

            $info = LinkInfo::updateOrCreate(
                [
                    'link_id' => $link->id
                ],
                [
                    'phone_number' => $request->phone_number,
                    'message' => $request->message,
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Link updated Successfully',
                'info' => $info,
                'link' => $link,
                'url' => Short::whereId($shortURL->id)->first()->default_short_url
            ], 200);

            DB::commit();
        } catch (Exception $e) {

            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
