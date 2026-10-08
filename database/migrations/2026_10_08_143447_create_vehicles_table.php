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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_model_id')->constrained()->restrictOnDelete();
            $table->foreignId('last_sync_run_id')->nullable()->constrained('sync_runs')->nullOnDelete();

            $table->string('external_reference', 100)->unique();
            $table->string('title');
            $table->string('version')->nullable();
            $table->text('source_url')->nullable();

            $table->string('vin', 17)->nullable()->unique();
            $table->string('registration', 20)->nullable()->unique();
            $table->string('vehicle_type', 30)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->date('registration_date')->nullable();
            $table->unsignedInteger('mileage')->default(0);
            $table->boolean('is_mileage_guaranteed')->nullable();
            $table->boolean('is_imported')->nullable();
            $table->boolean('is_first_hand')->nullable();

            $table->string('energy', 20);
            $table->string('gearbox', 20)->nullable();
            $table->string('transmission', 100)->nullable();
            $table->unsignedInteger('power')->nullable();
            $table->unsignedInteger('fiscal_power')->nullable();
            $table->unsignedInteger('emission_wltp')->nullable();

            $table->string('body', 50)->nullable();
            $table->string('color', 100)->nullable();
            $table->unsignedSmallInteger('doors')->nullable();
            $table->unsignedSmallInteger('seats')->nullable();
            $table->text('warranty')->nullable();

            $table->decimal('electric_range_wltp', 8, 2)->nullable();
            $table->decimal('battery_capacity', 8, 2)->nullable();

            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('merchant_price', 12, 2)->nullable();
            $table->decimal('partner_price', 12, 2)->nullable();
            $table->decimal('catalog_price', 12, 2)->nullable();
            $table->boolean('vat_reclaimable')->nullable();
            $table->string('tax_code', 20)->nullable();

            $table->timestampTz('availability_date')->nullable();
            $table->timestampTz('source_inserted_at')->nullable();
            $table->timestampTz('source_updated_at')->nullable()->index();
            $table->timestampTz('source_deleted_at')->nullable();
            $table->timestampTz('last_synced_at')->useCurrent();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('raw_payload');
            $table->timestamps();

            $table->index(['is_active', 'vehicle_model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
