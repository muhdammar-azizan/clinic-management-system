<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property AppointmentStatus $status
 * @property-read User|null $doctor
 * @property-read string $appointment_code
 */
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * @return BelongsTo<RosterSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(RosterSlot::class, 'roster_slot_id');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
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

    /**
     * @return HasOne<MedicalRecord, $this>
     */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    /**
     * A record can be written once the visit has happened (completed, or booked and already started)
     * and only one record is allowed per appointment.
     */
    public function canHaveMedicalRecord(): bool
    {
        $visitHappened = $this->status === AppointmentStatus::Completed
            || ($this->isBooked() && ! $this->slot->startsAt()->isFuture());

        return $visitHappened && ! $this->medicalRecord()->exists();
    }

    /**
     * A booked appointment can be cancelled until its slot starts.
     */
    public function canBeCancelled(): bool
    {
        return $this->isBooked() && $this->slot->startsAt()->isFuture();
    }

    /**
     * Sort by the slot's date and time. Joins roster_slots, so qualify column names when filtering.
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeOrderedBySlot(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->select('appointments.*')
            ->join('roster_slots', 'roster_slots.id', '=', 'appointments.roster_slot_id')
            ->orderBy('roster_slots.slot_date', $direction)
            ->orderBy('roster_slots.start_time', $direction);
    }

    /**
     * Booked appointments whose slot has not started yet.
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('appointments.status', AppointmentStatus::Booked)
            ->whereHas('slot', fn (Builder $slot) => $slot->where(function (Builder $q) {
                $q->whereDate('slot_date', '>', today())
                    ->orWhere(fn (Builder $day) => $day->whereDate('slot_date', today())->where('start_time', '>', now()->format('H:i')));
            }));
    }

    /**
     * Appointments on the given day that are still active (booked or completed).
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeOnDay(Builder $query, $date): Builder
    {
        return $query->whereIn('appointments.status', [AppointmentStatus::Booked, AppointmentStatus::Completed])
            ->whereHas('slot', fn (Builder $slot) => $slot->whereDate('slot_date', $date));
    }

    /**
     * Visits that have happened but have no medical record yet. Matches canHaveMedicalRecord().
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeAwaitingRecord(Builder $query): Builder
    {
        return $query->whereDoesntHave('medicalRecord')
            ->where(function (Builder $q) {
                $q->where('appointments.status', AppointmentStatus::Completed)
                    ->orWhere(fn (Builder $booked) => $booked
                        ->where('appointments.status', AppointmentStatus::Booked)
                        ->whereHas('slot', fn (Builder $slot) => $slot->where(function (Builder $started) {
                            $started->whereDate('slot_date', '<', today())
                                ->orWhere(fn (Builder $day) => $day->whereDate('slot_date', today())->where('start_time', '<=', now()->format('H:i')));
                        })));
            });
    }

    /**
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeBooked(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Booked);
    }
}
