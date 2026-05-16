<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class SurveyInstrument extends Model { protected $fillable=['code','title','description','status','single_response_per_account','active_from','active_until','created_by']; protected $casts=['active_from'=>'datetime','active_until'=>'datetime','single_response_per_account'=>'boolean']; public function questions(): HasMany { return $this->hasMany(SurveyQuestion::class); }}

