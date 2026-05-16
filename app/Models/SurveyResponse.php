<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class SurveyResponse extends Model { protected $fillable=['survey_instrument_id','user_id','status','submitted_at']; protected $casts=['submitted_at'=>'datetime']; public function items(): HasMany { return $this->hasMany(SurveyResponseItem::class); }}

