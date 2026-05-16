<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class AuditLog extends Model { protected $fillable=['actor_user_id','action','target_type','target_id','before_data','after_data','ip_address','user_agent','event_time']; protected $casts=['before_data'=>'array','after_data'=>'array','event_time'=>'datetime']; }

