<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class ExpertValidationAssessment extends Model { protected $fillable=['instrument_id','validator_user_id','status','score','notes','submitted_at','locked_at']; protected $casts=['submitted_at'=>'datetime','locked_at'=>'datetime']; }

