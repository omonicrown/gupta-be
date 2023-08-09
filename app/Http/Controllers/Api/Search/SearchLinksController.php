<?php

namespace App\Http\Controllers\Api\Search;


use Exception;
use App\Models\Link;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\AllowedFilter;


class SearchLinksController extends Controller
{
    /**
     *
     * @param Request $request
     */
    public function __invoke(Request $request)
    {
        $name = str_replace(' ', '', $request->name);

        $link = Link::whereName($name)->exists();

        if(!$link)
        {
            return response()->json([
                'status' => true,
                'message' => 'link name available'
            ], 200);
        }

    }
}
