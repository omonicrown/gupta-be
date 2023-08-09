<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Links\CreateLinksController;
use App\Http\Controllers\Api\Links\UpdateLinksController;
use App\Http\Controllers\Api\Links\DeleteLinksController;
use App\Http\Controllers\Api\Links\AddInfoToLinkController;
use App\Http\Controllers\Api\Links\CheckLinkController;
use App\Http\Controllers\Api\Links\UpdateLinkInfoController;
use App\Http\Controllers\Api\Links\CreateRandomLinkController;
use App\Http\Controllers\Api\Links\CreateCatalogController;
use App\Http\Controllers\Api\Links\CreateTieredController;
use App\Http\Controllers\Api\Search\SearchLinksController;
use App\Http\Controllers\Api\Profile\GetProfileController;
use App\Http\Controllers\Api\Profile\UpdatePasswordController;
use App\Http\Controllers\Api\Profile\UpdateProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/register', [AuthController::class, 'createUser']);
Route::post('/auth/login', [AuthController::class, 'loginUser']);
Route::post('/link/create-random-link', CreateRandomLinkController::class);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('session', [AuthController::class, 'session']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::prefix('profile')->group(function (Router $profile) {
        $profile->get('', GetProfileController::class);
        $profile->post('update', UpdateProfileController::class);
        $profile->post('password', UpdatePasswordController::class);
    });

    Route::post('search', SearchLinksController::class);

    Route::prefix('links')->group(function (Router $link) {
        $link->post('', CreateLinksController::class);
        $link->put('update/{id}', UpdateLinksController::class);
        $link->delete('delete/{id}', DeleteLinksController::class);
        $link->post('add-info', AddInfoToLinkController::class);
        $link->post('search', CheckLinkController::class);
        $link->put('update-link-info/{id}', UpdateLinkInfoController::class);
    });

    Route::prefix('links')->group(function (Router $link) {
        $link->post('catalog', CreateCatalogController::class);
        $link->post('tiered', CreateTieredController::class);
    });

});
