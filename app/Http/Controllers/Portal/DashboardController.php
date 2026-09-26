<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\Training;
use App\Services\PaystackService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $trainee = request()->user()->trainee;

        $enrollments = $trainee
            ? Enrollment::with(['training'])
                ->where('trainee_id', $trainee->id)
                ->latest()
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'ref_id' => $e->ref_id,
                    'training' => [
                        'id' => $e->training->id,
                        'title' => $e->training->title,
                        'level' => $e->training->level,
                        'duration_weeks' => $e->training->duration_weeks,
                    ],
                    'enrolled_at' => $e->enrolled_at?->toDateString(),
                    'status' => $e->status,
                    'progress' => $e->progress,
                    'grade' => $e->grade,
                    'certificate_no' => $e->certificate?->certificate_no,
                ])
            : collect();

        $certificates = collect();
        $certificateCount = 0;

        if ($trainee) {
            $certificateCount = Certificate::issued()->where('trainee_id', $trainee->id)->count();
            $certificates = $trainee->certificates()->issued()->with('training')->latest()->get();
        }

        $enrolledTrainingIds = collect($enrollments)->pluck('training.id')->filter()->values()->all();

        $available = Training::active()
            ->when($enrolledTrainingIds, fn ($q) => $q->whereNotIn('id', $enrolledTrainingIds))
            ->withCount(['enrollments as enrolled_count' => fn ($q) => $q->where('status', '!=', 'withdrawn')])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'ref_id' => $t->ref_id,
                'title' => $t->title,
                'description' => $t->description,
                'image_path' => $t->image_path,
                'start_date' => $t->start_date?->toDateString(),
                'level' => $t->level,
                'duration_weeks' => $t->duration_weeks,
                'price' => $t->price,
                'capacity' => $t->capacity,
                'enrolled_count' => (int) $t->enrolled_count,
            ]);

        $bankDetails = collect(Setting::where('group', 'bank')->pluck('value', 'key'))
            ->mapWithKeys(fn ($value, $key) => [str_replace('bank.', '', $key) => $value]);

        return Inertia::render('Portal/Dashboard', [
            'trainee' => $trainee ? [
                'id' => $trainee->id,
                'ref_id' => $trainee->ref_id,
                'type' => $trainee->type,
                'name' => $trainee->name,
                'email' => $trainee->email,
                'phone' => $trainee->phone,
                'date_of_birth' => $trainee->date_of_birth?->toDateString(),
                'gender' => $trainee->gender,
                'city' => $trainee->city,
                'education' => $trainee->education,
                'occupation' => $trainee->occupation,
                'status' => $trainee->status,
            ] : null,
            'enrollments' => $enrollments,
            'certificateCount' => $certificateCount,
            'certificates' => $certificates->map(fn ($c) => [
                'id' => $c->id,
                'certificate_no' => $c->certificate_no,
                'issued_at' => $c->issued_at?->toDateString(),
                'status' => $c->status,
                'grade' => $c->grade,
                'training' => [
                    'id' => $c->training->id,
                    'title' => $c->training->title,
                    'level' => $c->training->level,
                ],
            ]),
            'available' => $available,
            'paystackConfigured' => app(PaystackService::class)->isConfigured(),
            'bankDetails' => $bankDetails,
            'whatsappNumber' => preg_replace('/\D/', '', (string) Setting::where('key', 'business.phone')->value('value')),
        ]);
    }
}
