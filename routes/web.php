<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AiContentController;
use App\Http\Controllers\AiContentSettingController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnalyticsSettingController;
use App\Http\Controllers\BackupSettingController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CourierIntegrationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\DeliveryChargeSettingController;
use App\Http\Controllers\DigitalDownloadController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FaqSettingController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\FlashSaleSettingController;
use App\Http\Controllers\HeroSettingController;
use App\Http\Controllers\IncomeCategoryController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\MediaAssetController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecaptchaSettingController;
use App\Http\Controllers\ServiceMarqueeSettingController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StorefrontBlogController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\StorefrontOrderTrackingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebsiteContentController;
use App\Http\Controllers\WebsiteSettingController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Unit;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');
Route::get('/shop', [StorefrontController::class, 'index'])->name('storefront.shop');
Route::get('/best-selling-products', [StorefrontController::class, 'bestSelling'])->name('storefront.best-selling');
Route::get('/flash-sale', [StorefrontController::class, 'flashSale'])->name('storefront.flash-sale');
Route::get('/new-arrivals', [StorefrontController::class, 'newArrivals'])->name('storefront.new-arrivals');
Route::get('/latest-products', [StorefrontController::class, 'latestProducts'])->name('storefront.latest-products');
Route::get('/blogs', [StorefrontBlogController::class, 'index'])->name('storefront.blog.index');
Route::get('/blogs/{blogPost}', [StorefrontBlogController::class, 'show'])->name('storefront.blog.show');
Route::get('/track/order', [StorefrontOrderTrackingController::class, 'index'])->name('storefront.orders.track');
Route::post('/track/order', [StorefrontOrderTrackingController::class, 'show'])->middleware('throttle:10,1')->name('storefront.orders.track.show');
Route::post('/newsletter/subscribe', [NewsletterSubscriptionController::class, 'store'])->middleware('throttle:10,1')->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterSubscriptionController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/shop/{product:slug}', fn (Product $product) => to_route('catalog.show', $product->slug, 301))->name('storefront.legacy-show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/order/{product}', [CheckoutController::class, 'directStore'])->name('direct-order.store');
Route::get('/order-success/{order:order_number}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/downloads/{order}/{item}', [DigitalDownloadController::class, 'show'])->middleware('signed')->name('downloads.show');
Route::get('/img/{mediaAsset:slug}', MediaAssetController::class)->name('media.show');

Route::get('/dashboard', function () {
    return view('dashboard', [
        'brands' => Brand::query()->orderBy('name')->get(),
        'categories' => Category::query()->with('childrenRecursive')->whereNull('parent_id')->orderBy('name')->get(),
        'units' => Unit::query()->orderBy('name')->get(),
        'adminUnreadOrderCount' => Order::query()->whereNull('viewed_at')->count(),
        'websiteSettings' => WebsiteSetting::query()->first(),
    ]);
})->middleware(['admin', 'verified'])->name('dashboard');

