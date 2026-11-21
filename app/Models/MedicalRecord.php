<?php

namespace App\Models;

use App\Enums\ConditionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Carbon $visit_date
 * @property Carbon|null $follow_up_date
 * @property ConditionStatus $condition
 * @property-read string $record_code
 */
class MedicalRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'visit_date',
        'chief_complaint',
        'symptoms',
        'diagnosis',
        'treatment_plan',
        'prescription',
        'condition',
        'follow_up_date',
    ];

    protected $attributes = [
        'condition' => 'stable',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'follow_up_date' => 'date',
            'condition' => ConditionStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return HasMany<MedicalRecordAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MedicalRecordAttachment::class);
    }

    /**
     * Readable record ID such as MR-00003.
     */
    protected function recordCode(): Attribute
    {
        return Attribute::get(fn () => 'MR-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT));
    }
}
