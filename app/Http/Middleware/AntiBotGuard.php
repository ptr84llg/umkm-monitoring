<?php
namespace App\Http\Middleware;
use App\Models\SecurityEventLog;use Closure;use Illuminate\Http\Request;
class AntiBotGuard { public function handle(Request $request, Closure $next){ if($request->isMethod('post')){ $score=0; if($request->filled('website')) $score+=70; $tts=(int)$request->input('tts',0); if($tts>0 && $tts<2) $score+=40; if(config('umkm.captcha.provider','none')!=='none' && !$request->filled('captcha_token')) $score+=50; if($score>=60){ SecurityEventLog::query()->create(['actor_user_id'=>$request->user()?->id,'event_type'=>'bot_risk_blocked','severity'=>'high','event_detail'=>'Blocked by anti bot guard score='.$score,'ip_address'=>$request->ip(),'event_time'=>now()]); abort(429,'Aktivitas terdeteksi tidak wajar'); }} return $next($request); }}
