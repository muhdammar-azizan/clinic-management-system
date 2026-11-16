<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id', 'roster_slot_id', 'service_id', 'status', 'reason'];

    protected $attributes = [
        'status' => 'booked',
    ];

    protected function casts(): array
    {
        return ['status' => AppointmentStatus::class];
    }

    protected static function booted(): void
    {
        // Keep the "one active booking per slot" guard in sync with status and slot.
        static::saving(function (Appointment $appointment) {
            $appointment->active_slot_id = $appointment->status === AppointmentStatus::Booked
                ? $appointment->roster_slot_id
                : null;
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(RosterSlot::class, 'roster_slot_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * The doctor comes from the booked slot.
     */
    protected function doctor(): Attribute
    {
        return Attribute::get(fn () => $this->slot?->doctor);
    }

    /**
     * Readable appointment ID such as APT-00007.
     */
    protected function appointmentCode(): Attribute
    {
        return Attribute::get(fn () => 'APT-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT));
    }

    public function isBooked(): bool
    {
        return $this->status === AppointmentStatus::Booked;
    }

    public function scopeBooked(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Booked);
    }
}
