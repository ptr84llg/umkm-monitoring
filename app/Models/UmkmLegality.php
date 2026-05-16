<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class UmkmLegality extends Model { use SoftDeletes; protected $fillable=['umkm_id','nib_number','oss_risk_level','business_license_number','pb_umku_number','effective_date','expired_date','status_data','created_by','updated_by','deleted_by']; public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); }}

