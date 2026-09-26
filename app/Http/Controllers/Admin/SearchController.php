<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\Project;
use App\Models\Sale;
use App\Models\SolarCalculation;
use App\Models\Staff;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => []]);
        }

        $user = $request->user();
        $needle = '%'.$this->escapeLike($q).'%';
        $groups = [];

        $name = fn (string $name) => $name;

        if ($user->hasPermission('products.view')) {
            $items = Product::where('name', 'like', $needle)
                ->orWhere('sku', 'like', $needle)
                ->orWhere('ref_id', 'like', $needle)
                ->orWhere('brand', 'like', $needle)
                ->limit(5)->get()
                ->map(fn ($p) => [
                    'label' => $p->name,
                    'subtitle' => collect([$p->ref_id, $p->sku])->filter()->unique()->implode(' · '),
                    'href' => "/admin/products/{$p->id}/edit",
                ]);
            if ($items->count()) {
                $groups['products'] = ['key' => 'products', 'label' => $name('Products'), 'icon' => 'bi-box-seam-fill', 'items' => $items];
            }
        }

        if ($user->hasPermission('customers.view')) {
            $items = Customer::where('name', 'like', $needle)
                ->orWhere('phone', 'like', $needle)
                ->orWhere('email', 'like', $needle)
                ->orWhere('ref_id', 'like', $needle)
                ->limit(5)->get()
                ->map(fn ($c) => [
                    'label' => $c->name,
                    'subtitle' => $c->ref_id.($c->phone ? ' · '.$c->phone : ''),
                    'href' => "/admin/customers/{$c->id}",
                ]);
            if ($items->count()) {
                $groups['customers'] = ['key' => 'customers', 'label' => $name('Customers'), 'icon' => 'bi-people-fill', 'items' => $items];
            }
        }

        if ($user->hasPermission('suppliers.view')) {
            $items = Supplier::where('name', 'like', $needle)
                ->orWhere('contact_person', 'like', $needle)
                ->orWhere('phone', 'like', $needle)
                ->orWhere('email', 'like', $needle)
                ->orWhere('ref_id', 'like', $needle)
                ->limit(5)->get()
                ->map(fn ($s) => [
                    'label' => $s->name,
                    'subtitle' => $s->ref_id.($s->phone ? ' · '.$s->phone : ''),
                    'href' => "/admin/suppliers/{$s->id}",
                ]);
            if ($items->count()) {
                $groups['suppliers'] = ['key' => 'suppliers', 'label' => $name('Suppliers'), 'icon' => 'bi-truck', 'items' => $items];
            }
        }

        if ($user->hasPermission('staff.view')) {
            $items = Staff::where('name', 'like', $needle)
                ->orWhere('position', 'like', $needle)
                ->orWhere('phone', 'like', $needle)
                ->orWhere('email', 'like', $needle)
                ->orWhere('ref_id', 'like', $needle)
                ->limit(5)->get()
                ->map(fn ($s) => [
                    'label' => $s->name,
                    'subtitle' => $s->ref_id.($s->position ? ' · '.$s->position : ''),
                    'href' => "/admin/staff/{$s->id}",
                ]);
            if ($items->count()) {
                $groups['staff'] = ['key' => 'staff', 'label' => $name('Staff'), 'icon' => 'bi-person-badge-fill', 'items' => $items];
            }
        }

        if ($user->hasPermission('sales.view')) {
            $items = Sale::where('ref_id', 'like', $needle)
                ->orWhere('invoice_no', 'like', $needle)
                ->with('customer:id,name')
                ->limit(5)->get()
                ->map(fn ($s) => [
                    'label' => $s->invoice_no ?: $s->ref_id,
                    'subtitle' => $s->customer?->name ?? 'Direct sale',
                    'href' => "/admin/sales/{$s->id}",
                ]);
            if ($items->count()) {
                $groups['sales'] = ['key' => 'sales', 'label' => $name('Sales'), 'icon' => 'bi-receipt-cutoff', 'items' => $items];
            }
        }

        if ($user->hasPermission('orders.view')) {
            $items = Order::where('ref_id', 'like', $needle)
                ->orWhere('customer_name', 'like', $needle)
                ->orWhere('customer_phone', 'like', $needle)
                ->with('customer:id,name')
                ->limit(5)->get()
                ->map(fn ($o) => [
                    'label' => $o->ref_id,
                    'subtitle' => $o->customer_name ?: $o->customer?->name,
                    'href' => "/admin/orders/{$o->id}",
                ]);
            if ($items->count()) {
                $groups['orders'] = ['key' => 'orders', 'label' => $name('Orders'), 'icon' => 'bi-bag-fill', 'items' => $items];
            }
        }

        if ($user->hasPermission('projects.view')) {
            $items = Project::where('name', 'like', $needle)
                ->orWhere('ref_id', 'like', $needle)
                ->orWhere('location', 'like', $needle)
                ->limit(5)->get()
                ->map(fn ($p) => [
                    'label' => $p->name,
                    'subtitle' => $p->ref_id.' · '.ucfirst(str_replace('_', ' ', $p->status)),
                    'href' => "/admin/projects/{$p->id}",
                ]);
            if ($items->count()) {
                $groups['projects'] = ['key' => 'projects', 'label' => $name('Solar Projects'), 'icon' => 'bi-lightning-charge-fill', 'items' => $items];
            }
        }

        if ($user->hasPermission('solar.leads')) {
            $items = SolarCalculation::where('customer_name', 'like', $needle)
                ->orWhere('customer_phone', 'like', $needle)
                ->orWhere('customer_email', 'like', $needle)
                ->orWhere('ref_id', 'like', $needle)
                ->limit(5)->get()
                ->map(fn ($l) => [
                    'label' => $l->customer_name ?: $l->ref_id,
                    'subtitle' => $l->ref_id.($l->customer_phone ? ' · '.$l->customer_phone : ''),
                    'href' => "/admin/solar-leads/{$l->id}",
                ]);
            if ($items->count()) {
                $groups['leads'] = ['key' => 'leads', 'label' => $name('Solar Leads'), 'icon' => 'bi-person-lines-fill', 'items' => $items];
            }
        }

        if ($user->hasPermission('payroll.view')) {
            $items = Payroll::where('ref_id', 'like', $needle)
                ->with('staff:id,name')
                ->limit(5)->get()
                ->map(fn ($p) => [
                    'label' => $p->ref_id,
                    'subtitle' => $p->staff?->name.' · '.$p->period,
                    'href' => "/admin/payroll/{$p->id}",
                ]);
            if ($items->count()) {
                $groups['payroll'] = ['key' => 'payroll', 'label' => $name('Payroll'), 'icon' => 'bi-wallet2', 'items' => $items];
            }
        }

        return response()->json(['groups' => array_values($groups)]);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
