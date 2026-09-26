<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\AcademyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CertificateController extends Controller
{
    public function index(Request $request): Response
    {
        $certificates = Certificate::query()
            ->with(['trainee', 'training'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('certificate_no', 'like', "%{$s}%")
                    ->orWhereHas('trainee', fn ($q) => $q->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('training', fn ($q) => $q->where('title', 'like', "%{$s}%"));
            }))
            ->when($request->status === 'void', fn ($q) => $q->where('status', Certificate::STATUS_VOID))
            ->when($request->status === 'issued', fn ($q) => $q->where('status', Certificate::STATUS_ISSUED))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($c) => [
                'id' => $c->id,
                'ref_id' => $c->ref_id,
                'certificate_no' => $c->certificate_no,
                'issued_at' => $c->issued_at?->toDateString(),
                'status' => $c->status,
                'grade' => $c->grade,
                'trainee' => ['id' => $c->trainee->id, 'name' => $c->trainee->name, 'ref_id' => $c->trainee->ref_id],
                'training' => ['id' => $c->training->id, 'title' => $c->training->title],
            ]);

        $summary = [
            'issued' => Certificate::issued()->count(),
            'void' => Certificate::where('status', Certificate::STATUS_VOID)->count(),
        ];

        return Inertia::render('Admin/Certificate/Index', [
            'certificates' => $certificates,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function void(Request $request, Certificate $certificate): RedirectResponse
    {
        try {
            app(AcademyService::class)->voidCertificate($certificate, $request->user()->id);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', "Certificate {$certificate->certificate_no} voided.");
    }
}
