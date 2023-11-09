<?php

namespace App\Http\Controllers\Api\Links;

use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\LinkInfo;
use AshAllenDesign\ShortURL\Models\ShortURL;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;
use Illuminate\Support\Facades\Validator;

class GetSingleLinksController extends Controller
{
    /**
     * Create Link
     * @param Request $request
     */
    public function __invoke(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $link = Link::where('id', $id)->first();
            $linkInfo = LinkInfo::where('link_id', $id)->first();
            $shortUrlData = ShortURL::where('id', $link->short_url_id)->get();
            // $visits = ShortURLVisit::where('short_url_id', $link->short_url_id)->get();
            // $chrome = ShortURLVisit::where('short_url_id', $link->short_url_id)->where('browser', "Chrome")->count();

            // $graph = ShortURLVisit::where('short_url_id', $link->short_url_id)
            //     ->whereMonth('created_at', '>=', '5')
            //     ->get()->unique('MONTH(created_at)');

            $visitors = ShortURLVisit::where('short_url_id', $link->short_url_id)
                ->whereYear('created_at', '2023')
                ->select(
                    "id",
                    DB::raw("(count(short_url_id)) as total_click"),
                    DB::raw("(to_char(created_at, 'YYYY-MM')) as month_year")
                )
                ->orderBy('created_at')
                ->groupBy(DB::raw("to_char(created_at, 'YYYY-MM')"))
                ->groupBy('id')
                ->get();

            $social_traffic = ShortURLVisit::where('short_url_id', $link->short_url_id)
                ->select(
                    "id",
                    DB::raw("COUNT(CASE WHEN browser = 'Chrome' THEN browser ELSE null END)) as chrome"),
                    DB::raw("COUNT(CASE WHEN browser = 'Safari' THEN browser ELSE null END)) as safari"),
                    DB::raw("COUNT(CASE WHEN operating_system = 'OS X' THEN operating_system ELSE null END)) as android"),
                    DB::raw("COUNT(CASE WHEN operating_system = 'iOS' THEN operating_system ELSE null END)) as ios"),
                    DB::raw("COUNT(id) as visit"),
                )
                ->groupBy('id')
                // ->orderBy('browser')
                // ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%M')"))
                ->get();


            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Link Created Successfully',
                'link' => [
                    "link_data" => $link,
                    "link_info" => $linkInfo,
                    "short_url_data" => $shortUrlData,
                    "graph" => $visitors,
                    "social_traffic" => $social_traffic
                ]
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
