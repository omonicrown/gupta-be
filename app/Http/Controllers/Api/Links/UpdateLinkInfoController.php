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
use AshAllenDesign\ShortURL\Models\ShortURL as ShortModel;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;


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

            // $short = ShortModel::where('url_key',$request->name)->first();
            // ShortURLVisit::where('short_url_id',$short->id)->forceDelete();
            // ShortURL::where('url_key',$request->name)->forceDelete();

            $short = ShortModel::where('url_key',$request->name)->first();
            $short->destination_url =  'https://api.whatsapp.com/send?phone='.$request->phone_number.'&text='.$request->message;
            $short->save();


            // $shortURL = ShortURL::destinationUrl(
            //     'https://api.whatsapp.com/send?phone='.$request->phone_number.'&text='.$request->message
            // )->urlKey(
            //     str_replace(' ', '', $request->name)
            // )->trackVisits()->make();

            $Link = Link::where('id',$id)->first();
            // $Link->name = $shortURL->url_key;
            // $Link->type = 'message';
            // $Link->short_url_id = $shortURL->id;
            // $Link->save();

            // $link = Link::updateOrCreate(
            //     [
            //         'id' => $id
            //     ],
            //     [
            //         'name' => $shortURL->url_key,
            //         'type' => 'message',
            //         'short_url_id' => $shortURL->id
            //     ]
            // );

            $LinkInfo = LinkInfo::where('link_id',$Link->id)->first();
            $LinkInfo->phone_number = $request->phone_number;
            $LinkInfo->message = $request->message;
            $LinkInfo->save(); 

            // $info = LinkInfo::updateOrCreate(
            //     [
            //         'link_id' => $link->id
            //     ],
            //     [
            //         'phone_number' => $request->phone_number,
            //         'message' => $request->message,
            //     ]
            // );
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Link updated Successfully',
                'info' => $LinkInfo,
                'link' => $Link,
                'url' => Short::whereId($short->id)->first()->default_short_url
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
