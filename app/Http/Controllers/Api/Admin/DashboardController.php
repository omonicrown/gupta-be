<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{ 
    public function getLinksCount(DashboardService $dashboardService): JsonResponse
    {
        try {
            return 
                $dashboardService->getLinksCount();
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }


    public function getSingleUser(Request $request, DashboardService $dashboardService,$id): JsonResponse
    {
        try {
            return $dashboardService->getSingleUser($id);
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }
}
