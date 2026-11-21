<?php

namespace App\Http\Controllers;

use App\Services\HealthCheck;
use Illuminate\Http\JsonResponse;

/**
 * Deeper than Laravel's own /up page, which only proves the framework starts.
 * This one also checks the database and the storage folders, and answers 503 when one fails.
 */
class HealthController extends Controller
{
    public function __invoke(HealthCheck $health): JsonResponse
    {
        $result = $health->run();

        return response()->json($result, $result['status'] === 'ok' ? 200 : 503)
            ->header('Cache-Control', 'no-store');
    }
}
