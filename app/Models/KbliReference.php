<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class KbliReference extends Model { protected $fillable=['kbli_version_id','kbli_code','title','description','is_active']; public function version(): BelongsTo { return $this->belongsTo(KbliVersion::class,'kbli_version_id'); }}

