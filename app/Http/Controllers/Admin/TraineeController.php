<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Trainee;
use App\Services\AcademyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TraineeController extends Controller
{
    public function index(Request $request): Response
    {
        $trainees = Trainee::query()
            ->withCount(['enrollments' => fn ($q) => $q->where('status', '!=', Enrollment::STATUS_WITHDRAWN)])
            ->withCount(['certificates' => fn ($q) => $q->where('status', Certificate::STATUS_ISSUED)])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%");
            }))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($t) => [
                'id' => $t->id,
                'ref_id' => $t->ref_id,
                'name' => $t->name,
                'type' => $t->type,
                'phone' => $t->phone,
                'email' => $t->email,
                'city' => $t->city,
                'status' => $t->status,
                'enrollments_count' => (int) $t->enrollments_count,
                'certificates_count' => (int) $t->certificates_count,
                'has_login' => (bool) $t->user_id,
            ]);

        $summary = [
            'total' => Trainee::count(),
            'active' => Trainee::where('status', Trainee::STATUS_ACTIVE)->count(),
            'apprentices' => Trainee::where('type', Trainee::TYPE_APPRENTICE)->count(),
            'certificates' => Certificate::issued()->count(),
        ];

        return Inertia::render('Admin/Trainee/Index', [
            'trainees' => $trainees,
            'summary' => $summary,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Trainee/Form', ['trainee' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $trainee = app(AcademyService::class)->createTrainee($data, $request->user()->id);

        return redirect()->route('admin.trainees.show', $trainee->id)->with('success', 'Trainee record created.');
    }

    public function show(Trainee $trainee): Response
    {
        $trainee->load(['user', 'staff']);

        $enrollments = $trainee->enrollments()
            ->with('training')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($e) => [
                'id' => $e->id,
                'ref_id' => $e->ref_id,
                'training' => [
                    'id' => $e->training->id,
                    'title' => $e->training->title,
                    'level' => $e->training->level,
                ],
                'enrolled_at' => $e->enrolled_at?->toDateString(),
                'status' => $e->status,
                'progress' => $e->progress,
                'grade' => $e->grade,
                'certificate_no' => $e->certificate?->certificate_no,
            ]);

        $certificates = $trainee->certificates()->with('training')->latest()->get();

        return Inertia::render('Admin/Trainee/Show', [
            'trainee' => [
                'id' => $trainee->id,
                'ref_id' => $trainee->ref_id,
                'type' => $trainee->type,
                'name' => $trainee->name,
                'phone' => $trainee->phone,
                'email' => $trainee->email,
                'date_of_birth' => $trainee->date_of_birth?->toDateString(),
                'gender' => $trainee->gender,
                'address' => $trainee->address,
                'city' => $trainee->city,
                'education' => $trainee->education,
                'occupation' => $trainee->occupation,
                'emergency_contact_name' => $trainee->emergency_contact_name,
                'emergency_contact_phone' => $trainee->emergency_contact_phone,
                'notes' => $trainee->notes,
                'status' => $trainee->status,
                'has_login' => (bool) $trainee->user_id,
            ],
            'enrollments' => $enrollments,
            'certificates' => $certificates->map(fn ($c) => [
                'id' => $c->id,
                'certificate_no' => $c->certificate_no,
                'issued_at' => $c->issued_at?->toDateString(),
                'status' => $c->status,
                'grade' => $c->grade,
                'training' => ['id' => $c->training->id, 'title' => $c->training->title],
            ]),
            'trainings' => \App\Models\Training::active()
                ->orderBy('title')
                ->get(['id', 'ref_id', 'title'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'ref_id' => $t->ref_id,
                    'title' => $t->title,
                ]),
        ]);
    }

    public function edit(Trainee $trainee): Response
    {
        return Inertia::render('Admin/Trainee/Form', ['trainee' => $trainee]);
    }

    public function update(Request $request, Trainee $trainee): RedirectResponse
    {
        $data = $this->validated($request, $trainee->id);

        app(AcademyService::class)->updateTrainee($trainee, $data, $request->user()->id);

        return redirect()->route('admin.trainees.show', $trainee->id)->with('success', 'Trainee record updated.');
    }

    public function destroy(Request $request, Trainee $trainee): RedirectResponse
    {
        app(AcademyService::class)->destroyTrainee($trainee, $request->user()->id);

        return redirect()->route('admin.trainees.index')->with('success', 'Trainee record removed.');
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $ignore = $ignoreId ?: 0;

        return $request->validate([
            'type' => ['required', 'in:staff,apprentice,trainee'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:trainees,email,' . $ignore],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,active,graduated,withdrawn'],
            'create_login' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'min:8'],
        ]);
    }
}