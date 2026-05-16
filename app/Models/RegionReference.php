<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class RegionReference extends Model { protected $fillable=['region_code','region_name','region_level','parent_id','is_active']; public function parent(): BelongsTo { return $this->belongsTo(self::class,'parent_id'); } public function children(): HasMany { return $this->hasMany(self::class,'parent_id'); }}

