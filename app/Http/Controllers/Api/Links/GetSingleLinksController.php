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




            //For SQL 

            $visitors = ShortURLVisit::where('short_url_id', $link->short_url_id)
                ->select(
                    "id",
                    DB::raw("(count(short_url_id)) as total_click"),
                    DB::raw("(DATE_FORMAT(created_at, '%Y-%M')) as month_year")
                )
                ->orderBy('created_at')
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%M')"))
                ->get();


            $social_traffic = ShortURLVisit::where('short_url_id', $link->short_url_id)
                ->select(
                    DB::raw("COUNT(IF(browser = 'Chrome',browser,null)) as chrome"),
                    DB::raw("COUNT(IF(browser = 'Safari',browser,null)) as safari"),
                    DB::raw("COUNT(IF(operating_system = 'OS X',operating_system,null)) as android"),
                    DB::raw("COUNT(IF(operating_system = 'iOS',operating_system,null)) as ios"),
                    DB::raw("COUNT(id) as visit")
                )
                ->get();




            // $visitors = ShortURLVisit::where('short_url_id', $link->short_url_id)
            //     // ->whereYear('created_at', '2023')
            //     ->select(
            //         DB::raw("(count(case when operating_system <> '0' then short_url_id end)) as total_click"),
            //         DB::raw("(to_char(created_at, 'MM-DD')) as month_day")
            //     )
            //     // ->groupBy('created_at')
            //     ->groupBy(DB::raw("to_char(created_at, 'MM-DD')"))
            //     ->get();

            // $social_traffic = ShortURLVisit::where('short_url_id', $link->short_url_id)
            //     ->select(

            //         DB::raw("COUNT(case when browser = 'Chrome' then browser end) as chrome"),
            //         DB::raw("COUNT(case when browser = 'Safari' then browser end) as safari"),
            //         DB::raw("COUNT(case when operating_system = 'OS X' then operating_system end) as macbook"),
            //         DB::raw("COUNT(case when operating_system = 'AndroidOS' then operating_system end) as android"),
            //         DB::raw("COUNT(case when operating_system = 'iOS' then operating_system end) as iphone"),
            //         DB::raw("COUNT(case when operating_system <> '0' then id end) as visit"),
            //     )
            //     // ->groupBy('id')
            //     // ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%M')"))
            //     ->get();


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
