<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispenser extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_MAINTENANCE = 'MAINTENANCE';

    protected $fillable = [
        'branch_id',
        'dispenser_number',
        'name',
        'model',
        'serial_number',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'nozzle_count' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Keep the cached nozzle count in step with reality.
     */
    public function syncNozzleCount(): void
    {
        $this->forceFill([
            'nozzle_count' => $this->nozzles()->count(),
        ])->save();
    }
}
