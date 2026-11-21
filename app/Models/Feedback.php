<?php

namespace App\Models;

use App\Enums\FeedbackRating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property FeedbackRating $rating
 */
class Feedback extends Model
{
    use HasFactory;

    /** The parts of the system a user can give feedback on: key => label. */
    public const AREAS = [
        'booking' => 'Booking an appointment',
        'appointments' => 'My appointments',
        'roster' => 'Doctor roster',
        'records' => 'Medical records',
        'profile' => 'Profile and account',
        'general' => 'The system in general',
    ];

    protected $table = 'feedback';

    protected $fillable = ['user_id', 'rating', 'area', 'comment'];

    protected function casts(): array
    {
        return ['rating' => FeedbackRating::class];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function areaLabel(): string
    {
        return self::AREAS[$this->area] ?? $this->area;
    }
}
