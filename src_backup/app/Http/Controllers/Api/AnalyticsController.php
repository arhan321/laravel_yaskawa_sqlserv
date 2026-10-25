<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Robot;
use App\Models\RobotTelemetrySample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Real data-quality metrics; utilization is intentionally unavailable until state intervals exist. */
class AnalyticsController extends BaseRmsController
{
    public function dataQuality(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        // PostgreSQL FILTER syntax, only real readings from the telemetry table.
        $totals = RobotTelemetrySample::query()
            ->select('robot_id')
            ->selectRaw('COUNT(*) AS sample_count')
            ->selectRaw('COUNT(*) FILTER (WHERE position_ok = true) AS position_success_count')
            ->selectRaw('COUNT(*) FILTER (WHERE torque_ok = true) AS torque_success_count')
            ->whereBetween('sampled_at', [$filters['from'], $filters['to']])
            ->groupBy('robot_id')
            ->get()
            ->keyBy('robot_id');

        $perRobot = Robot::query()->orderBy('robot_code')->get(['id', 'robot_code', 'name'])
            ->map(function (Robot $robot) use ($totals): array {
                $row = $totals->get($robot->id);
                return [
                    'robot_id' => $robot->id,
                    'robot_code' => $robot->robot_code,
                    'sample_count' => (int) ($row?->sample_count ?? 0),
                    'position_success_count' => (int) ($row?->position_success_count ?? 0),
                    'torque_success_count' => (int) ($row?->torque_success_count ?? 0),
                ];
            });
        return response()->json([
            'period' => $filters,
            'robots' => $perRobot,
            'utilization_percent' => null,
            'downtime_minutes' => null,
            'limitation' => 'No measured RUN/IDLE/STOP interval history exists in the current migrations.',
        ]);
    }
}
