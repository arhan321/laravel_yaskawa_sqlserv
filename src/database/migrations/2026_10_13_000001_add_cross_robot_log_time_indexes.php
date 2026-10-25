<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** PostgreSQL indexes for time-range searches across ALL robots. Additive only. */
return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'CREATE INDEX IF NOT EXISTS rms_telemetry_time_all_idx ON robot_telemetry_samples (sampled_at DESC, id DESC)',
            'CREATE INDEX IF NOT EXISTS rms_operating_time_all_idx ON robot_operating_time_samples (sampled_at DESC, id DESC)',
            'CREATE INDEX IF NOT EXISTS rms_alarm_snap_time_all_idx ON robot_alarm_snapshots (sampled_at DESC, id DESC)',
            'CREATE INDEX IF NOT EXISTS rms_alarm_event_time_all_idx ON robot_alarm_events (started_at DESC, id DESC)',
            'CREATE INDEX IF NOT EXISTS rms_connection_time_all_idx ON robot_connection_events (occurred_at DESC, id DESC)',
            'CREATE INDEX IF NOT EXISTS rms_health_time_all_idx ON robot_health_assessments (evaluated_at DESC, id DESC)',
            'CREATE INDEX IF NOT EXISTS rms_maintenance_log_time_all_idx ON maintenance_logs ((COALESCE(completed_at, started_at, created_at)) DESC, id DESC)',
            'CREATE INDEX IF NOT EXISTS rms_rec_time_all_idx ON maintenance_recommendations (generated_at DESC, id DESC)',
        ];
        foreach ($indexes as $sql) {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        foreach ([
            'rms_telemetry_time_all_idx', 'rms_operating_time_all_idx',
            'rms_alarm_snap_time_all_idx', 'rms_alarm_event_time_all_idx',
            'rms_connection_time_all_idx', 'rms_health_time_all_idx',
            'rms_maintenance_log_time_all_idx', 'rms_rec_time_all_idx',
        ] as $index) {
            DB::statement('DROP INDEX IF EXISTS ' . $index);
        }
    }
};
