<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\AcademyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $trainee = request()->user()->trainee;

        return Inertia::render('Portal/Profile', [
            'trainee' => $trainee ? [
                'id' => $trainee->id,
                'ref_id' => $trainee->ref_id,
                'type' => $trainee->type,
                'name' => $trainee->name,
                'email' => $trainee->email,
                'phone' => $trainee->phone,
                'date_of_birth' => $trainee->date_of_birth?->toDateString(),
                'gender' => $trainee->gender,
                'address' => $trainee->address,
                'city' => $trainee->city,
                'education' => $trainee->education,
                'occupation' => $trainee->occupation,
                'emergency_contact_name' => $trainee->emergency_contact_name,
                'emergency_contact_phone' => $trainee->emergency_contact_phone,
                'status' => $trainee->status,
            ] : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:staff,apprentice,trainee'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $request->user();
        $trainee = $user->trainee;

        if (! $trainee) {
            $trainee = app(AcademyService::class)->createTrainee([
                'name' => $validated['name'],
                'email' => $user->email,
                'phone' => $validated['phone'] ?? null,
                'type' => $validated['type'],
            ], $user->id);

            $trainee->update(['user_id' => $user->id]);
        }

        app(AcademyService::class)->updateTrainee($trainee, $validated, $user->id);

        return redirect()->route('portal.profile.edit')
            ->with('success', 'Your profile has been updated.');
    }
}
