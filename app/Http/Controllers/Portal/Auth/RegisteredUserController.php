<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Trainee;
use App\Models\User;
use App\Services\AcademyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the portal registration view.
     */
    public function create(): Response
    {
        return Inertia::render('PortalAuth/Register');
    }

    /**
     * Handle an incoming portal registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'type' => ['required', Rule::in([Trainee::TYPE_STAFF, Trainee::TYPE_APPRENTICE, Trainee::TYPE_TRAINEE])],
        ]);

        $trainee = app(AcademyService::class)->createTrainee([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'type' => $validated['type'],
            'password' => $validated['password'],
            'create_login' => true,
        ], null);

        Auth::login($trainee->user);

        return redirect()->route('portal.dashboard');
    }
}