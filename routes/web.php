<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectExpenseController;
use App\Http\Controllers\Admin\ProjectMaterialController;
use App\Http\Controllers\Admin\ProjectMediaController;
use App\Http\Controllers\Admin\ProjectPaymentController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SolarPackageController;
use App\Http\Controllers\Admin\SolarLeadController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\TraineeController;
use App\Http\Controllers\Admin\TrainingController;
use App\Http\Controllers\Admin\WebsiteController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ==========================================================
// PUBLIC WEBSITE (Phase 3 expands this heavily)
// ==========================================================
Route::get('/', function () {
    $featuredProducts = \App\Models\Product::where('is_visible_online', true)
        ->with('images')
        ->orderByDesc('is_featured')
        ->orderBy('name')
        ->limit(8)
        ->get()
        ->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (int) $p->selling_price,
            'image' => $p->images->first()?->path ?? '/images/landing/solar_panels_sky.jpg',
            'category' => $p->category?->name ?? 'Solar',
        ]);

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'featuredProducts' => $featuredProducts,
    ]);
})->name('home');

Route::get('/about', function () {
    return Inertia::render('About', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('about');

Route::get('/contact', function () {
    return Inertia::render('Contact', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('contact');

Route::get('/shop', [StorefrontController::class, 'shop'])->name('shop');
Route::get('/shop/{product}', [StorefrontController::class, 'product'])->name('products.show');

Route::get('/cart', [CheckoutController::class, 'cart'])->name('cart');
Route::get('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/orders/{order:ref_id}/pay', [CheckoutController::class, 'pay'])->name('orders.pay');
Route::post('/orders/{order:ref_id}/paystack', [CheckoutController::class, 'paystack'])->name('orders.paystack');
Route::post('/orders/{order:ref_id}/offline', [CheckoutController::class, 'offline'])->name('orders.offline');

Route::get('/packages', [StorefrontController::class, 'packages'])->name('packages');
Route::get('/packages/{solarPackage}', [StorefrontController::class, 'packageShow'])->name('packages.show');
Route::post('/packages/{solarPackage}/paystack', [StorefrontController::class, 'packagePaystack'])->name('packages.paystack');
Route::get('/packages/{solarPackage}/payment/callback', [StorefrontController::class, 'packagePaymentCallback'])->name('packages.payment.callback');
Route::post('/packages/{solarPackage}/offline', [StorefrontController::class, 'packageOffline'])->name('packages.offline');

Route::get('/calculator', [StorefrontController::class, 'calculator'])->name('calculator');
Route::post('/calculator', [StorefrontController::class, 'calculatorStore'])->name('calculator.store');

Route::get('/projects', [StorefrontController::class, 'projects'])->name('projects');

Route::get('/training', [StorefrontController::class, 'training'])->name('training');
Route::get('/training/{training}', [StorefrontController::class, 'trainingShow'])->name('training.show');
Route::post('/training/{training}/paystack', [StorefrontController::class, 'trainingPaystack'])->name('training.paystack');
Route::get('/training/{training}/payment/callback', [StorefrontController::class, 'trainingPaymentCallback'])->name('training.payment.callback');

Route::post('/feedback', [StorefrontController::class, 'feedback'])->name('feedback.store');
Route::post('/newsletter', [StorefrontController::class, 'newsletter'])->name('newsletter.store');

// ==========================================================
// PRIVATE BUSINESS SYSTEM (Admin)
// ==========================================================
Route::get('/admin', [DashboardController::class, '__invoke'])
    ->middleware(['auth', 'permission:dashboard.view'])
    ->name('admin.dashboard');

// Breeze auth controllers redirect to named route "dashboard" after login/register.
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('products/{product}/movements', [ProductController::class, 'stockMovements'])->name('products.movements');
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->middleware('permission:inventory.adjust')->name('products.adjust-stock');
    Route::get('stock/movements', [ProductController::class, 'stockMovements'])->middleware('permission:inventory.view')->name('stock.movements');

    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('sales/{sale}/void', [SaleController::class, 'destroy'])->middleware('permission:sales.void')->name('sales.void');

    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('purchases/{purchase}/void', [PurchaseController::class, 'destroy'])->middleware('permission:purchases.void')->name('purchases.void');

    Route::resource('customers', CustomerController::class);
    Route::post('customers/quick', [CustomerController::class, 'quickCreate'])->name('customers.quick');
    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);

    Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments.index');
    Route::post('payments', [PaymentController::class, 'store'])->middleware('permission:payments.record')->name('payments.store');
    Route::post('payments/paystack', [PaymentController::class, 'paystack'])->middleware('permission:payments.record')->name('payments.paystack');

    Route::get('orders', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
    Route::post('orders/{order}/pay/{payment}', [OrderController::class, 'confirmPayment'])->middleware('permission:orders.manage')->name('orders.confirm-payment');
    Route::post('orders/{order}/deliver', [OrderController::class, 'deliver'])->middleware('permission:orders.manage')->name('orders.deliver');

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
    Route::delete('solar-leads/{calculation}', [SolarLeadController::class, 'destroy'])->middleware('permission:solar.leads')->name('solar-leads.destroy');
    Route::delete('solar-leads/quotations/{quotation}', [SolarLeadController::class, 'destroyQuotation'])->middleware('permission:solar.leads')->name('solar-leads.quotations.destroy');

    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/', [MarketingController::class, 'index'])->middleware('permission:marketing.newsletter')->name('index');

        Route::get('subscribers', [MarketingController::class, 'subscribers'])->middleware('permission:marketing.newsletter')->name('subscribers.index');
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
            'logs' => \App\Models\AuditLog::with('user')->latest()->paginate(20)->withQueryString(),
        ]);
    })->name('audit-logs');
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

require __DIR__.'/auth.php';
require __DIR__.'/portal.php';