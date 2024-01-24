<?php
use App\Http\Controllers\Api\Payment\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Links\CreateLinksController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ManageUsersController;
use App\Http\Controllers\Api\Links\UpdateLinksController;
use App\Http\Controllers\Api\Links\DeleteLinksController;
use App\Http\Controllers\Api\Links\AddInfoToLinkController;
use App\Http\Controllers\Api\Links\CheckLinkController;
use App\Http\Controllers\Api\Links\UpdateLinkInfoController;
use App\Http\Controllers\Api\Links\CreateRandomLinkController;
use App\Http\Controllers\Api\Links\CreateCatalogController;
use App\Http\Controllers\Api\Links\CreateRandomUrlController;
use App\Http\Controllers\Api\Links\CreateRedirectController;
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
Route::post('auth/forgot', [AuthController::class, 'forgot']);
Route::post('auth/reset', [AuthController::class, 'reset']);
Route::post('/link/create-random-link', CreateRandomLinkController::class);
Route::post('/link/create-random-url', CreateRandomUrlController::class);  
Route::post('/auth/verify-mail', [AuthController::class, 'verifyEmail']);

Route::post('payment/pay-for-product', [PaymentController::class, 'makeOutsideProductPaymentWithFlutterwave']); 
Route::get('product-payment-callback', [PaymentController::class, 'paymentCallbackForProduct']);
// Route::post('payment/pay-for-product', [PaymentController::class, 'makeOutsideProductPaymentWithFlutterwave']); 


Route::put('update-user-role/{id}', [ManageUsersController::class, 'updateUserRole']);

Route::get('/auth/test', [AuthController::class, 'testChunk']);

Route::get('get-products-by-link-name/{name}', [ProductController::class, 'getProductsByLinkName']);
Route::get('get-single-product-outside/{id}', [ProductController::class, 'getSingleProduct']);

Route::get('/links/get-tiered-link/{linkName}', [UpdateTieredController::class, 'getLinkDetailByName']);

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['middleware' => ['SubStatus']], function (Router $link) {
        Route::get('session', [AuthController::class, 'session']);
        Route::get('getRedirectLinks', [AuthController::class, 'redirectLinks']);
        Route::get('getlinksShort', [AuthController::class, 'getLinksShort']);
        Route::get('getlinks', [AuthController::class, 'getLinks']);
        Route::get('getlinksAll', [AuthController::class, 'getLinksAll']);
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
            $link->post('create-redirect-link', CreateRedirectController::class);
            $link->get('link-details/{id}', GetSingleLinksController::class);
            $link->put('update/{id}', UpdateLinksController::class);
            $link->delete('delete/{id}', DeleteLinksController::class);
            $link->post('add-info', AddInfoToLinkController::class);
            $link->post('search', CheckLinkController::class);
            $link->put('update-link-info/{id}', UpdateLinkInfoController::class);
        });

        Route::prefix('market-links')->group(function (Router $link) {
            $link->post('', [MarketLinkController::class, 'CreateMarketLink']);
            $link->post('check-market-link', [MarketLinkController::class, 'checkMarketLink']);
            $link->post('create-product', [ProductController::class, 'CreateProduct']);
            $link->post('update-product', [ProductController::class, 'UpdateProduct']);
            $link->post('update-market-link', [MarketLinkController::class, 'UpdateMarketLink']);
            $link->get('get-market-links', [MarketLinkController::class, 'getLinks']);
            $link->get('get-products', [ProductController::class, 'getAllProducts']);
            $link->get('get-single-product/{id}', [ProductController::class, 'getSingleProduct']);
            $link->delete('delete-product/{id}', [ProductController::class, 'deleteProduct']);
            $link->delete('delete-market-link/{id}', [MarketLinkController::class, 'deleteMarketLink']);
            $link->post('update-image-1', [ProductController::class, 'updateProductImage1']);
            $link->post('update-product-data', [ProductController::class, 'updateProductData']);
        });

        Route::prefix('links')->group(function (Router $link) {
            $link->post('catalog', CreateCatalogController::class);
            $link->delete('delete-tiered-link/{id}', [UpdateTieredController::class, 'DeleteLink']);
            $link->post('update-tiered-link', [UpdateTieredController::class, 'updateTieredLink']);
            $link->put('update-tiered-image/{id}', [UpdateTieredController::class, 'updateLogo']);
            $link->get('get-tiered-links/{linkName}', [UpdateTieredController::class, 'getLinkDetails']);
            // $link->get('get-tiered-link/{linkName}',[UpdateTieredController::class, 'getLinkDetailByName']);
            $link->post('tiered', CreateTieredController::class);
        });

    });

    Route::prefix('payment')->group(function () { 
        Route::post('make-payment', [PaymentController::class, 'makePayment']);
        Route::post('pay-to-customers', [PaymentController::class, 'payOutCustomers']);
        Route::get('callback', [PaymentController::class, 'paymentCallback']);
        Route::post('pay-for-course', [PaymentController::class, 'payForCourse']);
        Route::get('get-wallet-details', [PaymentController::class, 'walletDetails']);
        Route::get('get-transations', [PaymentController::class, 'transactionDetails']);
    });


    Route::prefix('admin')->group(function (Router $link) {
        Route::group(['middleware' => ['isAdmin']], function (Router $link) {
            //dashboard Apis
            $link->get('get-links-count', [DashboardController::class, 'getLinksCount']);
            Route::get('get-single-user/{id}', [DashboardController::class, 'getSingleUser']);
            //manage user api
            $link->get('get-all-users', [ManageUsersController::class, 'getAllUsers']);
            $link->get('get-all-details/{id}', [ManageUsersController::class, 'getUserDetails']);
            $link->put('update-user-status/{id}', [ManageUsersController::class, 'updateUserStatus']);
            $link->get('get-user-whatsapp-link/{id}', [ManageUsersController::class, 'getUserWhatsappLinks']);
            $link->get('get-user-url-link/{id}', [ManageUsersController::class, 'getUserUrlLinks']);
            $link->get('get-user-multi-link/{id}', [ManageUsersController::class, 'getUserMultiLinks']);
            $link->get('get-user-market-link/{id}', [ManageUsersController::class, 'getUserMarketLinks']);
            $link->post('tiered', CreateTieredController::class);
        });
    });

});
