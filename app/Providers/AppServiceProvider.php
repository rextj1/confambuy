<?php

namespace App\Providers;

use App\Listeners\AssignRoleAfterEmailVerified;
use App\Models\Category;
use App\Models\Product;
use App\Observers\CategoryObserver;
use App\Observers\ProductObserver;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Verified::class, AssignRoleAfterEmailVerified::class);

        Category::observe(CategoryObserver::class);
        Product::observe(ProductObserver::class);

        RateLimiter::for('checkout', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'checkout:'.$user->id : 'checkout:'.$request->ip();

            return Limit::perMinute(10)->by($key);
        });
    }
}
