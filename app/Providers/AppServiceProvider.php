<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\CategoryRepository;
use App\Repositories\EloquentCategoryRepository;
use App\Repositories\EloquentIdempotencyKeyRepository;
use App\Repositories\EloquentMenuRepository;
use App\Repositories\EloquentOrderItemRepository;
use App\Repositories\EloquentOrderRepository;
use App\Repositories\EloquentOtpCodeRepository;
use App\Repositories\EloquentRestaurantTableRepository;
use App\Repositories\EloquentUserRepository;
use App\Repositories\IdempotencyKeyRepository;
use App\Repositories\MenuRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Repositories\OtpCodeRepository;
use App\Repositories\RestaurantTableRepository;
use App\Repositories\UserRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CategoryRepository::class, EloquentCategoryRepository::class);
        $this->app->bind(MenuRepository::class, EloquentMenuRepository::class);
        $this->app->bind(RestaurantTableRepository::class, EloquentRestaurantTableRepository::class);
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->bind(OrderItemRepository::class, EloquentOrderItemRepository::class);
        $this->app->bind(IdempotencyKeyRepository::class, EloquentIdempotencyKeyRepository::class);
        $this->app->bind(OtpCodeRepository::class, EloquentOtpCodeRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
