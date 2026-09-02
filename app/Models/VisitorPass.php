<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorPass extends Model
{
   protected $fillable = [
    'building_id', 'pass_number', 'qr_token',
    'visitor_name', 'id_ref', 'id_type', 'purpose', 'status', 'issued_at',
    'is_multi_building', 'current_building_id', 'photo_path',
];

    protected $casts = [
        'issued_at' => 'datetime',
        'is_multi_building' => 'boolean',
    ];

    public function currentBuilding(): BelongsTo
{
    return $this->belongsTo(Building::class, 'current_building_id');
}

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    /**
     * Buildings this pass is authorized for when is_multi_building = true.
     * building_id remains the nominal/primary building for legacy display
     * even on multi-building passes; this pivot is the real source of truth.
     */
    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class, 'pass_building', 'visitor_pass_id', 'building_id');
    }

    /**
     * True if this pass grants access at the given building, whether it's a
     * legacy single-building pass (building_id match) or a multi-building
     * pass (pivot match).
     */
    public function isAuthorizedFor(int $buildingId): bool
    {
        if ($this->is_multi_building) {
            return $this->buildings->contains('id', $buildingId);
        }

        return $this->building_id === $buildingId;
    }

    /**
     * Human-readable list of authorized building(s) for display/logging.
     */
    public function authorizedBuildingNames(): string
    {
        if ($this->is_multi_building) {
            $names = $this->buildings->pluck('name');
            return $names->isNotEmpty() ? $names->join(', ') : 'None';
        }

        return $this->building?->name ?? 'None';
    }
}