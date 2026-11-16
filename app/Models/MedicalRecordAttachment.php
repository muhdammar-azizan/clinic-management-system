<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordAttachment extends Model
{
    use HasFactory;

    public const TYPE_PRESCRIPTION = 'prescription';

    public const TYPE_OTHER = 'other';

    protected $fillable = ['medical_record_id', 'type', 'path', 'original_name', 'mime_type', 'size'];

    protected $attributes = [
        'type' => self::TYPE_OTHER,
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id');
    }

    public function isPrescription(): bool
    {
        return $this->type === self::TYPE_PRESCRIPTION;
    }
}
