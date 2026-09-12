<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function index(): Response
    {
        $trainee = request()->user()->trainee;

        $certificates = $trainee
            ? $trainee->certificates()->with('training')->latest()->get()
            : collect();

        return Inertia::render('Portal/Certificates', [
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
                    'duration_weeks' => $c->training->duration_weeks,
                ],
            ]),
        ]);
    }

    public function show(Certificate $certificate): Response
    {
        $user = request()->user();

        abort_unless(
            $certificate->trainee->user_id === $user->id || $user->hasPermission('training.view'),
            403
        );

        $certificate->load(['trainee', 'training', 'enrollment']);

        return Inertia::render('Portal/Certificate', [
            'certificate' => [
                'id' => $certificate->id,
                'certificate_no' => $certificate->certificate_no,
                'issued_at' => $certificate->issued_at?->toDateString(),
                'status' => $certificate->status,
                'grade' => $certificate->grade,
                'trainee' => [
                    'name' => $certificate->trainee->name,
                    'ref_id' => $certificate->trainee->ref_id,
                    'type' => $certificate->trainee->type,
                ],
                'training' => [
                    'title' => $certificate->training->title,
                    'level' => $certificate->training->level,
                    'duration_weeks' => $certificate->training->duration_weeks,
                    'ref_id' => $certificate->training->ref_id,
                ],
                'enrolled_at' => $certificate->enrollment?->enrolled_at?->toDateString(),
            ],
        ]);
    }
}