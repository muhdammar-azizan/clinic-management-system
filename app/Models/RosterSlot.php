<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterSlot extends Model
{
    use HasFactory;

    protected $fillable = ['doctor_id', 'slot_date', 'start_time', 'status', 'notes'];

    protected $attributes = [
        'status' => 'available',
    ];

    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
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

    public function isAvailable(): bool
    {
        return $this->status === SlotStatus::Available;
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', SlotStatus::Available);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('slot_date', '>=', today());
    }
}
