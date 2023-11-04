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
use App\Http\Controllers\Api\Links\CreateRandomUrlController;
use App\Http\Controllers\Api\Links\CreateTieredController;
use App\Http\Controllers\Api\Links\GetSingleLinksController;
use App\Http\Controllers\Api\Links\UpdateTieredController;
use App\Http\Controllers\Api\MarketPlace\MarketLinkController;
use App\Http\Controllers\Api\MarketPlace\ProductController;
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
Route::post('/link/create-random-url', CreateRandomUrlController::class);

Route::get('get-products-by-link-name/{name}', [ProductController::class,'getProductsByLinkName']);

Route::get('/links/get-tiered-link/{linkName}', [UpdateTieredController::class, 'getLinkDetailByName']);

Route::middleware('auth:sanctum')->group(function () { 

    Route::get('session', [AuthController::class, 'session']);
    Route::get('getlinks', [AuthController::class, 'getLinks']);  
    Route::get('get-multi-links', [AuthController::class, 'getMultiLinks']);

    Route::get('getlinksByName/{name}', [AuthController::class, 'getlinksByName']);

    Route::post('logout', [AuthController::class, 'logout']);

    Route::prefix('profile')->group(function (Router $profile) {
        $profile->get('', GetProfileController::class);
        $profile->post('update', UpdateProfileController::class);
        $profile->post('password', UpdatePasswordController::class);
    });

    Route::post('search', SearchLinksController::class); 

    Route::prefix('links')->group(function (Router $link) {
        $link->post('', CreateLinksController::class);
        $link->get('link-details/{id}', GetSingleLinksController::class);
        $link->put('update/{id}', UpdateLinksController::class);
        $link->delete('delete/{id}', DeleteLinksController::class);
        $link->post('add-info', AddInfoToLinkController::class);
        $link->post('search', CheckLinkController::class);
        $link->put('update-link-info/{id}', UpdateLinkInfoController::class);
    });

    Route::prefix('market-links')->group(function (Router $link) {  
        $link->post('', [MarketLinkController::class,'CreateMarketLink']);  
        $link->post('check-market-link', [MarketLinkController::class,'checkMarketLink']);   
        $link->post('create-product', [ProductController::class,'CreateProduct']);
        $link->post('update-product', [ProductController::class,'UpdateProduct']);
        $link->get('get-market-links', [MarketLinkController::class,'getLinks']); 
        $link->get('get-products', [ProductController::class,'getAllProducts']); 
        $link->get('get-single-product/{id}', [ProductController::class,'getSingleProduct']); 
        $link->delete('delete-product/{id}', [ProductController::class,'deleteProduct']); 
        $link->post('update-image-1', [ProductController::class,'updateProductImage1']); 
        $link->post('update-product-data', [ProductController::class,'updateProductData']);
    });

    Route::prefix('links')->group(function (Router $link) {
        $link->post('catalog', CreateCatalogController::class);
        $link->delete('delete-tiered-link/{id}',[UpdateTieredController::class, 'DeleteLink']);
        $link->post('update-tiered-link',[UpdateTieredController::class, 'updateTieredLink']);
        $link->put('update-tiered-image/{id}',[UpdateTieredController::class, 'updateLogo']); 
        $link->get('get-tiered-links/{linkName}',[UpdateTieredController::class, 'getLinkDetails']);
        // $link->get('get-tiered-link/{linkName}',[UpdateTieredController::class, 'getLinkDetailByName']);
        $link->post('tiered', CreateTieredController::class);
    });

});
