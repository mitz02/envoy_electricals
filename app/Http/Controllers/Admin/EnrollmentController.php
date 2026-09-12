<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Trainee;
use App\Models\Training;
use App\Services\AcademyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class EnrollmentController extends Controller
{
    public function store(Request $request, Trainee $trainee): RedirectResponse
    {
        $data = $request->validate([
            'training_id' => ['required', 'exists:trainings,id'],
            'enrolled_at' => ['nullable', 'date'],
        ]);

        $training = Training::findOrFail($data['training_id']);

        try {
            app(AcademyService::class)->enroll(
                $trainee,
                $training->id,
                $request->user()->id,
                $data['enrolled_at'] ?? null
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.trainees.show', $trainee->id)
            ->with('success', "{$trainee->name} enrolled in {$training->title}.");
    }

    public function progress(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'grade' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        try {
            app(AcademyService::class)->updateProgress(
                $enrollment,
                (int) $data['progress'],
                $request->user()->id,
                isset($data['grade']) ? (float) $data['grade'] : null,
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $message = $enrollment->status === Enrollment::STATUS_COMPLETED
            ? 'Program marked as completed — certificate issued.'
            : 'Progress updated.';

        return redirect()->back()->with('success', $message);
    }

    public function withdraw(Request $request, Enrollment $enrollment): RedirectResponse
    {
        try {
            app(AcademyService::class)->withdraw($enrollment, $request->user()->id);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Enrollment withdrawn.');
    }

    public function destroy(Request $request, Enrollment $enrollment): RedirectResponse
    {
        app(AcademyService::class)->destroyEnrollment($enrollment, $request->user()->id);

        return redirect()->back()->with('success', 'Enrollment removed.');
    }
}