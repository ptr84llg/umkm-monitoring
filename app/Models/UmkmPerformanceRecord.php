<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class UmkmPerformanceRecord extends Model { protected $fillable=['umkm_id','monitoring_period_id','monthly_revenue','worker_count','production_volume','status_data']; public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); } public function period(): BelongsTo { return $this->belongsTo(MonitoringPeriod::class,'monitoring_period_id'); }}

