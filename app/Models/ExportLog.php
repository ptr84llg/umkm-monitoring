<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class ExportLog extends Model { protected $fillable=['actor_user_id','export_type','export_reason','watermark_token','status','exported_at']; protected $casts=['exported_at'=>'datetime']; }

