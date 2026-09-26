<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\ReferenceGenerator;
use App\Services\UserMailService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Registers a website buyer only. The new account is given the "buyer" role
     * and a linked customer record, so buyers can sign in via /login, see their
     * own dashboard, and show up in the admin customer directory for follow-up.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $buyerRole = Role::where('slug', 'buyer')->firstOrCreate(
            ['slug' => 'buyer'],
            ['name' => 'Buyer', 'description' => 'Registered online customer with a buyer account only.'],
        );

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $buyerRole->id,
            'is_active' => true,
        ]);

        $customer = $user->customer()->make([
            'ref_id' => ReferenceGenerator::generate('customer'),
            'name' => $request->name,
            'email' => $request->email,
            'customer_type' => 'regular',
        ]);
        // Buyers who self-register stay unassigned (no branch) until an admin
        // picks a store; they show up under "All Stores" until then.
        $customer->skipStoreAutoAssign = true;
        $customer->save();

        event(new Registered($user));

        app(UserMailService::class)->welcome($user, 'buyer');

        Auth::login($user);

        return redirect(route('buyer.dashboard'));
    }
}
