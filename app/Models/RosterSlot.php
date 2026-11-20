<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RosterSlot extends Model
{
    use HasFactory;

    protected $fillable = ['doctor_id', 'slot_date', 'start_time', 'end_time', 'status', 'notes'];

    protected $attributes = [
        'status' => 'available',
    ];

    protected function casts(): array
    {
        return [
            'slot_date' => 'date:Y-m-d',
            'status' => SlotStatus::class,
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Readable slot ID such as SLT-00012, built from the primary key.
     */
    protected function slotCode(): Attribute
    {
        return Attribute::get(fn () => 'SLT-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT));
    }

    /**
     * Start time as "HH:MM" regardless of how the database returns it.
     */
    protected function startTimeLabel(): Attribute
    {
        return Attribute::get(fn () => substr((string) $this->start_time, 0, 5));
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * The one booked appointment that currently holds this slot, if any.
     */
    public function activeAppointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'active_slot_id');
    }

    public function hasActiveBooking(): bool
    {
        return $this->activeAppointment()->exists();
    }

    /**
     * Date and time when the slot starts.
     */
    public function startsAt(): Carbon
    {
        return $this->slot_date->copy()->setTimeFromTimeString((string) $this->start_time);
    }

    /**
     * End time as "HH:MM", or null for old slots that have none.
     */
    protected function endTimeLabel(): Attribute
    {
        return Attribute::get(fn () => $this->end_time ? substr((string) $this->end_time, 0, 5) : null);
    }

    /**
     * "09:00 - 09:30", or just the start time when there is no end time.
     */
    protected function timeRange(): Attribute
    {
        return Attribute::get(fn () => $this->end_time_label
            ? $this->start_time_label.' - '.$this->end_time_label
            : $this->start_time_label);
    }

    public function isAvailable(): bool
    {
        return $this->status === SlotStatus::Available;
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', SlotStatus::Available);
    }

    /**
     * Slots a patient can still book: available, no active booking, and not already in the past.
     */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->available()
            ->whereDoesntHave('activeAppointment')
            ->where(function (Builder $q) {
                $q->whereDate('slot_date', '>', today())
                    ->orWhere(fn (Builder $today) => $today
                        ->whereDate('slot_date', today())
                        ->where('start_time', '>', now()->format('H:i')));
            });
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('slot_date', '>=', today());
    }
}
