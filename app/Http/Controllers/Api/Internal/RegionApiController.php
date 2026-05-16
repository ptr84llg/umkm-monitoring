<?php
namespace App\Http\Controllers\Api\Internal;
use App\Http\Controllers\Controller;use App\Models\RegionReference;use Illuminate\Http\Request;
class RegionApiController extends Controller { public function children(Request $r){ $r->validate(['parent_id'=>'nullable|integer|exists:region_references,id','level'=>'required|in:province,city_regency,district,village']); $q=RegionReference::query()->where('region_level',$r->string('level'))->where('is_active',true); if($r->filled('parent_id')) $q->where('parent_id',$r->integer('parent_id')); return response()->json(['data'=>$q->orderBy('region_name')->get(['id','region_code','region_name'])]); }}
