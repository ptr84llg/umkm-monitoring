<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class ExpertValidationInvitation extends Model { protected $fillable=['instrument_id','validator_user_id','invite_token','expires_at','is_used']; protected $casts=['expires_at'=>'datetime','is_used'=>'boolean']; }

