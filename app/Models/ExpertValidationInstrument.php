<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class ExpertValidationInstrument extends Model { protected $fillable=['code','title','description','validator_type','status','created_by']; public function assessments(): HasMany { return $this->hasMany(ExpertValidationAssessment::class,'instrument_id'); }}

