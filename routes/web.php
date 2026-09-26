<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\BuyerController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectExpenseController;
use App\Http\Controllers\Admin\ProjectMaterialController;
use App\Http\Controllers\Admin\ProjectMediaController;
use App\Http\Controllers\Admin\ProjectPaymentController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleAccessController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SolarLeadController;
use App\Http\Controllers\Admin\SolarPackageController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\StoreSelectionController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\TraineeController;
use App\Http\Controllers\Admin\TrainingController;
use App\Http\Controllers\Admin\WebsiteController;
use App\Http\Controllers\Buyer\DashboardController as BuyerDashboardController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StorefrontController;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ==========================================================
// PUBLIC WEBSITE (Phase 3 expands this heavily)
// ==========================================================
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::get('/', function () {
    $featuredProducts = Product::where('is_visible_online', true)
        ->where('is_featured', true)
        ->whereHas('images')
        ->with('images')
        ->orderBy('name')
        ->limit(12)
        ->get()
        ->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (int) $p->selling_price,
            'image' => $p->images->first()?->path,
            'category' => $p->category?->name ?? 'Solar',
        ])
        ->filter(fn ($p) => $p['image'] !== null)
        ->values();

    $whatsappNumber = preg_replace('/\D/', '', (string) Setting::where('key', 'business.phone')->value('value'));

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'featuredProducts' => $featuredProducts,
        'whatsappNumber' => $whatsappNumber,
    ]);
})->name('home');

Route::get('/about', function () {
    $whatsappNumber = preg_replace('/\D/', '', (string) Setting::where('key', 'business.phone')->value('value'));

    return Inertia::render('About', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'whatsappNumber' => $whatsappNumber,
    ]);
})->name('about');

Route::get('/contact', function () {
    $whatsappNumber = preg_replace('/\D/', '', (string) Setting::where('key', 'business.phone')->value('value'));

    return Inertia::render('Contact', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'whatsappNumber' => $whatsappNumber,
    ]);
})->name('contact');

Route::get('/shop', [StorefrontController::class, 'shop'])->name('shop');
Route::get('/shop/{product}', [StorefrontController::class, 'product'])->name('products.show');

Route::get('/cart', [CheckoutController::class, 'cart'])->name('cart');
Route::get('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/orders/{order:ref_id}/pay', [CheckoutController::class, 'pay'])->name('orders.pay');
Route::post('/orders/{order:ref_id}/paystack', [CheckoutController::class, 'paystack'])->name('orders.paystack');
Route::get('/orders/{order:ref_id}/paystack/verify', [CheckoutController::class, 'verify'])->name('orders.paystack.verify');

Route::get('/packages', [StorefrontController::class, 'packages'])->name('packages');
Route::get('/packages/{solarPackage}', [StorefrontController::class, 'packageShow'])->name('packages.show');
Route::get('/packages/{solarPackage}/pay/bank-transfer', [StorefrontController::class, 'packageBankTransfer'])->name('packages.pay.bank-transfer');
Route::post('/packages/{solarPackage}/paystack', [StorefrontController::class, 'packagePaystack'])->name('packages.paystack');
Route::get('/packages/{solarPackage}/payment/callback', [StorefrontController::class, 'packagePaymentCallback'])->name('packages.payment.callback');
Route::post('/packages/{solarPackage}/offline', [StorefrontController::class, 'packageOffline'])->name('packages.offline');

Route::get('/calculator', [StorefrontController::class, 'calculator'])->name('calculator');
Route::post('/calculator', [StorefrontController::class, 'calculatorStore'])->middleware('throttle:10,1')->name('calculator.store');

Route::get('/projects', [StorefrontController::class, 'projects'])->name('projects');

Route::get('/training', [StorefrontController::class, 'training'])->name('training');
Route::get('/training/{training}', [StorefrontController::class, 'trainingShow'])->name('training.show');
Route::get('/training/{training}/pay/bank-transfer', [StorefrontController::class, 'trainingBankTransfer'])->name('training.pay.bank-transfer');
Route::post('/training/{training}/paystack', [StorefrontController::class, 'trainingPaystack'])->name('training.paystack');
Route::get('/training/{training}/payment/callback', [StorefrontController::class, 'trainingPaymentCallback'])->name('training.payment.callback');

