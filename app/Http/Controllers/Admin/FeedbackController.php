<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FeedbackRating;
use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'rating' => ['nullable', Rule::enum(FeedbackRating::class)],
            'area' => ['nullable', Rule::in(array_keys(Feedback::AREAS))],
        ]);

        $query = Feedback::query()
            ->when($filters['search'] ?? null, function ($q, $search) {
                $search = trim($search);

                $q->where(fn ($w) => $w
                    ->whereLike('comment', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%")));
            })
            ->when($filters['rating'] ?? null, fn ($q, $rating) => $q->where('rating', $rating))
            ->when($filters['area'] ?? null, fn ($q, $area) => $q->where('area', $area));

        // Summary numbers follow the filters, so "Doctor roster only" gives the roster's own score.
        $total = (clone $query)->count();
        $average = $total > 0 ? round((float) (clone $query)->avg('rating'), 2) : null;
        $counts = (clone $query)->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');

        return view('admin.feedback.index', [
            'feedback' => $query->with('user')->latest('id')->paginate(15)->withQueryString(),
            'total' => $total,
            'average' => $average,
            'acceptable' => $average !== null && $average >= FeedbackRating::ACCEPTABLE_SCORE,
            'counts' => collect(FeedbackRating::cases())->mapWithKeys(fn ($r) => [$r->value => (int) ($counts[$r->value] ?? 0)]),
            'byArea' => $this->averageByArea(),
            'areas' => Feedback::AREAS,
            'filtered' => collect($filters)->filter()->isNotEmpty(),
        ]);
    }

    /**
     * Average score for every area that has feedback, over all feedback.
     *
     * @return Collection<string, array{count: int, average: float}>
     */
    private function averageByArea(): Collection
    {
        return Feedback::query()
            ->selectRaw('area, count(*) as total, avg(rating) as average')
            ->groupBy('area')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->area => ['count' => (int) $row->getAttribute('total'), 'average' => round((float) $row->getAttribute('average'), 2)]]);
    }
}
