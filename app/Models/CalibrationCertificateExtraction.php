<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtractionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalibrationCertificateExtraction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'calibration_certificate_extractions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'equipment_id',
        'file_path',
        'file_hash',
        'ai_key_index',
        'file_name',
        'file_size',
        'status',
        'ai_model',
        'extracted_data',
        'error_message',
        'is_applied',
        'applied_at',
        'applied_certificate_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExtractionStatus::class,
            'extracted_data' => 'array',
            'is_applied' => 'boolean',
            'applied_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    /**
     * User who uploaded and initiated the extraction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Equipment associated with the certificate.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * The resulting calibration certificate once applied.
     */
    public function appliedCertificate(): BelongsTo
    {
        return $this->belongsTo(CalibrationCertificate::class, 'applied_certificate_id');
    }

    /**
     * Scope query to pending extractions.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ExtractionStatus::Pending);
    }

    /**
     * Scope query to completed extractions.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ExtractionStatus::Completed);
    }

    /**
     * Scope query to completed extractions not yet applied.
     */
    public function scopeUnapplied(Builder $query): Builder
    {
        return $query->where('status', ExtractionStatus::Completed)
            ->where('is_applied', false);
    }
}
