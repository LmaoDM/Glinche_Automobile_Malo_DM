<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['RUNNING', 'SUCCESS', 'PARTIAL', 'FAILED'])->default('RUNNING');
            $table->timestampTz('started_at')->useCurrent();
            $table->timestampTz('finished_at')->nullable();
            $table->unsignedInteger('vehicles_received')->default(0);
            $table->unsignedInteger('vehicles_created')->default(0);
            $table->unsignedInteger('vehicles_updated')->default(0);
            $table->unsignedInteger('vehicles_deactivated')->default(0);
            $table->text('error_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
