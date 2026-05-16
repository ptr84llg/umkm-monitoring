<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class UmkmBusinessField extends Model { protected $fillable=['umkm_id','kbli_reference_id','is_primary','local_description','status_data']; protected $casts=['is_primary'=>'boolean']; public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); } public function kbliReference(): BelongsTo { return $this->belongsTo(KbliReference::class); }}

