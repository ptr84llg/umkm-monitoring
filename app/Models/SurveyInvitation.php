<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class SurveyInvitation extends Model { protected $fillable=['survey_instrument_id','user_id','token','expires_at','is_used']; protected $casts=['expires_at'=>'datetime','is_used'=>'boolean']; }

