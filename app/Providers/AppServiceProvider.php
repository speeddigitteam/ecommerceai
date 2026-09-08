<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        View::composer([
            'components.admin-layout',
            'components.admin-sidebar',
            'components.admin-topbar',
            'components.customer-layout',
            'layouts.app',
            'layouts.guest',
            'welcome',
            'catalog.show',
            'products.form',
            'storefront.*',
            'components.storefront-layout',
        ], function (\Illuminate\View\View $view): void {
            $view->with('websiteSettings', Schema::hasTable('website_settings')
                ? WebsiteSetting::query()->first()
                : null);

            if ($view->name() === 'components.storefront-layout') {
                $view->with('storeNavigationCategories', Schema::hasTable('categories')
                    ? Category::query()->with('childrenRecursive')->whereNull('parent_id')->orderBy('name')->limit(14)->get()
                    : collect());
            }

            if (in_array($view->name(), ['components.admin-topbar', 'components.admin-sidebar'], true)) {
                $view->with('adminUnreadOrderCount', Schema::hasColumn('orders', 'viewed_at')
                    ? Order::query()->whereNull('viewed_at')->count()
                    : 0);
            }
        });
    }
}
