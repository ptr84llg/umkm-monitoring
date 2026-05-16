<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class UmkmLocation extends Model { use SoftDeletes; protected $fillable=['umkm_id','province_region_id','city_region_id','district_region_id','village_region_id','address_detail','latitude','longitude','status_data']; public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); }}

