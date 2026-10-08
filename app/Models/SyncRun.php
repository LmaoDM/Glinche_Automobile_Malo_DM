<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncRun extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'status',
        'started_at',
        'finished_at',
        'vehicles_received',
        'vehicles_created',
        'vehicles_updated',
        'vehicles_deactivated',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'last_sync_run_id');
    }
}
