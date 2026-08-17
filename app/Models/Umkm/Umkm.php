<?php

namespace App\Models\Umkm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Umkm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'umkm_code',
        'business_name',
        'status_data',
        'quality_status',
        'established_date',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'established_date' => 'date',
        'lss_detail_synced_at' => 'datetime',
        'source_first_seen_at' => 'datetime',
        'source_last_seen_at' => 'datetime',
        'source_missing_since' => 'datetime',
        'source_active' => 'boolean',
    ];

    public function owners(): HasMany
    {
        return $this->hasMany(UmkmOwner::class);
    }

    public function userLinks(): HasMany
    {
        return $this->hasMany(UmkmUserLink::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'umkm_user_links')
            ->withPivot(['relationship_type', 'is_primary'])
            ->withTimestamps();
    }

    public function legalities(): HasMany
    {
        return $this->hasMany(UmkmLegality::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(UmkmLocation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(UmkmProduct::class);
    }

    public function businessClassifications(): HasMany
    {
        return $this->hasMany(UmkmBusinessClassification::class);
    }

    public function baselineProfile(): HasOne
    {
        return $this->hasOne(UmkmBaselineProfile::class);
    }

    public function dataQualityFlags(): HasMany
    {
        return $this->hasMany(UmkmDataQualityFlag::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(UmkmMedia::class);
    }

    public function performanceRecords(): HasMany
    {
        return $this->hasMany(UmkmPerformanceRecord::class);
    }

    public function updateSubmissions(): HasMany
    {
        return $this->hasMany(UmkmUpdateSubmission::class);
    }

    public function profileOverrideRevisions(): HasMany
    {
        return $this->hasMany(UmkmProfileOverrideRevision::class);
    }

    public function currentProfileOverride(): HasOne
    {
        return $this->hasOne(UmkmCurrentProfileOverride::class);
    }
}