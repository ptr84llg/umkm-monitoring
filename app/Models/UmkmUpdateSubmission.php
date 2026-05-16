<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class UmkmUpdateSubmission extends Model { protected $fillable=['umkm_id','submitted_by','old_data','new_data','evidence_path','submission_payload','status_data','review_notes','submitted_at','reviewed_at','reviewed_by']; protected $casts=['old_data'=>'array','new_data'=>'array','submission_payload'=>'array','submitted_at'=>'datetime','reviewed_at'=>'datetime']; public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class); } public function reviews(): HasMany { return $this->hasMany(DataValidationReview::class,'submission_id'); }}

