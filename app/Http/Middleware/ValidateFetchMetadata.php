<?php
namespace App\Http\Middleware;
use App\Models\SecurityEventLog;use Closure;use Illuminate\Http\Request;
class ValidateFetchMetadata { public function handle(Request $request, Closure $next){ if(!config('umkm.security.fetch_metadata_enforced',true)) return $next($request); $site=strtolower((string)$request->headers->get('Sec-Fetch-Site','')); if(in_array($request->method(),['POST','PUT','PATCH','DELETE'],true) && in_array($site,['cross-site','none'],true)){ SecurityEventLog::query()->create(['actor_user_id'=>$request->user()?->id,'event_type'=>'blocked_fetch_metadata','severity'=>'high','event_detail'=>'Blocked cross-site unsafe method request','ip_address'=>$request->ip(),'event_time'=>now()]); abort(403); } return $next($request); }}
