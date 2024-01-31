<?php

namespace App\Http\Controllers\Api\Links;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\LinkInfo;
use App\Models\Short;
use AshAllenDesign\ShortURL\Models\ShortURL;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;

class DeleteLinksController extends Controller
{
    /**
     *
     * @param Request $request
     */
    public function __invoke(Request $request, $id)
    {
        try {
            // return ($id);
            DB::beginTransaction();
            $link = Link::whereId($id)->first();

            if($link->logo_id !=='no image path' || $link->logo_id !==null){
                (Cloudinary::destroy($link->logo_id));
            }

            $short = ShortURL::where('url_key',$link->name)->first();
            ShortURLVisit::where('short_url_id',$short->id)->forceDelete();
            ShortURL::where('url_key',$link->name)->forceDelete();
            $link = Link::whereId($id)->forceDelete();
            $link = LinkInfo::where('link_id',$id)->forceDelete();

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