Route::middleware(['admin', 'verified'])->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::controller(BlogController::class)->prefix('blog')->name('blog.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/posts/{blogPost}', 'show')->name('show');
        Route::get('/posts/{blogPost}/edit', 'edit')->name('edit');
        Route::put('/posts/{blogPost}', 'update')->name('update');
        Route::delete('/posts/{blogPost}', 'destroy')->name('destroy');
    });
    Route::controller(BlogCategoryController::class)->prefix('blog/categories')->group(function () {
        Route::get('/', 'index')->name('blog.categories');
        Route::post('/', 'store')->name('blog.categories.store');
        Route::get('/{blogCategory}', 'show')->name('blog.categories.show');
        Route::get('/{blogCategory}/edit', 'edit')->name('blog.categories.edit');
        Route::put('/{blogCategory}', 'update')->name('blog.categories.update');
        Route::delete('/{blogCategory}', 'destroy')->name('blog.categories.destroy');
    });

    Route::get('/file-manager', [FileManagerController::class, 'index'])->name('file-manager.index');
    Route::post('/file-manager/upload', [FileManagerController::class, 'upload'])->name('file-manager.upload');
    Route::post('/file-manager/folders', [FileManagerController::class, 'createFolder'])->name('file-manager.folders.store');
    Route::patch('/file-manager/items', [FileManagerController::class, 'rename'])->name('file-manager.items.rename');
    Route::patch('/file-manager/images/seo', [FileManagerController::class, 'updateImageSeo'])->name('file-manager.images.seo');
    Route::delete('/file-manager/items', [FileManagerController::class, 'destroy'])->name('file-manager.items.destroy');
    Route::get('/file-manager/download', [FileManagerController::class, 'download'])->name('file-manager.download');

    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::get('/orders/notifications', [OrderController::class, 'notifications'])->name('orders.notifications');
    Route::get('/orders/{order}/print-label', [OrderController::class, 'printLabel'])->name('orders.print-label');
    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status.update');
    Route::post('/orders/{order}/courier', [OrderController::class, 'sendToCourier'])->name('orders.courier.send');
    Route::post('/orders/{order}/courier/sync', [OrderController::class, 'syncCourier'])->name('orders.courier.sync');

    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index')->name('categories.index');
        Route::post('/categories', 'store')->name('categories.store');
        Route::get('/categories/{category}', 'show')->name('categories.show');
        Route::get('/categories/{category}/edit', 'edit')->name('categories.edit');
        Route::put('/categories/{category}', 'update')->name('categories.update');
        Route::delete('/categories/{category}', 'destroy')->name('categories.destroy');
    });

    Route::controller(BrandController::class)->group(function () {
        Route::get('/brands', 'index')->name('brands.index');
        Route::post('/brands', 'store')->name('brands.store');
        Route::put('/brands/{brand}', 'update')->name('brands.update');
        Route::delete('/brands/{brand}', 'destroy')->name('brands.destroy');
    });

    Route::controller(UnitController::class)->group(function () {
        Route::get('/units', 'index')->name('units.index');
        Route::post('/units', 'store')->name('units.store');
        Route::put('/units/{unit}', 'update')->name('units.update');
        Route::delete('/units/{unit}', 'destroy')->name('units.destroy');
    });

    Route::get('/settings/ai-content', [AiContentSettingController::class, 'edit'])->name('settings.ai-content.edit');
    Route::put('/settings/ai-content', [AiContentSettingController::class, 'update'])->name('settings.ai-content.update');
    Route::post('/ai-content/generate', AiContentController::class)->middleware('throttle:10,1')->name('ai-content.generate');

    Route::get('/settings/website', [WebsiteSettingController::class, 'edit'])->name('settings.website.edit');
    Route::put('/settings/website', [WebsiteSettingController::class, 'update'])->name('settings.website.update');
    Route::get('/settings/delivery-charges', [DeliveryChargeSettingController::class, 'edit'])->name('settings.delivery-charges.edit');
    Route::put('/settings/delivery-charges', [DeliveryChargeSettingController::class, 'update'])->name('settings.delivery-charges.update');
    Route::get('/settings/content', [WebsiteContentController::class, 'edit'])->name('settings.content.edit');
    Route::put('/settings/content', [WebsiteContentController::class, 'update'])->name('settings.content.update');
    Route::get('/settings/hero', [HeroSettingController::class, 'edit'])->name('settings.hero.edit');
    Route::put('/settings/hero', [HeroSettingController::class, 'update'])->name('settings.hero.update');
    Route::get('/settings/service-marquee', [ServiceMarqueeSettingController::class, 'edit'])->name('settings.service-marquee.edit');
    Route::get('/settings/recaptcha', [RecaptchaSettingController::class, 'edit'])->name('settings.recaptcha.edit');
    Route::get('/settings/faq', [FaqSettingController::class, 'edit'])->name('settings.faq.edit');
    Route::get('/settings/flash-sale', [FlashSaleSettingController::class, 'edit'])->name('settings.flash-sale.edit');
    Route::put('/settings/flash-sale', [FlashSaleSettingController::class, 'update'])->name('settings.flash-sale.update');
    Route::put('/settings/faq', [FaqSettingController::class, 'update'])->name('settings.faq.update');
    Route::put('/settings/recaptcha', [RecaptchaSettingController::class, 'update'])->name('settings.recaptcha.update');
    Route::put('/settings/service-marquee', [ServiceMarqueeSettingController::class, 'update'])->name('settings.service-marquee.update');
    Route::get('/settings/backup', [BackupSettingController::class, 'edit'])->name('settings.backup.edit');
    Route::put('/settings/backup', [BackupSettingController::class, 'update'])->name('settings.backup.update');
    Route::post('/settings/backup/run', [BackupSettingController::class, 'run'])->name('settings.backup.run');
    Route::get('/settings/backup/download', [BackupSettingController::class, 'download'])->name('settings.backup.download');
    Route::delete('/settings/backup', [BackupSettingController::class, 'destroy'])->name('settings.backup.destroy');
    Route::get('/settings/analytics', [AnalyticsSettingController::class, 'edit'])->name('settings.analytics.edit');
    Route::put('/settings/analytics', [AnalyticsSettingController::class, 'update'])->name('settings.analytics.update');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/newsletter', [NewsletterController::class, 'index'])->name('newsletter.index');
    Route::get('/newsletter/export', [NewsletterController::class, 'export'])->name('newsletter.export');
    Route::delete('/newsletter/{subscriber}', [NewsletterController::class, 'destroy'])->name('newsletter.destroy');

    Route::get('/courier-integrations', [CourierIntegrationController::class, 'index'])->name('courier-integrations.index');
    Route::post('/courier-integrations', [CourierIntegrationController::class, 'store'])->name('courier-integrations.store');
    Route::post('/courier-integrations/{courierIntegration}/test', [CourierIntegrationController::class, 'testConnection'])->name('courier-integrations.test');
    Route::patch('/courier-integrations/{courierIntegration}/toggle', [CourierIntegrationController::class, 'toggle'])->name('courier-integrations.toggle');
    Route::delete('/courier-integrations/{courierIntegration}', [CourierIntegrationController::class, 'destroy'])->name('courier-integrations.destroy');

    Route::get('/accounting/income', [AccountingController::class, 'income'])->name('accounting.income');
    Route::get('/accounting/income-categories', [AccountingController::class, 'incomeCategories'])->name('accounting.income-categories');
    Route::get('/accounting/expenses', [AccountingController::class, 'expenses'])->name('accounting.expenses');
    Route::get('/accounting/expense-categories', [AccountingController::class, 'expenseCategories'])->name('accounting.expense-categories');
    Route::get('/accounting/reports', [AccountingController::class, 'reports'])->name('accounting.reports');
    Route::post('/accounting/incomes', [IncomeController::class, 'store'])->name('incomes.store');
    Route::put('/accounting/incomes/{income}', [IncomeController::class, 'update'])->name('incomes.update');
    Route::delete('/accounting/incomes/{income}', [IncomeController::class, 'destroy'])->name('incomes.destroy');
    Route::post('/accounting/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('/accounting/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/accounting/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::post('/accounting/income-categories', [IncomeCategoryController::class, 'store'])->name('income-categories.store');
    Route::put('/accounting/income-categories/{incomeCategory}', [IncomeCategoryController::class, 'update'])->name('income-categories.update');
    Route::delete('/accounting/income-categories/{incomeCategory}', [IncomeCategoryController::class, 'destroy'])->name('income-categories.destroy');
    Route::post('/accounting/expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
    Route::put('/accounting/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
    Route::delete('/accounting/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

    Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::resource('products', ProductController::class)->except('show');
    Route::get('/products/{product}/preview', [ProductController::class, 'show'])->name('products.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/account', CustomerDashboardController::class)->name('customer.dashboard');
    Route::get('/account/reviews', [ProductReviewController::class, 'index'])->name('customer.reviews.index');
    Route::post('/account/reviews/{product}', [ProductReviewController::class, 'store'])->name('customer.reviews.store');
    Route::put('/account/reviews/{review}', [ProductReviewController::class, 'update'])->name('customer.reviews.update');
    Route::delete('/account/reviews/{review}', [ProductReviewController::class, 'destroy'])->name('customer.reviews.destroy');
    Route::get('/account/password', [ProfileController::class, 'password'])->name('customer.password.edit');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/{slug}', CatalogController::class)->name('catalog.show');
