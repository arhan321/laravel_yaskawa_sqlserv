<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Structured event history is created ONLY after an alarm can be identified.
        Schema::create('robot_alarm_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('robot_id')->constrained('robots')->restrictOnDelete();
            $table->string('event_key', 160)->nullable(); // Unique occurrence ID; NOT only alarm code.
            $table->string('alarm_code', 60)->nullable();
            $table->string('subcode', 60)->nullable();
            $table->string('alarm_name', 255)->nullable();
            $table->string('severity', 32)->nullable();
            $table->string('status', 24)->default('active'); // active, cleared, unknown
            $table->timestampTz('started_at', 3);
            $table->timestampTz('last_seen_at', 3)->nullable();
            $table->timestampTz('cleared_at', 3)->nullable();
            $table->string('source', 40)->default('ymconnect');
            $table->jsonb('details')->nullable();
            $table->timestampsTz(3);

            $table->unique(['robot_id', 'event_key'], 'alarm_event_occurrence_uq');
            $table->index(['robot_id', 'status', 'started_at'], 'alarm_event_status_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('robot_alarm_events');
    }
};
