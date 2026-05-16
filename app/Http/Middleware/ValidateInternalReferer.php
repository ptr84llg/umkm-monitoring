<?php
namespace App\Http\Middleware;
use App\Models\SecurityEventLog;use Closure;use Illuminate\Http\Request;
class ValidateInternalReferer { public function handle(Request $request, Closure $next){ $referer=$request->headers->get('Referer'); if($referer){ $valid=collect(config('umkm.security.internal_allowed_referers',[]))->contains(fn(string $allowed)=>str_starts_with($referer,$allowed)); if(!$valid){ SecurityEventLog::query()->create(['actor_user_id'=>$request->user()?->id,'event_type'=>'blocked_referer','severity'=>'high','event_detail'=>'Blocked request due to invalid referer','ip_address'=>$request->ip(),'event_time'=>now()]); abort(403); }} return $next($request); }}
