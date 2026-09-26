<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Training;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        $programs = Training::active()
            ->withCount(['enrollments as enrolled_count' => fn ($q) => $q->where('status', '!=', 'withdrawn')])
            ->latest()
            ->get();

        return Inertia::render('Portal/Landing', [
            'programs' => $programs->map(fn ($t) => [
                'id' => $t->id,
                'ref_id' => $t->ref_id,
                'title' => $t->title,
                'description' => $t->description,
                'level' => $t->level,
                'duration_weeks' => $t->duration_weeks,
                'capacity' => $t->capacity,
                'enrolled_count' => (int) $t->enrolled_count,
            ]),
            'stats' => [
                'programs' => $programs->count(),
                'graduates' => Certificate::issued()->count(),
            ],
        ]);
    }
}
