<?php
namespace App\Http\Middleware;
use App\Models\ApiRequestLog;use Closure;use Illuminate\Http\Request;
class LogInternalApiRequest { public function handle(Request $request, Closure $next){ $start=microtime(true); $response=$next($request); ApiRequestLog::query()->create(['actor_user_id'=>$request->user()?->id,'method'=>$request->method(),'endpoint'=>$request->path(),'http_status'=>$response->getStatusCode(),'response_time_ms'=>(int)((microtime(true)-$start)*1000),'ip_address'=>$request->ip(),'origin'=>$request->headers->get('Origin'),'requested_at'=>now()]); return $response; }}