Route::post('/feedback', [StorefrontController::class, 'feedback'])->middleware('throttle:10,1')->name('feedback.store');
Route::post('/newsletter', [StorefrontController::class, 'newsletter'])->middleware('throttle:10,1')->name('newsletter.store');
Route::get('/newsletter/unsubscribe/{token}', [StorefrontController::class, 'newsletterUnsubscribe'])->name('newsletter.unsubscribe');

// ==========================================================
// PRIVATE BUSINESS SYSTEM (Admin)
// ==========================================================
Route::get('/admin', [DashboardController::class, '__invoke'])
    ->middleware(['auth', 'permission:dashboard.view'])
    ->name('admin.dashboard');

// Breeze auth controllers redirect to named route "dashboard" after login/register.
Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->role?->slug === 'buyer') {
        return redirect()->route('buyer.dashboard');
    }

    return redirect()->route('admin.dashboard');
})
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('search', [SearchController::class, 'index'])->middleware('permission:dashboard.view')->name('search');

    // Custom product routes (must come before resource)
    Route::get('products/movements', [ProductController::class, 'stockMovements'])->middleware('permission:inventory.view')->name('products.movements');
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->middleware('permission:inventory.adjust')->name('products.adjust-stock');
    Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete'])->middleware('permission:products.delete')->name('products.bulk-delete');
    Route::post('products/bulk-status', [ProductController::class, 'bulkStatus'])->middleware('permission:products.edit')->name('products.bulk-status');
    Route::post('products/bulk-featured', [ProductController::class, 'bulkFeatured'])->middleware('permission:products.edit')->name('products.bulk-featured');
    Route::post('products/bulk-online', [ProductController::class, 'bulkOnline'])->middleware('permission:products.edit')->name('products.bulk-online');
    Route::post('products/bulk-brand', [ProductController::class, 'bulkBrand'])->middleware('permission:products.edit')->name('products.bulk-brand');
    Route::post('products/bulk-adjust-stock', [ProductController::class, 'bulkAdjustStock'])->middleware('permission:inventory.adjust')->name('products.bulk-adjust-stock');
    Route::post('products/bulk-store', [ProductController::class, 'bulkStore'])->middleware('permission:products.edit')->name('products.bulk-store');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete')->name('products.destroy');

    Route::resource('products', ProductController::class)->except(['destroy']);
    Route::get('stock/movements', [ProductController::class, 'stockMovements'])->middleware('permission:inventory.view')->name('stock.movements');
    Route::get('stock/adjust', [ProductController::class, 'adjustStockPage'])->middleware('permission:inventory.adjust')->name('stock.adjust');
    Route::post('stock/adjust', [ProductController::class, 'adjustStockAction'])->middleware('permission:inventory.adjust')->name('stock.adjust.submit');
    Route::get('stock/receive', fn () => redirect()->route('admin.purchases.create'))->name('stock.receive');

    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('sales/{sale}/void', [SaleController::class, 'destroy'])->middleware('permission:sales.void')->name('sales.void');
    Route::get('sales/{sale}/invoice', [SaleController::class, 'downloadInvoice'])->middleware('permission:sales.view')->name('sales.invoice');

    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('purchases/{purchase}/void', [PurchaseController::class, 'destroy'])->middleware('permission:purchases.void')->name('purchases.void');

    Route::resource('customers', CustomerController::class);
    Route::post('customers/quick', [CustomerController::class, 'quickCreate'])->name('customers.quick');
    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);
    Route::post('suppliers/quick', [SupplierController::class, 'quickCreate'])->name('suppliers.quick');

    Route::get('brands', [BrandController::class, 'index'])->middleware('permission:products.view')->name('brands.index');
    Route::get('brands/{brand}', [BrandController::class, 'show'])->middleware('permission:products.view')->name('brands.show');
    Route::post('brands', [BrandController::class, 'store'])->middleware('permission:products.create')->name('brands.store');
    Route::put('brands/{brand}', [BrandController::class, 'update'])->middleware('permission:products.edit')->name('brands.update');
    Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->middleware('permission:products.delete')->name('brands.destroy');
    Route::post('brands/quick', [BrandController::class, 'quickCreate'])->middleware('permission:products.create')->name('brands.quick');

    Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments.index');
    Route::post('payments', [PaymentController::class, 'store'])->middleware('permission:payments.record')->name('payments.store');
    Route::post('payments/paystack', [PaymentController::class, 'paystack'])->middleware('permission:payments.record')->name('payments.paystack');
    Route::post('payments/send-link', [PaymentController::class, 'sendPaymentLinkEmail'])->middleware('permission:payments.record')->name('payments.send-link');

    Route::get('orders', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
    Route::post('orders/{order}/pay/{payment}', [OrderController::class, 'confirmPayment'])->middleware('permission:orders.manage')->name('orders.confirm-payment');
    Route::post('orders/{order}/deliver', [OrderController::class, 'deliver'])->middleware('permission:orders.manage')->name('orders.deliver');

    Route::get('buyers', [BuyerController::class, 'index'])->middleware('permission:orders.view')->name('buyers.index');
    Route::get('buyers/{buyer}', [BuyerController::class, 'show'])->middleware('permission:orders.view')->name('buyers.show');
    Route::get('buyers/{buyer}/edit', [BuyerController::class, 'edit'])->middleware('permission:orders.manage')->name('buyers.edit');
    Route::put('buyers/{buyer}', [BuyerController::class, 'update'])->middleware('permission:orders.manage')->name('buyers.update');
    Route::delete('buyers/{buyer}', [BuyerController::class, 'destroy'])->middleware('permission:orders.manage')->name('buyers.destroy');
    Route::post('buyers/{buyer}/impersonate', [BuyerController::class, 'impersonate'])->middleware('permission:orders.manage')->name('buyers.impersonate');

    Route::resource('expenses', ExpenseController::class)->except(['show']);

    Route::get('projects', [ProjectController::class, 'index'])->middleware('permission:projects.view')->name('projects.index');
    Route::get('projects/create', [ProjectController::class, 'create'])->middleware('permission:projects.create')->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->middleware('permission:projects.create')->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->middleware('permission:projects.view')->name('projects.show');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->middleware('permission:projects.edit')->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->middleware('permission:projects.edit')->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->middleware('permission:projects.delete')->name('projects.destroy');
    Route::post('projects/{project}/status', [ProjectController::class, 'status'])->middleware('permission:projects.edit')->name('projects.status');

    Route::post('projects/{project}/materials', [ProjectMaterialController::class, 'store'])->middleware('permission:projects.materials')->name('projects.materials.store');
    Route::post('projects/{project}/materials/{material}/issue', [ProjectMaterialController::class, 'issue'])->middleware('permission:projects.materials')->name('projects.materials.issue');
    Route::delete('projects/{project}/materials/{material}', [ProjectMaterialController::class, 'destroy'])->middleware('permission:projects.materials')->name('projects.materials.destroy');

    Route::post('projects/{project}/payments', [ProjectPaymentController::class, 'store'])->middleware('permission:projects.payments')->name('projects.payments.store');
    Route::delete('projects/{project}/payments/{payment}', [ProjectPaymentController::class, 'destroy'])->middleware('permission:projects.payments')->name('projects.payments.destroy');

    Route::post('projects/{project}/expenses', [ProjectExpenseController::class, 'store'])->middleware('permission:projects.expenses')->name('projects.expenses.store');
    Route::delete('projects/{project}/expenses/{expense}', [ProjectExpenseController::class, 'destroy'])->middleware('permission:projects.expenses')->name('projects.expenses.destroy');

    Route::post('projects/{project}/media', [ProjectMediaController::class, 'store'])->middleware('permission:projects.media')->name('projects.media.store');
    Route::post('projects/{project}/media/{media}/publish', [ProjectMediaController::class, 'publish'])->middleware('permission:projects.media')->name('projects.media.publish');
    Route::delete('projects/{project}/media/{media}', [ProjectMediaController::class, 'destroy'])->middleware('permission:projects.media')->name('projects.media.destroy');

    Route::get('staff', [StaffController::class, 'index'])->middleware('permission:staff.view')->name('staff.index');
    Route::get('staff/create', [StaffController::class, 'create'])->middleware('permission:staff.manage')->name('staff.create');
    Route::post('staff', [StaffController::class, 'store'])->middleware('permission:staff.manage')->name('staff.store');
    Route::get('staff/{staff}', [StaffController::class, 'show'])->middleware('permission:staff.view')->name('staff.show');
    Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])->middleware('permission:staff.manage')->name('staff.edit');
    Route::put('staff/{staff}', [StaffController::class, 'update'])->middleware('permission:staff.manage')->name('staff.update');
    Route::delete('staff/{staff}', [StaffController::class, 'destroy'])->middleware('permission:staff.manage')->name('staff.destroy');

    // Positions (for staff roles)
    Route::get('positions', [StaffController::class, 'positionsIndex'])->middleware('permission:staff.manage')->name('positions.index');
    Route::post('positions', [StaffController::class, 'positionStore'])->middleware('permission:staff.manage')->name('positions.store');
    Route::put('positions/{position}', [StaffController::class, 'positionUpdate'])->middleware('permission:staff.manage')->name('positions.update');
    Route::delete('positions/{position}', [StaffController::class, 'positionDestroy'])->middleware('permission:staff.manage')->name('positions.destroy');

    Route::resource('stores', StoreController::class)
        ->parameters(['stores' => 'store:code']);
    Route::post('stores/transfer', [StoreController::class, 'transferStock'])->name('stores.transfer');

    Route::post('store/select', [StoreSelectionController::class, 'select'])->name('store.select');

    // ---- Academy: training programs, trainees, enrollments & certificates ----
    Route::get('training', [TrainingController::class, 'index'])->middleware('permission:training.view')->name('training.index');
    Route::get('training/create', [TrainingController::class, 'create'])->middleware('permission:training.manage')->name('training.create');
    Route::post('training', [TrainingController::class, 'store'])->middleware('permission:training.manage')->name('training.store');
    Route::get('training/{training}', [TrainingController::class, 'show'])->middleware('permission:training.view')->name('training.show');
    Route::get('training/{training}/edit', [TrainingController::class, 'edit'])->middleware('permission:training.manage')->name('training.edit');
    Route::put('training/{training}', [TrainingController::class, 'update'])->middleware('permission:training.manage')->name('training.update');
    Route::delete('training/{training}', [TrainingController::class, 'destroy'])->middleware('permission:training.manage')->name('training.destroy');

    Route::get('trainees', [TraineeController::class, 'index'])->middleware('permission:training.view')->name('trainees.index');
    Route::get('trainees/create', [TraineeController::class, 'create'])->middleware('permission:training.manage')->name('trainees.create');
    Route::post('trainees', [TraineeController::class, 'store'])->middleware('permission:training.manage')->name('trainees.store');
    Route::get('trainees/{trainee}', [TraineeController::class, 'show'])->middleware('permission:training.view')->name('trainees.show');
    Route::get('trainees/{trainee}/edit', [TraineeController::class, 'edit'])->middleware('permission:training.manage')->name('trainees.edit');
    Route::put('trainees/{trainee}', [TraineeController::class, 'update'])->middleware('permission:training.manage')->name('trainees.update');
    Route::delete('trainees/{trainee}', [TraineeController::class, 'destroy'])->middleware('permission:training.manage')->name('trainees.destroy');

    Route::post('trainees/{trainee}/enroll', [EnrollmentController::class, 'store'])->middleware('permission:training.manage')->name('trainees.enroll');
    Route::post('enrollments/{enrollment}/progress', [EnrollmentController::class, 'progress'])->middleware('permission:training.manage')->name('enrollments.progress');
    Route::post('enrollments/{enrollment}/complete', [EnrollmentController::class, 'complete'])->middleware('permission:training.manage')->name('enrollments.complete');
    Route::post('enrollments/{enrollment}/withdraw', [EnrollmentController::class, 'withdraw'])->middleware('permission:training.manage')->name('enrollments.withdraw');
    Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->middleware('permission:training.manage')->name('enrollments.destroy');

    Route::get('certificates', [CertificateController::class, 'index'])->middleware('permission:training.view')->name('certificates.index');
    Route::post('certificates/{certificate}/void', [CertificateController::class, 'void'])->middleware('permission:training.manage')->name('certificates.void');

    Route::get('payroll', [PayrollController::class, 'index'])->middleware('permission:payroll.view')->name('payroll.index');
    Route::get('payroll/create', [PayrollController::class, 'create'])->middleware('permission:payroll.manage')->name('payroll.create');
    Route::post('payroll', [PayrollController::class, 'store'])->middleware('permission:payroll.manage')->name('payroll.store');
    Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->middleware('permission:payroll.view')->name('payroll.show');
    Route::get('payroll/{payroll}/edit', [PayrollController::class, 'edit'])->middleware('permission:payroll.manage')->name('payroll.edit');
    Route::put('payroll/{payroll}', [PayrollController::class, 'update'])->middleware('permission:payroll.manage')->name('payroll.update');
    Route::post('payroll/{payroll}/pay', [PayrollController::class, 'pay'])->middleware('permission:payroll.manage')->name('payroll.pay');
    Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->middleware('permission:payroll.manage')->name('payroll.destroy');

    Route::get('assets', [AssetController::class, 'index'])->middleware('permission:assets.manage')->name('assets.index');
    Route::get('assets/create', [AssetController::class, 'create'])->middleware('permission:assets.manage')->name('assets.create');
    Route::post('assets', [AssetController::class, 'store'])->middleware('permission:assets.manage')->name('assets.store');
    Route::get('assets/{asset}', [AssetController::class, 'show'])->middleware('permission:assets.manage')->name('assets.show');
    Route::get('assets/{asset}/edit', [AssetController::class, 'edit'])->middleware('permission:assets.manage')->name('assets.edit');
    Route::put('assets/{asset}', [AssetController::class, 'update'])->middleware('permission:assets.manage')->name('assets.update');
    Route::delete('assets/{asset}', [AssetController::class, 'destroy'])->middleware('permission:assets.manage')->name('assets.destroy');
    Route::post('assets/{asset}/maintenance', [AssetController::class, 'storeMaintenance'])->middleware('permission:assets.manage')->name('assets.maintenance.store');
    Route::delete('assets/{asset}/maintenance/{maintenance}', [AssetController::class, 'destroyMaintenance'])->middleware('permission:assets.manage')->name('assets.maintenance.destroy');

    Route::get('solar-packages', [SolarPackageController::class, 'index'])->middleware('permission:solar.view')->name('solar-packages.index');
    Route::get('solar-packages/create', [SolarPackageController::class, 'create'])->middleware('permission:solar.manage')->name('solar-packages.create');
    Route::post('solar-packages', [SolarPackageController::class, 'store'])->middleware('permission:solar.manage')->name('solar-packages.store');
    Route::get('solar-packages/{solarPackage}', [SolarPackageController::class, 'show'])->middleware('permission:solar.view')->name('solar-packages.show');
    Route::get('solar-packages/{solarPackage}/edit', [SolarPackageController::class, 'edit'])->middleware('permission:solar.manage')->name('solar-packages.edit');
    Route::put('solar-packages/{solarPackage}', [SolarPackageController::class, 'update'])->middleware('permission:solar.manage')->name('solar-packages.update');
    Route::delete('solar-packages/{solarPackage}', [SolarPackageController::class, 'destroy'])->middleware('permission:solar.manage')->name('solar-packages.destroy');

    Route::get('solar-leads', [SolarLeadController::class, 'index'])->middleware('permission:solar.leads')->name('solar-leads.index');
    Route::get('solar-leads/quotations/{quotation}', [SolarLeadController::class, 'showQuotation'])->middleware('permission:solar.leads')->name('solar-leads.quotations.show');
    Route::post('solar-leads/status', [SolarLeadController::class, 'updateStatus'])->middleware('permission:solar.leads')->name('solar-leads.status');
    Route::get('solar-leads/{calculation}', [SolarLeadController::class, 'show'])->middleware('permission:solar.leads')->name('solar-leads.show');
    Route::post('solar-leads/{calculation}/quotation', [SolarLeadController::class, 'createQuotation'])->middleware('permission:solar.leads')->name('solar-leads.quotation.create');
    Route::delete('solar-leads/{calculation}', [SolarLeadController::class, 'destroy'])->middleware('permission:solar.leads')->name('solar-leads.destroy');
    Route::delete('solar-leads/quotations/{quotation}', [SolarLeadController::class, 'destroyQuotation'])->middleware('permission:solar.leads')->name('solar-leads.quotations.destroy');

    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/', [MarketingController::class, 'index'])->middleware('permission:marketing.newsletter')->name('index');

        Route::get('subscribers', [MarketingController::class, 'subscribers'])->middleware('permission:marketing.newsletter')->name('subscribers.index');
        Route::post('subscribers', [MarketingController::class, 'storeSubscriber'])->middleware('permission:marketing.newsletter')->name('subscribers.store');
        Route::post('subscribers/{subscriber}/toggle', [MarketingController::class, 'toggleSubscriber'])->middleware('permission:marketing.newsletter')->name('subscribers.toggle');
        Route::delete('subscribers/{subscriber}', [MarketingController::class, 'destroySubscriber'])->middleware('permission:marketing.newsletter')->name('subscribers.destroy');

        Route::get('newsletters', [MarketingController::class, 'newsletters'])->middleware('permission:marketing.newsletter')->name('newsletters.index');
        Route::get('newsletters/create', [MarketingController::class, 'newsletterCreate'])->middleware('permission:marketing.newsletter')->name('newsletters.create');
        Route::post('newsletters', [MarketingController::class, 'newsletterStore'])->middleware('permission:marketing.newsletter')->name('newsletters.store');
        Route::get('newsletters/{newsletter}', [MarketingController::class, 'newsletterShow'])->middleware('permission:marketing.newsletter')->name('newsletters.show');
        Route::get('newsletters/{newsletter}/edit', [MarketingController::class, 'newsletterEdit'])->middleware('permission:marketing.newsletter')->name('newsletters.edit');
        Route::put('newsletters/{newsletter}', [MarketingController::class, 'newsletterUpdate'])->middleware('permission:marketing.newsletter')->name('newsletters.update');
        Route::post('newsletters/{newsletter}/send', [MarketingController::class, 'newsletterSend'])->middleware('permission:marketing.newsletter')->name('newsletters.send');
        Route::delete('newsletters/{newsletter}', [MarketingController::class, 'newsletterDestroy'])->middleware('permission:marketing.newsletter')->name('newsletters.destroy');

        Route::get('testimonials', [MarketingController::class, 'testimonials'])->middleware('permission:marketing.testimonials')->name('testimonials.index');
        Route::get('testimonials/create', [MarketingController::class, 'testimonialCreate'])->middleware('permission:marketing.testimonials')->name('testimonials.create');
        Route::post('testimonials', [MarketingController::class, 'testimonialStore'])->middleware('permission:marketing.testimonials')->name('testimonials.store');
        Route::get('testimonials/{testimonial}/edit', [MarketingController::class, 'testimonialEdit'])->middleware('permission:marketing.testimonials')->name('testimonials.edit');
        Route::put('testimonials/{testimonial}', [MarketingController::class, 'testimonialUpdate'])->middleware('permission:marketing.testimonials')->name('testimonials.update');
        Route::post('testimonials/{testimonial}/toggle', [MarketingController::class, 'testimonialToggle'])->middleware('permission:marketing.testimonials')->name('testimonials.toggle');
        Route::delete('testimonials/{testimonial}', [MarketingController::class, 'testimonialDestroy'])->middleware('permission:marketing.testimonials')->name('testimonials.destroy');

        Route::get('feedback', [MarketingController::class, 'feedback'])->middleware('permission:feedback.manage')->name('feedback.index');
        Route::get('feedback/{feedback}', [MarketingController::class, 'feedbackShow'])->middleware('permission:feedback.manage')->name('feedback.show');
        Route::post('feedback/{feedback}/respond', [MarketingController::class, 'feedbackRespond'])->middleware('permission:feedback.manage')->name('feedback.respond');
        Route::delete('feedback/{feedback}', [MarketingController::class, 'feedbackDestroy'])->middleware('permission:feedback.manage')->name('feedback.destroy');
    });

    Route::get('website', [WebsiteController::class, 'index'])->middleware('permission:website.content')->name('website.index');
    Route::post('website', [WebsiteController::class, 'update'])->middleware('permission:website.content')->name('website.update');

    Route::get('media', [WebsiteController::class, 'media'])->middleware('permission:website.media')->name('media.index');
    Route::post('media', [WebsiteController::class, 'mediaStore'])->middleware('permission:website.media')->name('media.store');
    Route::put('media/{media}', [WebsiteController::class, 'mediaUpdate'])->middleware('permission:website.media')->name('media.update');
    Route::delete('media/{media}', [WebsiteController::class, 'mediaDestroy'])->middleware('permission:website.media')->name('media.destroy');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('sales', [ReportController::class, 'sales'])->middleware('permission:reports.view')->name('sales');
        Route::get('purchases', [ReportController::class, 'purchases'])->middleware('permission:reports.view')->name('purchases');
        Route::get('inventory', [ReportController::class, 'inventory'])->middleware('permission:reports.view')->name('inventory');
        Route::get('profit', [ReportController::class, 'profit'])->middleware('permission:reports.profit')->name('profit');
        Route::get('expenses', [ReportController::class, 'expenses'])->middleware('permission:reports.view')->name('expenses');
        Route::get('projects', [ReportController::class, 'projects'])->middleware('permission:reports.view')->name('projects');
        Route::get('payroll', [ReportController::class, 'payroll'])->middleware('permission:reports.view')->name('payroll');
        Route::get('export', [ReportController::class, 'export'])->name('export');
    });

    Route::get('audit-logs', function () {
        abort_unless(request()->user()->hasPermission('audit.view'), 403);

        return Inertia::render('Admin/AuditLogs', [
            'logs' => AuditLog::with('user')->latest()->paginate(20)->withQueryString(),
        ]);
    })->name('audit-logs');

    Route::get('settings', [SettingsController::class, 'index'])->middleware('permission:settings.manage')->name('settings.index');
    Route::post('settings/categories', [SettingsController::class, 'storeCategory'])->middleware('permission:settings.manage')->name('settings.categories.store');
    Route::delete('settings/categories/{category}', [SettingsController::class, 'destroyCategory'])->middleware('permission:settings.manage')->name('settings.categories.destroy');
    Route::post('settings/calculator', [SettingsController::class, 'storeCalculator'])->middleware('permission:settings.manage')->name('settings.calculator.store');

    Route::get('settings/roles', [RoleAccessController::class, 'index'])->middleware('permission:settings.roles')->name('settings.roles');
    Route::post('settings/roles', [RoleAccessController::class, 'store'])->middleware('permission:settings.roles')->name('settings.roles.store');
    Route::post('settings/roles/{role}', [RoleAccessController::class, 'update'])->middleware('permission:settings.roles')->name('settings.roles.update');
    Route::delete('settings/roles/{role}', [RoleAccessController::class, 'destroy'])->middleware('permission:settings.roles')->name('settings.roles.destroy');

    Route::get('notifications', [NotificationController::class, 'index'])->middleware('permission:dashboard.view')->name('notifications.index');
    Route::get('notifications/data', [NotificationController::class, 'data'])->middleware('permission:dashboard.view')->name('notifications.data');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->middleware('permission:dashboard.view')->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->middleware('permission:dashboard.view')->name('notifications.read');
});

// ==========================================================
// WEBHOOKS (Paystack) — signature-verified, CSRF-exempt.
// ==========================================================
Route::post('webhooks/paystack', [PaystackWebhookController::class, 'handle'])
    ->name('webhooks.paystack');

// ==========================================================
// PROFILE (Breeze)
// ==========================================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==========================================================
// BUYER ACCOUNT (website buyers only)
// ==========================================================
Route::middleware(['auth', 'buyer'])->prefix('account')->name('buyer.')->group(function () {
    Route::get('/', [BuyerDashboardController::class, 'index'])->name('dashboard');
    Route::get('orders', [BuyerDashboardController::class, 'orders'])->name('orders');
    Route::post('leave-impersonation', [BuyerController::class, 'leaveImpersonation'])->name('impersonation.leave');
});

require __DIR__.'/auth.php';
require __DIR__.'/portal.php';
