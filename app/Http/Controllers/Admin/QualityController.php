<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Services\QualityReport;
use Illuminate\View\View;

/**
 * Read-only page that shows the saved quality results. It never runs tests itself.
 */
class QualityController extends Controller
{
    public function index(QualityReport $quality): View
    {
        $average = Feedback::count() > 0 ? round((float) Feedback::avg('rating'), 2) : null;

        return view('admin.quality.index', [
            'report' => $quality->build($quality->results(), $quality->loadTest(), $average),
            'feedbackCount' => Feedback::count(),
        ]);
    }
}
