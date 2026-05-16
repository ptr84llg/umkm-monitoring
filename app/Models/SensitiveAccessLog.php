<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class SensitiveAccessLog extends Model { protected $fillable=['actor_user_id','sensitive_domain','access_reason','target_type','target_id','accessed_at']; protected $casts=['accessed_at'=>'datetime']; }

