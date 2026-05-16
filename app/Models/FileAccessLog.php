<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class FileAccessLog extends Model { protected $fillable=['actor_user_id','file_reference','access_action','accessed_at','ip_address']; protected $casts=['accessed_at'=>'datetime']; }

