<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    const STATUS_ISSUED = 'issued';
    const STATUS_VOID = 'void';

    protected $fillable = [
        'ref_id', 'enrollment_id', 'trainee_id', 'training_id', 'certificate_no',
        'grade', 'issued_at', 'status', 'issued_by', 'voided_by', 'voided_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'voided_at' => 'datetime',
        'grade' => 'float',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }
}