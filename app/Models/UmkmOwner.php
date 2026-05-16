<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class UmkmOwner extends Model { use SoftDeletes; protected $fillable=['umkm_id','owner_name','owner_nik','phone','email','is_active','created_by','updated_by','deleted_by']; protected $casts=['is_active'=>'boolean']; public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); }}

