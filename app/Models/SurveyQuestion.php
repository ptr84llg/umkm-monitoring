<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;use Illuminate\Database\Eloquent\Relations\BelongsTo;use Illuminate\Database\Eloquent\Relations\HasMany;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class SurveyQuestion extends Model { protected $fillable=['survey_instrument_id','question_text','question_type','is_required','sort_order']; protected $casts=['is_required'=>'boolean']; }

