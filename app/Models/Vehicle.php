<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'vehicle_model_id',
        'last_sync_run_id',
        'external_reference',
        'title',
        'version',
        'source_url',
        'vin',
        'registration',
        'vehicle_type',
        'year',
        'registration_date',
        'mileage',
        'is_mileage_guaranteed',
        'is_imported',
        'is_first_hand',
        'energy',
        'gearbox',
        'transmission',
        'power',
        'fiscal_power',
        'emission_wltp',
        'body',
        'color',
        'doors',
        'seats',
        'warranty',
        'electric_range_wltp',
        'battery_capacity',
        'price',
        'merchant_price',
        'partner_price',
        'catalog_price',
        'vat_reclaimable',
        'tax_code',
        'availability_date',
        'source_inserted_at',
        'source_updated_at',
        'source_deleted_at',
        'last_synced_at',
        'is_active',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'is_mileage_guaranteed' => 'boolean',
            'is_imported' => 'boolean',
            'is_first_hand' => 'boolean',
            'electric_range_wltp' => 'decimal:2',
            'battery_capacity' => 'decimal:2',
            'price' => 'decimal:2',
            'merchant_price' => 'decimal:2',
            'partner_price' => 'decimal:2',
            'catalog_price' => 'decimal:2',
            'vat_reclaimable' => 'boolean',
            'availability_date' => 'datetime',
            'source_inserted_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'source_deleted_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
            'raw_payload' => 'array',
        ];
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function lastSyncRun(): BelongsTo
    {
        return $this->belongsTo(SyncRun::class, 'last_sync_run_id');
    }

    public function pictures(): HasMany
    {
        return $this->hasMany(VehiclePicture::class)->orderBy('position');
    }
}
