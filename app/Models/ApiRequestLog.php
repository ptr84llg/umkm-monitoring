<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class ApiRequestLog extends Model { protected $fillable=['actor_user_id','method','endpoint','http_status','response_time_ms','ip_address','origin','requested_at']; protected $casts=['requested_at'=>'datetime']; }

