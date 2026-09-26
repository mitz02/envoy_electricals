<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Training;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    public function index(Request $request): Response
    {
        $trainings = Training::query()
            ->withCount(['enrollments as enrolled' => fn ($q) => $q->where('status', '!=', Enrollment::STATUS_WITHDRAWN)])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('ref_id', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->level, fn ($q, $l) => $q->where('level', $l))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn ($t) => [
                'id' => $t->id,
                'ref_id' => $t->ref_id,
                'title' => $t->title,
                'description' => $t->description,
                'level' => $t->level,
                'duration_weeks' => $t->duration_weeks,
                'price' => $t->price,
                'capacity' => $t->capacity,
                'enrolled' => (int) $t->enrolled,
                'is_active' => $t->is_active,
                'is_featured' => $t->is_featured,
                'start_date' => $t->start_date?->toDateString(),
                'weeks_count' => $t->weeks()->count(),
            ]);

        $summary = [
            'total' => Training::withTrashed()->count(),
            'active_programs' => Training::where('is_active', true)->count(),
            'enrollments' => Enrollment::where('status', '!=', Enrollment::STATUS_WITHDRAWN)->count(),
            'completed' => Enrollment::completed()->count(),
        ];

        return Inertia::render('Admin/Training/Index', [
            'trainings' => $trainings,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status', 'level']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Training/Form', ['training' => null, 'weeks' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        unset($data['image_path']);

        $training = Training::create([
            ...$data,
            'ref_id' => ReferenceGenerator::generate('training'),
            'created_by' => $request->user()->id,
        ]);

        $this->syncCover($request, $training);

        $this->syncCurriculum($training, $request->array('weeks'));

        AuditLogger::log('created', 'training', $training->id, "Created training program {$training->ref_id}: {$training->title}");

        return redirect()->route('admin.training.show', $training->id)->with('success', 'Training program created.');
    }

    public function show(Training $training): Response
    {
        $training->load(['creator', 'weeks.lessons']);

        $enrollments = $training->enrollments()
            ->with('trainee')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($e) => [
                'id' => $e->id,
                'ref_id' => $e->ref_id,
                'trainee' => [
                    'id' => $e->trainee->id,
                    'ref_id' => $e->trainee->ref_id,
                    'name' => $e->trainee->name,
                    'type' => $e->trainee->type,
                    'phone' => $e->trainee->phone,
                ],
                'enrolled_at' => $e->enrolled_at?->toDateString(),
                'status' => $e->status,
                'progress' => $e->progress,
                'grade' => $e->grade,
                'certificate_no' => $e->certificate?->certificate_no,
            ]);

        return Inertia::render('Admin/Training/Show', [
            'training' => [
                'id' => $training->id,
                'ref_id' => $training->ref_id,
                'title' => $training->title,
                'description' => $training->description,
                'image_path' => $training->image_path,
                'prerequisites' => $training->prerequisites,
                'objectives' => $training->objectives,
                'learning_outcomes' => $training->learning_outcomes,
                'curriculum' => $training->curriculum,
                'duration_weeks' => $training->duration_weeks,
                'start_date' => $training->start_date?->toDateString(),
                'level' => $training->level,
                'price' => $training->price,
                'capacity' => $training->capacity,
                'is_active' => $training->is_active,
                'certificate_eligible' => $training->certificate_eligible,
                'is_featured' => $training->is_featured,
                'weeks' => $training->weeks->map(fn ($w) => [
                    'id' => $w->id,
                    'week_number' => $w->week_number,
                    'title' => $w->title,
                    'summary' => $w->summary,
                    'lessons' => $w->lessons->map(fn ($l) => [
                        'id' => $l->id,
                        'title' => $l->title,
                        'description' => $l->description,
                        'objectives' => $l->objectives,
                        'duration_minutes' => $l->duration_minutes,
                        'position' => $l->position,
                    ])->values(),
                ])->values(),
            ],
            'enrollments' => $enrollments,
        ]);
    }

    public function edit(Training $training): Response
    {
        $training->load(['weeks.lessons']);

        return Inertia::render('Admin/Training/Form', [
            'training' => $training,
            'weeks' => $training->weeks->map(fn ($w) => [
                'week_number' => $w->week_number,
                'title' => $w->title,
                'summary' => $w->summary,
                'lessons' => $w->lessons->map(fn ($l) => [
                    'title' => $l->title,
                    'description' => $l->description,
                    'objectives' => $l->objectives,
                    'duration_minutes' => $l->duration_minutes,
                ])->values(),
            ])->values(),
        ]);
    }

    public function update(Request $request, Training $training): RedirectResponse
    {
        $data = $this->validated($request);

        unset($data['image_path']);

        $training->update($data);

        $this->syncCover($request, $training);

        $this->syncCurriculum($training, $request->array('weeks'));

        AuditLogger::log('updated', 'training', $training->id, "Updated training program {$training->ref_id}: {$training->title}");

        return redirect()->route('admin.training.show', $training->id)->with('success', 'Training program updated.');
    }

    public function destroy(Training $training): RedirectResponse
    {
        $training->delete();

        AuditLogger::log('deleted', 'training', $training->id, "Deleted training program {$training->ref_id}");

        return redirect()->route('admin.training.index')->with('success', 'Training program removed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'cover_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'prerequisites' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'learning_outcomes' => ['nullable', 'string'],
            'curriculum' => ['nullable', 'string'],
            'duration_weeks' => ['nullable', 'integer', 'min:1', 'max:520'],
            'start_date' => ['nullable', 'date'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'certificate_eligible' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'weeks' => ['nullable', 'array'],
            'weeks.*.week_number' => ['nullable', 'integer', 'min:1', 'max:520'],
            'weeks.*.title' => ['required', 'string', 'max:255'],
            'weeks.*.summary' => ['nullable', 'string'],
            'weeks.*.lessons' => ['nullable', 'array'],
            'weeks.*.lessons.*.title' => ['required', 'string', 'max:255'],
            'weeks.*.lessons.*.description' => ['nullable', 'string'],
            'weeks.*.lessons.*.objectives' => ['nullable', 'string'],
            'weeks.*.lessons.*.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);
    }

    /**
     * Store or replace the cover image from an uploaded file. Falls back to the
     * previously saved cover when no new file is supplied.
     */
    protected function syncCover(Request $request, Training $training): void
    {
        if (! $request->hasFile('cover_file')) {
            return;
        }

        $existing = $training->image_path;

        $training->update([
            'image_path' => asset('storage/'.$request->file('cover_file')->store('training-images', 'public')),
        ]);

        if ($existing && str_contains($existing, '/storage/')) {
            $relative = substr($existing, strpos($existing, '/storage/') + strlen('/storage/'));

            if ($relative && Storage::disk('public')->exists($relative)) {
                Storage::disk('public')->delete($relative);
            }
        }
    }

    /**
     * Store or replace the structured curriculum (weeks and lessons).
     */
    protected function syncCurriculum(Training $training, array $weeks): void
    {
        $training->weeks()->delete();

        foreach ($weeks as $index => $week) {
            $weekModel = $training->weeks()->create([
                'week_number' => data_get($week, 'week_number', $index + 1),
                'title' => $week['title'] ?? 'Week '.($index + 1),
                'summary' => data_get($week, 'summary'),
            ]);

            $lessons = array_values(data_get($week, 'lessons', []));

            foreach ($lessons as $pos => $lesson) {
                $weekModel->lessons()->create([
                    'title' => $lesson['title'] ?? 'Lesson '.($pos + 1),
                    'description' => data_get($lesson, 'description'),
                    'objectives' => data_get($lesson, 'objectives'),
                    'duration_minutes' => data_get($lesson, 'duration_minutes'),
                    'position' => $pos + 1,
                ]);
            }
        }
    }
}
