<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class SecurityEventLog extends Model { protected $fillable=['actor_user_id','event_type','severity','event_detail','ip_address','event_time']; protected $casts=['event_time'=>'datetime']; }

