<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class KbliVersion extends Model { protected $fillable=['version_code','name','effective_year','is_active']; public function references(): HasMany { return $this->hasMany(KbliReference::class); }}

