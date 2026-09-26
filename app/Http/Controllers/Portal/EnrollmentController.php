<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Training;
use App\Services\AcademyService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class EnrollmentController extends Controller
{
    public function store(Request $request, Training $training): RedirectResponse
    {
        $trainee = $request->user()->trainee;

        if (! $trainee) {
            return redirect()->route('portal.profile.edit')
                ->with('error', 'Complete your profile before enrolling in a program.');
        }

        $mode = $request->input('mode');

        // Paid programs must choose a payment route: online (Paystack) or offline.
        if ($training->price > 0 && $mode !== 'offline') {
            return redirect()->back()
                ->with('error', 'This program requires payment. Use "Pay online" or enroll and complete an offline payment.');
        }

        try {
            app(AcademyService::class)->enroll($trainee, $training->id, $request->user()->id);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($mode === 'offline' && $training->price > 0) {
            PaymentService::recordPayment(
                type: 'payment_in',
                amount: $training->price,
                paymentMethod: 'bank_transfer',
                documentType: 'enrollment',
                documentId: $training->id,
                customerId: null,
                traineeId: $trainee->id,
                supplierId: null,
                reference: null,
                userId: $request->user()->id,
                gateway: Payment::GATEWAY_LOCAL,
                status: Payment::STATUS_PENDING,
                remarks: "Offline enrollment payment for {$training->title}",
            );

            return redirect()->route('portal.dashboard')
                ->with('success', "You've been enrolled in {$training->title}! Complete your offline payment and we'll confirm your seat.");
        }

        return redirect()->route('portal.dashboard')
            ->with('success', "You've been enrolled in {$training->title}.");
    }
}
