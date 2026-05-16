<?php
namespace App\Http\Middleware;
use App\Models\SecurityEventLog;use Closure;use Illuminate\Http\Request;
class ValidateInternalOrigin { public function handle(Request $request, Closure $next){ $origin=$request->headers->get('Origin'); if($origin && !in_array($origin,config('umkm.security.internal_allowed_origins',[]),true)){ SecurityEventLog::query()->create(['actor_user_id'=>$request->user()?->id,'event_type'=>'blocked_origin','severity'=>'high','event_detail'=>'Blocked request due to invalid origin','ip_address'=>$request->ip(),'event_time'=>now()]); abort(403); } return $next($request); }}
