<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class UmkmProduct extends Model { use SoftDeletes; protected $fillable=['umkm_id','product_name','product_type','description','status_data']; public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); }}

