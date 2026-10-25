<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\Robot;
use App\Models\RobotTelemetrySample;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends BaseRmsWebController
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'in:24h,7d'],
        ]);
        $period = $validated['period'] ?? '24h';
        $since = $period === '7d' ? now()->subDays(7) : now()->subDay();

        // Only data quality. These samples do NOT contain RUN/IDLE state intervals.
        $perRobot = RobotTelemetrySample::query()
            ->where('sampled_at', '>=', $since)
            ->selectRaw('robot_id, COUNT(*) AS total_samples')
            ->selectRaw('COUNT(*) FILTER (WHERE position_ok = TRUE) AS valid_position')
            ->selectRaw('COUNT(*) FILTER (WHERE torque_ok = TRUE) AS valid_torque')
            ->groupBy('robot_id')
            ->get()
            ->keyBy('robot_id');

        // Cap to 7 days; group by Jakarta calendar dates for consistent display.
        $daily = DB::table('robot_telemetry_samples')
            ->where('sampled_at', '>=', $since)
            ->selectRaw("DATE(sampled_at AT TIME ZONE 'Asia/Jakarta') AS local_day")
            ->selectRaw('COUNT(*) AS total_samples')
            ->selectRaw('COUNT(*) FILTER (WHERE position_ok = TRUE) AS valid_position')
            ->selectRaw('COUNT(*) FILTER (WHERE torque_ok = TRUE) AS valid_torque')
            ->groupByRaw("DATE(sampled_at AT TIME ZONE 'Asia/Jakarta')")
            ->orderBy('local_day')
            ->get();

        return view('rms.analytics.index', [
            'title' => 'Data Quality & Analytics',
            'robots' => Robot::query()->orderBy('robot_code')->get(),
            'perRobot' => $perRobot,
            'daily' => $daily,
            'period' => $period,
            'since' => $since,
            'metrics' => [
                'total_samples' => $perRobot->sum('total_samples'),
                'valid_position_samples' => $perRobot->sum('valid_position'),
                'valid_torque_samples' => $perRobot->sum('valid_torque'),
                'utilization_percent' => null,
                'downtime_minutes' => null,
                'running_minutes' => null,
            ],
            'unavailableReason' => 'Durasi RUN/IDLE/STOP/UNKNOWN dan jadwal shift belum tersedia pada monitoring data saat ini.',
        ]);
    }
}
