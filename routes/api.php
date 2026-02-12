<?php

use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\OrderTrackingController;
use App\Http\Controllers\Api\V1\PaystackController;
use App\Http\Controllers\Api\V1\PricingController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health/live', [HealthCheckController::class, 'live']);
Route::get('/health/ready', [HealthCheckController::class, 'ready']);
Route::get('/health', [HealthCheckController::class, 'ready']);

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Confam Buy API',
        'version' => '1.0.0',
    ]);
});

// Public routes
Route::post('/login', [LoginController::class, 'login']);
/*
|--------------------------------------------------------------------------
| Public Read-Only Resources (Guest OK)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::apiResource('categories', CategoryController::class)
        ->only(['index', 'show']);

    Route::get('products/featured', [ProductController::class, 'featured']);

    Route::apiResource('products', ProductController::class)
        ->only(['index', 'show']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout']);

    Route::get('/me', function (Request $request) {
        return $request->user();
    });

    Route::prefix('v1')->group(function () {
        Route::middleware('role:customer|admin|staff')->group(function () {
            Route::get('orders/{order:uuid}/track', [OrderTrackingController::class, 'track']);
            Route::get('tickets', [SupportTicketController::class, 'index']);
            Route::post('tickets', [SupportTicketController::class, 'store']);
            Route::get('tickets/{supportTicket}', [SupportTicketController::class, 'show']);
            Route::post('tickets/{supportTicket}/messages', [SupportTicketController::class, 'storeMessage']);
            Route::patch('tickets/{supportTicket}', [SupportTicketController::class, 'update'])->middleware('role:admin|staff');
        });

        Route::middleware('role:customer')->group(function () {
            Route::apiResource('addresses', AddressController::class);
            Route::post('pricing/quote', [PricingController::class, 'quote']);
            Route::get('cart', [CartController::class, 'show']);
            Route::patch('cart', [CartController::class, 'update']);
            Route::post('cart/items', [CartController::class, 'addItem']);
            Route::patch('cart/items/{cartItem}', [CartController::class, 'updateItem']);
            Route::delete('cart/items/{cartItem}', [CartController::class, 'removeItem']);
            Route::post('checkout', [CheckoutController::class, 'placeOrder'])->middleware('throttle:checkout');
            Route::post('payments/paystack/initialize', [PaystackController::class, 'initialize']);
            Route::get('payments/paystack/verify/{reference}', [PaystackController::class, 'verify']);
        });

        Route::middleware('role:admin|staff')->group(function () {
            Route::apiResource('categories', CategoryController::class)
                ->only(['store', 'update', 'destroy']);

            Route::apiResource('products', ProductController::class)
                ->only(['store', 'update', 'destroy']);
        });
    });
});

Route::post('/v1/payments/webhook', [WebhookController::class, 'payments']);
