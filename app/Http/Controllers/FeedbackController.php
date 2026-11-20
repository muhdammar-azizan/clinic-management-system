<?php

namespace App\Http\Controllers;

use App\Enums\FeedbackRating;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Any signed-in user can tell the clinic how easy the system was to use.
 */
class FeedbackController extends Controller
{
    public function create(Request $request): View
    {
        return view('feedback.create', [
            'ratings' => [FeedbackRating::Easy, FeedbackRating::Moderate, FeedbackRating::Hard],
            'areas' => Feedback::AREAS,
            'selectedArea' => $request->query('area'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', Rule::enum(FeedbackRating::class)],
            'area' => ['required', Rule::in(array_keys(Feedback::AREAS))],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $request->user()->feedback()->create([
            'rating' => $data['rating'],
            'area' => $data['area'],
            'comment' => filled($data['comment'] ?? null) ? trim($data['comment']) : null,
        ]);

        return to_route('feedback.create')->with('status', 'Thank you for your feedback.');
    }
}
