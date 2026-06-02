<?php
namespace App\Models\Validation;

use App\Models\Umkm\UmkmUpdateSubmission;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class DataValidationReview extends Model { protected $fillable=['submission_id','reviewer_id','decision','review_note','reviewed_at']; protected $casts=['reviewed_at'=>'datetime']; public function submission(): BelongsTo { return $this->belongsTo(UmkmUpdateSubmission::class,'submission_id'); }}

