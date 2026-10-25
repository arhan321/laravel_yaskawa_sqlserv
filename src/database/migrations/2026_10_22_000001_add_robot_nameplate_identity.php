<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('robots', function (Blueprint $table): void {
            $table->string('serial_number', 120)->nullable()->unique();
            $table->string('robot_type', 120)->nullable();
            // The physical plate records year and month, e.g. 2020.03.
            $table->string('nameplate_date', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('robots', function (Blueprint $table): void {
            $table->dropUnique(['serial_number']);
            $table->dropColumn(['serial_number', 'robot_type', 'nameplate_date']);
        });
    }
};
