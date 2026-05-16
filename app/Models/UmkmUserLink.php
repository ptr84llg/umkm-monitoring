<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class UmkmUserLink extends Model { protected $fillable=['umkm_id','user_id','relationship_type','is_primary']; protected $casts=['is_primary'=>'boolean']; public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); } public function user(): BelongsTo { return $this->belongsTo(User::class); }}

